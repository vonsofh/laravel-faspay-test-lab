<?php

namespace Vonso\FaspayTestLab\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vonso\FaspayTestLab\Models\FaspayMerchant;
use Vonso\FaspayTestLab\Models\FaspayTestRun;
use Vonso\FaspayTestLab\Services\FaspayExcelExportService;
use Vonso\FaspayTestLab\Services\QrisFunctionalTestService;

class FaspayTestLabController extends Controller
{
    public function index(): View
    {
        return view('faspay-test-lab::index', [
            'merchants' => FaspayMerchant::latest()->get(),
            'runs' => FaspayTestRun::with('merchant')->latest()->limit(20)->get(),
            'cases' => config('faspay-test-lab.qris_cases', []),
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
        ]);

        FaspayMerchant::create([...$data, 'merchant_id' => $data['partner_id']]);

        return back()->with('status', 'Merchant ditambahkan.');
    }

    public function updateMerchant(Request $request, FaspayMerchant $merchant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_url' => ['required', 'url'],
            'partner_id' => ['required', 'string'],
            'private_key' => ['nullable', 'string'],
            'channel_id' => ['required', 'string'],
            'qris_channel_code' => ['required', 'string'],
        ]);

        if (blank($data['private_key'] ?? null)) {
            unset($data['private_key']);
        }
        $data['merchant_id'] = $data['partner_id'];
        $merchant->update($data);

        return response()->json([
            'ok' => true,
            'message' => 'Merchant berhasil diperbarui.',
            'merchant' => $merchant->fresh()->only([
                'id', 'name', 'base_url', 'partner_id', 'channel_id', 'qris_channel_code',
            ]),
        ]);
    }

    public function createRun(Request $request): JsonResponse
    {
        $merchantTable = config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');
        $data = $request->validate([
            'merchant_id' => ['required', "exists:{$merchantTable},id"],
        ]);

        $run = FaspayTestRun::create([
            'faspay_merchant_id' => $data['merchant_id'],
            'service' => 'qris',
            'results' => collect(config('faspay-test-lab.qris_cases'))->map(function (array $case): array {
                if ($case['execution_type'] !== 'automated') {
                    return $this->placeholderResult($case);
                }

                return $this->placeholderResult($case, 'NOT RUN');
            })->all(),
            'status' => 'pending',
        ]);

        return response()->json([
            'run_id' => $run->id,
            'show_url' => route('faspay-test-lab.runs.show', $run),
            'export_url' => route('faspay-test-lab.runs.export', $run),
        ], 201);
    }

    public function executeCase(
        FaspayTestRun $run,
        string $caseNo,
        QrisFunctionalTestService $service,
    ): JsonResponse {
        $caseNo = (string) $caseNo;
        $case = collect(config('faspay-test-lab.qris_cases'))->first(
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

        return view('faspay-test-lab::show', compact('run'));
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

    public function export(FaspayTestRun $run, FaspayExcelExportService $exporter): BinaryFileResponse
    {
        $run->load('merchant');
        $safeName = Str::slug($run->merchant->name ?: 'merchant');
        $filename = 'FASPAY_QRIS_Functional_Test_'.$safeName.'_'.$run->created_at->format('Ymd_His').'.xlsx';
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
