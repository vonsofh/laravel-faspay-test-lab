<?php

namespace Vonso\FaspayTestLab\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vonso\FaspayTestLab\Models\FaspayCallback;
use Vonso\FaspayTestLab\Models\FaspayMerchant;
use Vonso\FaspayTestLab\Models\FaspayTestRun;
use Vonso\FaspayTestLab\Models\FaspayVaAccount;
use Vonso\FaspayTestLab\Services\DirectDebitNotificationService;
use Vonso\FaspayTestLab\Services\FaspayExcelExportService;
use Vonso\FaspayTestLab\Services\PaymentChannelInquiryService;
use Vonso\FaspayTestLab\Services\QrisNotificationService;
use Vonso\FaspayTestLab\Services\QrisFunctionalTestService;
use Vonso\FaspayTestLab\Services\VaFunctionalTestService;
use Vonso\FaspayTestLab\Services\VaNotificationService;
use Vonso\FaspayTestLab\Services\VaSandboxAccountService;

class FaspayTestLabController extends Controller
{
    public function index(Request $request): View
    {
        $merchants = FaspayMerchant::latest()->get();
        if ($merchants->isEmpty()) {
            return view('faspay-test-lab::merchants.form', ['onboarding' => true]);
        }

        $allRuns = FaspayTestRun::query()->latest()->get(['service', 'status', 'results']);
        $automatedResults = $allRuns->flatMap(
            fn (FaspayTestRun $run): array => collect($run->results ?? [])
                ->where('execution_type', 'automated')
                ->all(),
        );
        $passedResults = $automatedResults->where('result', 'PASS')->count();
        $failedResults = $automatedResults->where('result', 'FAIL')->count();
        $attentionResults = $automatedResults->whereIn('result', ['NOT RUN', 'WAITING'])->count();

        $merchantReadiness = $merchants->mapWithKeys(function (FaspayMerchant $merchant): array {
            $requiredFields = ['name', 'base_url', 'partner_id', 'channel_id', 'qris_channel_code'];
            $missingRequired = collect($requiredFields)
                ->filter(fn (string $field): bool => blank($merchant->{$field}))
                ->values()
                ->all();

            return [$merchant->id => [
                'ready' => blank($missingRequired) && filled($merchant->private_key),
                'has_private_key' => filled($merchant->private_key),
                'missing_required' => $missingRequired,
                'va_configured' => collect([
                    'va_target_url', 'va_inquiry_path', 'va_payment_path', 'va_partner_service_id',
                    'va_test_customer_no', 'va_test_amount',
                ])->filter(fn (string $field): bool => filled($merchant->{$field}))->count(),
                'va_total' => 6,
            ]];
        })->all();

        return view('faspay-test-lab::dashboard', [
            'merchants' => $merchants,
            'runs' => FaspayTestRun::with('merchant')->latest()->limit(5)->get(),
            'qris_cases' => config('faspay-test-lab.qris_cases', []),
            'va_cases' => config('faspay-test-lab.va_cases', []),
            'selectedMerchantId' => $request->integer('merchant'),
            'merchantReadiness' => $merchantReadiness,
            'dashboardMetrics' => [
                'merchant_count' => $merchants->count(),
                'run_count' => $allRuns->count(),
                'qris_runs' => $allRuns->where('service', 'qris')->count(),
                'va_runs' => $allRuns->where('service', 'va')->count(),
                'pass_count' => $passedResults,
                'fail_count' => $failedResults,
                'attention_count' => $attentionResults,
                'automated_count' => $automatedResults->count(),
                'pass_rate' => $automatedResults->count() > 0
                    ? (int) round(($passedResults / $automatedResults->count()) * 100)
                    : 0,
            ],
        ]);
    }

    public function merchants(): View
    {
        return view('faspay-test-lab::merchants.index', [
            'merchants' => FaspayMerchant::withCount('runs')->latest()->get(),
        ]);
    }

    public function createMerchant(): View
    {
        return view('faspay-test-lab::merchants.form');
    }

    public function editMerchant(FaspayMerchant $merchant): View
    {
        return view('faspay-test-lab::merchants.form', compact('merchant'));
    }

