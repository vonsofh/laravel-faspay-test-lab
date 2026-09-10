<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Faspay Test Result</title>
    <style>
        body{font-family:Inter,system-ui,sans-serif;background:#f6f7f9;color:#111827;margin:0;padding:32px}.wrap{max-width:1250px;margin:auto}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:18px}.btn{display:inline-block;padding:10px 14px;border:0;border-radius:10px;background:#111827;color:white;text-decoration:none;cursor:pointer}.btn:disabled{opacity:.55}.muted{color:#6b7280}.pass{color:#067647;font-weight:700}.fail{color:#b42318;font-weight:700}.waiting{color:#b54708;font-weight:700}details{margin-top:14px}details>summary{display:inline-block;color:#374151;cursor:pointer;font-weight:600;padding:7px 0;list-style:none}details>summary::-webkit-details-marker{display:none}.evidence summary{display:flex;align-items:center;gap:8px;width:100%;box-sizing:border-box;padding:9px 10px;border-radius:8px;cursor:pointer;user-select:none;transition:background .15s ease}.evidence summary:hover{background:#f3f4f6}.evidence-chevron{display:inline-block;font-size:18px;line-height:1;transition:transform .15s ease}.evidence[open] .evidence-chevron{transform:rotate(90deg)}pre{white-space:pre-wrap;word-break:break-word;background:#111827;color:#f9fafb;padding:14px;border-radius:10px;overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}.summary{display:flex;gap:24px;flex-wrap:wrap}.summary strong{display:block;font-size:20px}.manual-payment-form{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:14px 0;max-width:850px}.manual-payment-form label{display:block;font-size:13px;color:#374151;margin-bottom:6px}.manual-payment-form input{box-sizing:border-box;width:100%;min-height:42px;padding:10px;border:1px solid #d1d5db;border-radius:10px}.manual-payment-form .actions{grid-column:1/-1}.toast-container{position:fixed;right:24px;top:24px;z-index:1000;display:grid;gap:10px;width:min(360px,calc(100vw - 32px))}.toast{display:flex;justify-content:space-between;gap:12px;padding:13px 14px;border:1px solid #d1d5db;border-left:4px solid #2563eb;border-radius:10px;background:#fff;box-shadow:0 10px 30px rgba(17,24,39,.15)}.toast.success{border-left-color:#067647}.toast.error{border-left-color:#b42318}.toast.warning{border-left-color:#b54708}.toast button{border:0;background:transparent;cursor:pointer}@media(max-width:700px){.manual-payment-form{grid-template-columns:1fr}.toast-container{top:12px;right:16px}}
    </style>
</head>
<body><div class="wrap">
    @if(session('status'))<div class="card">{{ session('status') }}</div>@endif
    <div class="card">
        <h1>{{ $run->merchant->name }} — QRIS Functional Test</h1>
        <p class="muted">{{ $run->created_at }}</p>
        <a class="btn" href="{{ route('faspay-test-lab.runs.export', $run) }}">Export XLSX</a>
        <a class="btn" href="{{ route('faspay-test-lab.index') }}" style="background:#fff;color:#111827;border:1px solid #ddd">Kembali</a>
    </div>

    @php($results = collect($run->results))
    <div class="card summary">
        <div><span class="muted">Automated</span><strong>{{ $results->where('execution_type', 'automated')->where('result', 'PASS')->count() }} PASS · {{ $results->where('execution_type', 'automated')->where('result', 'FAIL')->count() }} FAIL</strong></div>
        <div><span class="muted">Requires Payment</span><strong>18.12 — {{ $results->firstWhere('test_no', '18.12')['result'] ?? 'WAITING' }}</strong></div>
        <div><span class="muted">Manual</span><strong>18.25 — Pending</strong></div>
        <div><span class="muted">N/A</span><strong>{{ $results->where('result', 'N/A')->count() }} cases</strong></div>
    </div>

    @foreach($run->results as $item)
        <div class="card">
            <h2>{{ $item['test_no'] }} — {{ $item['scenario'] }}</h2>
            <p class="{{ strtolower($item['result']) }}">{{ $item['result'] }}</p>
            @if($item['test_no'] === '18.6' && $item['result'] === 'PASS')
                <div class="card" style="background:#f8fafc">
                    <strong>QRIS Generated Successfully</strong>
                    <p>Reference No: {{ data_get($item, 'metadata.referenceNo') }}</p>
                    <p>Partner Reference No: {{ data_get($item, 'metadata.partnerReferenceNo') }}</p>
                    <p>Amount: {{ data_get($item, 'metadata.amount') }}</p>
                    @php($qrImage = data_get($item, 'metadata.qrUrl') ?: data_get($item, 'metadata.qrImageUrl'))
                    @if($qrImage)<img src="{{ $qrImage }}" alt="QRIS" style="max-width:220px">@endif
                    <p class="muted">Bayar QR ini melalui simulator Faspay untuk melanjutkan test 18.12.</p>
                    <button class="btn check-payment" type="button" data-run="{{ $run->id }}">Check Payment Status</button>
                </div>
            @endif
            @if($item['test_no'] === '18.12' && in_array($item['result'], ['WAITING', 'MANUAL'], true))
                <p class="muted">Status: WAITING FOR PAYMENT</p>
                @if(filled(data_get($results->firstWhere('test_no', '18.6'), 'metadata.referenceNo')))
                    <p>Reference No: {{ data_get($results->firstWhere('test_no', '18.6'), 'metadata.referenceNo') }}</p>
                    <p>Partner Reference No: {{ data_get($results->firstWhere('test_no', '18.6'), 'metadata.partnerReferenceNo') }}</p>
                    <button class="btn check-payment" type="button" data-run="{{ $run->id }}">Check Payment Status</button>
                    <button class="btn" type="button" id="start-polling" data-run="{{ $run->id }}">Start Auto Check</button>
                @else
                    <p class="waiting">REQUIRES QR TRANSACTION</p>
                    <button class="btn run-again" type="button" data-run="{{ $run->id }}" data-case="18.6">Generate QR for 18.12</button>
                @endif
            @endif
            @if($item['test_no'] === '18.12' && $item['result'] === 'PASS')
                <p class="pass">Payment detected</p>
                @if(data_get($item, 'metadata.transactionStatusDesc'))<p>{{ data_get($item, 'metadata.transactionStatusDesc') }}</p>@endif
                @if(data_get($item, 'metadata.paidTime'))<p>Paid Time: {{ data_get($item, 'metadata.paidTime') }}</p>@endif
                @if(data_get($item, 'metadata.paymentReff'))<p>Payment Reff: {{ data_get($item, 'metadata.paymentReff') }}</p>@endif
            @endif
            @if($item['test_no'] === '18.25')
                <p class="waiting">WAITING FOR CALLBACK</p>
                <p class="muted">Setelah transaksi QRIS dibayar, Faspay akan mengirim notification ke endpoint merchant.</p>
            @endif
            @if(in_array($item['result'], ['N/A', 'MANUAL'], true))
                <p class="muted">{{ $item['notes'] }}</p>
            @endif
            @if($item['test_no'] === '18.12')
                <details><summary>Gunakan transaksi lain</summary>
                <form class="manual-payment-form" method="post" action="{{ route('faspay-test-lab.runs.cases.payment.check', $run) }}">
                    @csrf
                    <div><label for="original-reference-no">Original Reference No</label><input id="original-reference-no" name="original_reference_no" required></div>
                    <div><label for="original-partner-reference-no">Original Partner Reference No</label><input id="original-partner-reference-no" name="original_partner_reference_no" required></div>
                    <div class="actions"><button class="btn" type="submit" data-run="{{ $run->id }}">Check transaksi ini</button></div>
                </form>
                </details>
            @endif
            @if($item['execution_type'] === 'automated' && in_array($item['result'], ['FAIL', 'NOT RUN'], true))
                <button class="btn run-again" type="button" data-run="{{ $run->id }}" data-case="{{ $item['test_no'] }}">Run Again</button>
            @endif
            @if(! in_array($item['result'], ['N/A', 'MANUAL', 'NOT RUN'], true) && is_array($item['request']))
                <table>
                    <tr><th>Expected</th><td>{{ $item['expected_code'] }} — {{ $item['expected_message'] }}</td></tr>
                    <tr><th>Actual</th><td>{{ $item['actual_code'] }} — {{ $item['actual_message'] }}</td></tr>
                </table>
                @if(str_contains((string) $item['actual_message'], 'Invalid Mandatory Field'))
                    <p class="muted">Request rejected before transaction lookup.</p>
                @endif
                <details class="evidence"><summary><span class="evidence-chevron" aria-hidden="true">›</span><span>Request</span></summary><pre>{{ json_encode($item['request'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details>
                <details class="evidence"><summary><span class="evidence-chevron" aria-hidden="true">›</span><span>Response</span></summary><pre>{{ json_encode($item['response'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details>
            @endif
        </div>
    @endforeach
</div>
<div id="toast-container" class="toast-container" aria-live="polite"></div>
<script>
const routePrefix = '{{ config('faspay-test-lab.route_prefix', 'faspay-test-lab') }}';
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

function showToast(message, type = 'info') {
    const toast = document.createElement('div'); toast.className = `toast ${type}`; toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    const text = document.createElement('span'); text.textContent = message; const close = document.createElement('button'); close.type = 'button'; close.textContent = '×'; close.setAttribute('aria-label', 'Tutup'); close.onclick = () => toast.remove();
    toast.append(text, close); document.getElementById('toast-container').append(toast); setTimeout(() => toast.remove(), 4500);
}

async function postJson(url, body = {}) {
    const response = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(body)});
    let data = {}; try { data = await response.json(); } catch (_) {}
    if (!response.ok) { const validation = data.errors ? Object.values(data.errors).flat()[0] : null; throw new Error(validation || data.message || data.status || 'Tidak dapat terhubung ke server.'); }
    return data;
}

document.querySelectorAll('.run-again').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true; button.textContent = 'Running…';
    try { await postJson(`/${routePrefix}/runs/${button.dataset.run}/cases/${button.dataset.case}/execute`); location.reload(); }
    catch (error) { button.textContent = 'Run Again'; button.disabled = false; showToast(error.message, 'error'); }
}));

async function checkPayment(button, body = {}) {
    button.disabled = true; button.textContent = 'CHECKING…';
    try {
        const data = await postJson(`/${routePrefix}/runs/${button.dataset.run}/cases/18.12/check`, body);
        button.textContent = data.result.payment_status;
        if (data.result.result === 'PASS') { showToast('Payment detected.', 'success'); location.reload(); }
        else showToast('Pembayaran masih ditunggu.', 'info');
        return data.result;
    } catch (error) { button.textContent = 'Check Payment Status'; showToast(error.message, 'error'); throw error; }
    finally { button.disabled = false; }
}

document.querySelectorAll('.check-payment').forEach(button => button.addEventListener('click', () => checkPayment(button)));
document.querySelector('.manual-payment-form')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget; const button = form.querySelector('button');
    const values = new FormData(form);
    await checkPayment(button, {original_reference_no: values.get('original_reference_no'), original_partner_reference_no: values.get('original_partner_reference_no')});
});

let polling = false;
document.getElementById('start-polling')?.addEventListener('click', async event => {
    if (polling) return; polling = true;
    const button = event.currentTarget; const deadline = Date.now() + 120000;
    button.disabled = true;
    while (Date.now() < deadline) {
        try { const result = await checkPayment(button); if (result.result === 'PASS') { polling = false; return; } } catch (_) { button.disabled = false; polling = false; return; }
        await new Promise(resolve => setTimeout(resolve, 5000));
    }
    button.textContent = 'Auto-check timeout'; button.disabled = false; polling = false; showToast('Auto-check timeout. Silakan periksa kembali.', 'warning');
});
</script>
</body></html>