    public function createQrisRun(): View
    {
        $merchants = FaspayMerchant::latest()->get();
        if ($merchants->isEmpty()) {
            return view('faspay-test-lab::merchants.form');
        }

        return view('faspay-test-lab::runs.create', [
            'service' => 'qris',
            'merchants' => $merchants,
            'cases' => config('faspay-test-lab.qris_cases', []),
        ]);
    }

    public function createVaRun(): View
    {
        $merchants = FaspayMerchant::latest()->get();
        if ($merchants->isEmpty()) {
            return view('faspay-test-lab::merchants.form');
        }

        return view('faspay-test-lab::runs.create', [
            'service' => 'va',
            'merchants' => $merchants,
            'cases' => config('faspay-test-lab.va_cases', []),
        ]);
    }

    public function va(Request $request, PaymentChannelInquiryService $channelInquiry): View
    {
        $merchants = FaspayMerchant::latest()->get();
        $account = FaspayVaAccount::with('merchant')->where('active', true)->latest()->first();
        $merchant = $merchants->firstWhere('id', $request->integer('merchant'))
            ?? $account?->merchant
            ?? $merchants->first();
        $channels = $merchant !== null ? $channelInquiry->channels($merchant) : [];

        return view('faspay-test-lab::va.index', [
            'merchants' => $merchants,
            'selectedMerchant' => $merchant,
            'account' => $account,
            'channels' => $channels,
        ]);
    }

    public function storeVaAccount(
        Request $request,
        VaSandboxAccountService $accounts,
        PaymentChannelInquiryService $channelInquiry,
    ): RedirectResponse
    {
        $merchantTable = config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');
        $data = $request->validate([
            'merchant_id' => ['required', "exists:{$merchantTable},id"],
            'channel_code' => ['required', 'string'],
            'display_name' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ]+$/'],
            'amount' => ['required', 'integer', 'min:1000', 'max:999999999'],
        ]);

        $merchant = FaspayMerchant::findOrFail($data['merchant_id']);
        $channel = collect($channelInquiry->channels($merchant))->firstWhere('code', $data['channel_code']);
        if ($channel === null) {
            return back()->withInput()->withErrors(['channel_code' => 'Kanal tidak ditemukan.']);
        }

        $accounts->create(
            $merchant,
            $channel,
            $data['display_name'],
            ((int) $data['amount']) * 100,
        );

        return redirect()->route('faspay-test-lab.va.index')->with('status', 'Nomor VA sandbox berhasil dibuat.');
    }

    public function callbacks(Request $request): View
    {
        $service = $request->string('service')->toString();
        $status = $request->string('status')->toString();
        $query = FaspayCallback::query()->latest();

        if (in_array($service, ['qris', 'va_inquiry', 'va_payment', 'direct_debit'], true)) {
            $query->where('service', $service);
        }
        if ($status === 'success') {
            $query->whereBetween('http_status', [200, 299]);
        } elseif ($status === 'rejected') {
            $query->where('http_status', '>=', 400)->where('http_status', '<', 500);
        } elseif ($status === 'error') {
            $query->where('http_status', '>=', 500);
        }

        return view('faspay-test-lab::callbacks.index', [
            'callbacks' => $query->paginate(25)->withQueryString(),
            'service' => $service,
            'status' => $status,
        ]);
    }

    public function showCallback(FaspayCallback $callback): View
    {
        return view('faspay-test-lab::callbacks.show', compact('callback'));
    }

    public function destroyCallback(FaspayCallback $callback): RedirectResponse
    {
        $callback->delete();

        return redirect()->route('faspay-test-lab.callbacks.index')->with('status', 'Callback berhasil dihapus.');
    }

    public function clearCallbacks(): RedirectResponse
    {
        FaspayCallback::query()->delete();

        return redirect()->route('faspay-test-lab.callbacks.index')->with('status', 'Semua riwayat callback berhasil dihapus.');
    }

    public function runs(): View
    {
        return view('faspay-test-lab::runs.index', [
            'runs' => FaspayTestRun::with('merchant')->latest()->paginate(20),
        ]);
    }

    public function storeMerchant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_url' => ['required', 'url'],
            'partner_id' => ['required', 'string'],
            'private_key' => ['required', 'string'],
            'channel_id' => ['required', 'string'],
            'qris_channel_code' => ['required', 'string'],
            'va_target_url' => ['nullable', 'string', 'max:255'],
            'va_inquiry_path' => ['nullable', 'string', 'max:255'],
            'va_payment_path' => ['nullable', 'string', 'max:255'],
            'va_partner_service_id' => ['nullable', 'string', 'max:32'],
            'va_test_customer_no' => ['nullable', 'string', 'max:32'],
            'va_paid_customer_no' => ['nullable', 'string', 'max:32'],
            'va_expired_customer_no' => ['nullable', 'string', 'max:32'],
            'va_test_amount' => ['nullable', 'string', 'max:32'],
        ]);

        $merchant = FaspayMerchant::create([...$data, 'merchant_id' => $data['partner_id']]);

        return redirect()
            ->route('faspay-test-lab.qris.runs.create', ['merchant' => $merchant->id])
            ->with('status', 'Merchant berhasil disimpan.');
    }

    public function updateMerchant(Request $request, FaspayMerchant $merchant): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_url' => ['required', 'url'],
            'partner_id' => ['required', 'string'],
            'private_key' => ['nullable', 'string'],
            'channel_id' => ['required', 'string'],
            'qris_channel_code' => ['required', 'string'],
            'va_target_url' => ['nullable', 'string', 'max:255'],
            'va_inquiry_path' => ['nullable', 'string', 'max:255'],
            'va_payment_path' => ['nullable', 'string', 'max:255'],
            'va_partner_service_id' => ['nullable', 'string', 'max:32'],
            'va_test_customer_no' => ['nullable', 'string', 'max:32'],
            'va_paid_customer_no' => ['nullable', 'string', 'max:32'],
            'va_expired_customer_no' => ['nullable', 'string', 'max:32'],
            'va_test_amount' => ['nullable', 'string', 'max:32'],
        ]);

        if (blank($data['private_key'] ?? null)) {
            unset($data['private_key']);
        }
        $data['merchant_id'] = $data['partner_id'];
        $merchant->update($data);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('faspay-test-lab.merchants.index')
                ->with('status', 'Merchant berhasil diperbarui.');
        }

        return response()->json([
            'ok' => true,
            'message' => 'Merchant berhasil diperbarui.',
            'merchant' => $merchant->fresh()->only([
                'id', 'name', 'base_url', 'partner_id', 'channel_id', 'qris_channel_code',
                'va_target_url', 'va_inquiry_path', 'va_payment_path', 'va_partner_service_id',
                'va_test_customer_no', 'va_paid_customer_no', 'va_expired_customer_no', 'va_test_amount',
            ]),
        ]);
    }

    public function createRun(Request $request): JsonResponse
    {
        $merchantTable = config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');
        $data = $request->validate([
            'merchant_id' => ['required', "exists:{$merchantTable},id"],
            'service' => ['nullable', 'in:qris,va'],
        ]);

        $service = $data['service'] ?? 'qris';
        $casesKey = $service === 'va' ? 'faspay-test-lab.va_cases' : 'faspay-test-lab.qris_cases';

        $run = FaspayTestRun::create([
            'faspay_merchant_id' => $data['merchant_id'],
            'service' => $service,
            'results' => collect(config($casesKey, []))->map(function (array $case): array {
                if ($case['execution_type'] !== 'automated') {
                    return $this->placeholderResult($case);
                }

                return $this->placeholderResult($case, 'NOT RUN');
            })->all(),
            'status' => 'pending',
        ]);

        return response()->json([
            'run_id' => $run->public_id,
            'service' => $service,
            'show_url' => route('faspay-test-lab.runs.show', $run),
            'export_url' => route('faspay-test-lab.runs.export', $run),
        ], 201);
    }

    public function executeCase(
        FaspayTestRun $run,
        string $caseNo,
        QrisFunctionalTestService $qrisService,
        VaFunctionalTestService $vaService,
    ): JsonResponse {
        $caseNo = (string) $caseNo;
        $casesKey = $run->service === 'va' ? 'faspay-test-lab.va_cases' : 'faspay-test-lab.qris_cases';
        $case = collect(config($casesKey, []))->first(
            fn (array $configuredCase): bool => (string) $configuredCase['no'] === $caseNo,
        );
        if ($case === null || $case['execution_type'] !== 'automated') {
            return response()->json([
                'ok' => false,
                'case' => $caseNo,
                'message' => "Case {$caseNo} tidak ditemukan oleh aplikasi.",
            ], 404);
        }

        $run->load('merchant');
        $run->update(['status' => 'running']);

        $service = $run->service === 'va' ? $vaService : $qrisService;
        $result = $service->runCase($run->merchant, $case);
        $this->persistResult($run, $result);

        return response()->json([
            'ok' => true,
            'case' => $caseNo,
            'status' => $result['result'],
            'result' => $result,
            'run_status' => $run->fresh()->status,
        ]);
    }

    public function show(FaspayTestRun $run): View
    {
        $run->load('merchant');

        return view('faspay-test-lab::runs.show', compact('run'));
    }

    public function checkPayment(
        Request $request,
        FaspayTestRun $run,
        QrisFunctionalTestService $service,
    ): JsonResponse {
        $data = $request->validate([
            'original_reference_no' => ['nullable', 'string', 'max:255'],
            'original_partner_reference_no' => ['nullable', 'string', 'max:255'],
        ]);

        $run->load('merchant');
        $generate = collect($run->results)->firstWhere('test_no', '18.6');
        $referenceNo = $data['original_reference_no'] ?? data_get($generate, 'metadata.referenceNo');
        $partnerReferenceNo = $data['original_partner_reference_no'] ?? data_get($generate, 'metadata.partnerReferenceNo');
        if (blank($referenceNo) || blank($partnerReferenceNo)) {
            return response()->json([
                'status' => 'REQUIRES QR TRANSACTION',
                'message' => 'Jalankan 18.6 untuk membuat transaksi QRIS terlebih dahulu.',
            ], 422);
        }

        $result = $service->checkPayment(
            $run->merchant,
            (string) $referenceNo,
            (string) $partnerReferenceNo,
        );
        $this->persistResult($run, $result);

        return response()->json(['result' => $result]);
    }

    public function checkVaNotification(FaspayTestRun $run): JsonResponse
    {
        $run->load('merchant');
        $merchant = $run->merchant;
        $vaNumber = $merchant?->getEffectiveVaPartnerServiceId().$merchant?->getEffectiveVaCustomerNo();
        $customerNo = $merchant?->getEffectiveVaCustomerNo();

        // 1. Check host application callback log (e.g. sikeu faspay_callback_logs)
        $log = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('faspay_callback_logs')) {
            $log = \Illuminate\Support\Facades\DB::table('faspay_callback_logs')
                ->where('service_code', '25')
                ->where('http_status', 200)
                ->where(function ($q) use ($vaNumber, $customerNo): void {
                    $q->where('virtual_account_no', $vaNumber)
                        ->orWhere('customer_no', $customerNo);
                })
                ->latest('id')
                ->first();
        }

        // 2. Check test lab callback log (faspay_test_lab_callbacks)
        $testLabLog = null;
        $cbTable = config('faspay-test-lab.tables.callbacks', 'faspay_test_lab_callbacks');
        if (! $log && \Illuminate\Support\Facades\Schema::hasTable($cbTable)) {
            $testLabLog = \Illuminate\Support\Facades\DB::table($cbTable)
                ->where('service', 'va_payment')
                ->where('http_status', 200)
                ->latest('id')
                ->first();
        }

        // 3. Fallback: check if Case 11.10 in this run succeeded
        $paymentCase = collect($run->results)->firstWhere('test_no', '11.10');

        if ($log) {
            $reqBody = json_decode($log->request_body, true) ?: [];
            $respBody = json_decode($log->response_body, true) ?: [];
            $requestEvidence = [
                'url' => url($log->request_path ?: $merchant->getEffectiveVaPaymentPath()),
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-TIMESTAMP' => $log->timestamp_header,
                    'X-SIGNATURE' => $log->signature_header,
                    'X-PARTNER-ID' => $log->partner_id ?: $merchant->partner_id,
                    'X-EXTERNAL-ID' => $log->external_id,
                    'CHANNEL-ID' => $log->channel_id ?: $merchant->channel_id,
                ],
                'body' => $reqBody,
            ];
            $responseEvidence = [
                'http_status' => (int) $log->http_status,
                'body' => $respBody,
            ];
        } elseif ($testLabLog) {
            $requestEvidence = [
                'url' => url($merchant->getEffectiveVaPaymentPath()),
                'method' => 'POST',
                'headers' => json_decode($testLabLog->request_headers, true) ?: [],
                'body' => json_decode($testLabLog->request_body, true) ?: [],
            ];
            $responseEvidence = [
                'http_status' => (int) $testLabLog->http_status,
                'body' => json_decode($testLabLog->response_body, true) ?: [],
            ];
        } elseif ($paymentCase && $paymentCase['result'] === 'PASS') {
            $requestEvidence = $paymentCase['request'];
            $responseEvidence = $paymentCase['response'];
        } else {
            return response()->json([
                'ok' => false,
                'result' => [
                    'result' => 'MANUAL',
                    'payment_status' => 'WAITING FOR PAYMENT',
                    'message' => 'Belum ada notifikasi pembayaran yang terdeteksi untuk nomor VA ini.',
                ],
            ]);
        }

        $case11_17 = [
            'test_no' => '11.17',
            'no' => '11.17',
            'service' => 'Payment Notification',
            'execution_type' => 'manual',
            'scenario' => 'Merchant creates a transaction and completes the payment successfully. Upon receiving a successful notification request from Faspay, the merchant responds as outlined in the documentation.',
            'expected_code' => '2002500',
            'expected_message' => 'Successful',
            'request' => $requestEvidence,
            'response' => $responseEvidence,
            'actual_code' => (string) (data_get($responseEvidence, 'body.responseCode') ?? 200),
            'actual_message' => (string) (data_get($responseEvidence, 'body.responseMessage') ?? 'Successful'),
            'passed' => true,
            'result' => 'PASS',
            'notes' => 'Notifikasi sukses dari Faspay berhasil diterima dan diproses oleh sistem merchant.',
            'metadata' => [
                'virtual_account_no' => $vaNumber,
                'detected_at' => now()->toIso8601String(),
            ],
        ];

        $this->persistResult($run, $case11_17);

        return response()->json([
            'ok' => true,
            'result' => [
                'result' => 'PASS',
                'payment_status' => 'PAYMENT DETECTED',
                'message' => 'Notifikasi pembayaran berhasil dideteksi dan dicatat!',
            ],
        ]);
    }

    public function receiveQrisNotification(
        Request $request,
        QrisNotificationService $service,
    ): JsonResponse {
        return $service->handle($request);
    }

    public function receiveVaInquiry(Request $request, VaNotificationService $service): JsonResponse
    {
        return $service->inquiry($request);
    }

    public function receiveVaPayment(Request $request, VaNotificationService $service): JsonResponse
    {
        return $service->payment($request);
    }

    public function receiveDirectDebitNotification(
        Request $request,
        DirectDebitNotificationService $service,
    ): JsonResponse|Response {
        return $service->handle($request);
    }

    public function export(FaspayTestRun $run, FaspayExcelExportService $exporter): BinaryFileResponse
    {
        $run->load('merchant');
        $safeName = Str::slug($run->merchant->name ?: 'merchant');
        $prefix = $run->service === 'va' ? 'FASPAY_VA_Functional_Test_' : 'FASPAY_QRIS_Functional_Test_';
        $filename = $prefix.$safeName.'_'.$run->created_at->format('Ymd_His').'.xlsx';
        $directory = storage_path('app/private/faspay-reports');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $path = $directory.'/'.$filename;

        $exporter->export($run, $path);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }

    private function persistResult(FaspayTestRun $run, array $result): void
    {
        $results = collect($run->fresh()->results)
            ->map(fn (array $item): array => $item['test_no'] === $result['test_no'] ? $result : $item)
            ->values()
            ->all();
        $automatedComplete = collect($results)
            ->where('execution_type', 'automated')
            ->every(fn (array $item): bool => $item['result'] !== 'NOT RUN');
        $run->update(['results' => $results, 'status' => $automatedComplete ? 'completed' : 'running']);
    }

    private function placeholderResult(array $case, ?string $result = null): array
    {
        $result ??= match ($case['execution_type']) {
            'manual' => 'MANUAL',
            'requires_payment' => 'WAITING',
            default => 'N/A',
        };

        return [
            'test_no' => $case['no'], 'no' => $case['no'], 'service' => $case['service'],
            'scenario' => $case['scenario'], 'execution_type' => $case['execution_type'],
            'expected_code' => $case['expected_code'], 'expected_message' => $case['expected_message'] ?? '',
            'actual_code' => null, 'actual_message' => null, 'passed' => null,
            'request' => null, 'response' => null, 'result' => $result,
            'notes' => $case['notes'], 'metadata' => [],
        ];
    }
}
