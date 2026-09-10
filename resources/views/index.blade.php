<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faspay SNAP Test Lab</title>
    <style>
        body{font-family:Inter,system-ui,sans-serif;background:#f6f7f9;color:#111827;margin:0;padding:32px}.wrap{max-width:1180px;margin:auto}.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.card{background:white;border:1px solid #e5e7eb;border-radius:16px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.03)}h1{margin:0 0 6px}h2{font-size:18px;margin-top:0}h3{font-size:14px;color:#4b5563;margin:18px 0 8px}.muted{color:#6b7280}.field{margin:10px 0}.field label{display:block;font-size:13px;margin-bottom:5px;color:#374151}.field input,.field textarea,.field select{width:100%;box-sizing:border-box;border:1px solid #d1d5db;border-radius:10px;padding:10px;background:#fff}.field textarea{min-height:160px;font-family:ui-monospace,monospace}.btn{display:inline-block;border:0;border-radius:10px;padding:10px 14px;background:#111827;color:white;text-decoration:none;cursor:pointer}.btn:disabled{opacity:.5;cursor:not-allowed}.btn.secondary{background:#fff;color:#111827;border:1px solid #d1d5db}table{width:100%;border-collapse:collapse;font-size:14px}th,td{text-align:left;padding:10px;border-bottom:1px solid #eee}.badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 8px;background:#f3f4f6;color:#4b5563;font-size:11px;font-weight:700}.badge.pass{background:#ecfdf3;color:#067647}.badge.fail{background:#fef3f2;color:#b42318}.badge.running{background:#eff6ff;color:#175cd3}.badge.running:before{content:"";width:9px;height:9px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin .7s linear infinite}.progress{height:9px;background:#e5e7eb;border-radius:999px;overflow:hidden}.progress span{display:block;height:100%;background:#111827;transition:width .2s}.hidden{display:none}.merchant-summary{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin:12px 0}.merchant-summary dl{display:grid;grid-template-columns:120px 1fr;gap:7px;margin:0}.merchant-summary dt{color:#6b7280}.merchant-summary dd{margin:0}.toast-container{position:fixed;right:24px;top:24px;z-index:1000;display:grid;gap:10px;width:min(360px,calc(100vw - 32px))}.toast{display:flex;justify-content:space-between;gap:12px;padding:13px 14px;border:1px solid #d1d5db;border-left:4px solid #2563eb;border-radius:10px;background:#fff;box-shadow:0 10px 30px rgba(17,24,39,.15);animation:toast-in .18s ease-out}.toast.success{border-left-color:#067647}.toast.error{border-left-color:#b42318}.toast.warning{border-left-color:#b54708}.toast button{border:0;background:transparent;cursor:pointer;color:#6b7280}@keyframes spin{to{transform:rotate(360deg)}}@keyframes toast-in{from{opacity:0;transform:translateY(-8px)}}@media(max-width:850px){.grid{grid-template-columns:1fr}.toast-container{top:12px;right:16px}}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Faspay SNAP Test Lab</h1>
    <p class="muted">Automated certification test runner & official Excel evidence exporter.</p>

    @if(session('status'))<div class="card" style="margin:16px 0">{{ session('status') }}</div>@endif

    <div class="grid">
        <div class="card">
            <h2 id="merchant-form-title">Tambah Merchant</h2>
            <form id="merchant-form" method="post" action="{{ route('faspay-test-lab.merchants.store') }}">
                @csrf
                <div class="field"><label>Nama</label><input name="name" required></div>
                <div class="field"><label>Base URL</label><input name="base_url" value="https://debit-sandbox.faspay.co.id" required></div>
                <div class="field"><label>Merchant ID / X-PARTNER-ID</label><input name="partner_id" required></div>
                <div class="field"><label>CHANNEL-ID</label><input name="channel_id" value="77001" required></div>
                <div class="field"><label>QRIS channelCode</label><input name="qris_channel_code" value="836" required></div>
                <div class="field"><label>Private Key PEM</label><textarea name="private_key" required placeholder="-----BEGIN PRIVATE KEY-----"></textarea></div>
                <button class="btn" id="merchant-submit">Simpan Merchant</button>
                <button class="btn secondary hidden" id="merchant-cancel" type="button">Batal</button>
            </form>
        </div>

        <div class="card">
            <h2>Run Functional Test</h2>
            <form id="run-form" method="post" action="{{ route('faspay-test-lab.runs.store') }}">
                @csrf
                <div class="field">
                    <label>Merchant</label>
                    <select id="merchant-select" name="merchant_id" required>
                        <option value="">Pilih merchant</option>
                        @foreach($merchants as $merchant)
                            <option value="{{ $merchant->id }}" data-name="{{ $merchant->name }}" data-base-url="{{ $merchant->base_url }}" data-partner-id="{{ $merchant->partner_id }}" data-channel-id="{{ $merchant->channel_id }}" data-qris-channel-code="{{ $merchant->qris_channel_code }}">{{ $merchant->name }} — {{ $merchant->partner_id }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="merchant-summary" class="merchant-summary hidden"><dl><dt>Merchant</dt><dd id="summary-name"></dd><dt>Merchant ID</dt><dd id="summary-id"></dd><dt>Environment</dt><dd id="summary-environment"></dd><dt>QRIS Channel</dt><dd id="summary-channel"></dd></dl><button class="btn secondary" id="edit-merchant" type="button" style="margin-top:12px">Edit Merchant</button></div>
                <button class="btn" id="run-all" type="submit" disabled>Run All Automated Tests</button>
            </form>

            <h2 style="margin-top:28px">Cases (QRIS QR MPM)</h2>
            @foreach(['automated' => 'AUTOMATED', 'requires_payment' => 'REQUIRES PAYMENT', 'manual' => 'MANUAL', 'not_applicable' => 'N/A'] as $type => $label)
                <h3>{{ $label }}</h3>
                <table><tbody>
                @foreach(collect($cases)->where('execution_type', $type) as $case)
                    <tr data-case="{{ $case['no'] }}">
                        <td>{{ $case['no'] }}</td><td>{{ $case['scenario'] }}<br><small class="muted">Expected {{ $case['expected_code'] ?? '—' }}</small></td>
                        <td><span class="badge case-status">{{ $type === 'automated' ? 'NOT RUN' : ($type === 'not_applicable' ? 'N/A' : ($type === 'manual' ? 'MANUAL' : 'WAITING')) }}</span></td>
                        <td>@if($type === 'automated')<button class="btn secondary run-case" type="button" data-case="{{ $case['no'] }}" disabled>Run</button>@endif</td>
                    </tr>
                @endforeach
                </tbody></table>
            @endforeach
        </div>
    </div>

    <div id="progress-panel" class="card hidden" style="margin-top:20px">
        <h2>QRIS Functional Test</h2>
        <p id="progress-copy">Preparing test run…</p>
        <div class="progress"><span id="progress-bar" style="width:0"></span></div>
        <p id="progress-summary" class="muted"></p>
        <a id="view-results" class="btn hidden" href="#">View Full Results</a>
        <a id="export-results" class="btn secondary hidden" href="#">Export XLSX</a>
    </div>

    <div class="card" style="margin-top:20px">
        <h2>Recent Runs</h2>
        <table>
            <thead><tr><th>Waktu</th><th>Merchant</th><th>Service</th><th>Pass</th><th></th></tr></thead>
            <tbody>
            @foreach($runs as $run)
                @php($results = collect($run->results))
                <tr>
                    <td>{{ $run->created_at }}</td>
                    <td>{{ $run->merchant?->name }}</td>
                    <td>{{ strtoupper($run->service) }}</td>
                    <td>Automated: {{ $results->where('execution_type', 'automated')->where('result', 'PASS')->count() }}/{{ $results->where('execution_type', 'automated')->count() }} PASS; Manual: {{ $results->where('result', 'MANUAL')->count() }}; N/A: {{ $results->where('result', 'N/A')->count() }}</td>
                    <td><a class="btn secondary" href="{{ route('faspay-test-lab.runs.show', $run) }}">Lihat</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div id="toast-container" class="toast-container" aria-live="polite"></div>
<script>
const routePrefix = '{{ config('faspay-test-lab.route_prefix', 'faspay-test-lab') }}';
const form = document.getElementById('run-form');
const merchantSelect = document.getElementById('merchant-select');
const automatedCases = @json(collect($cases)->where('execution_type', 'automated')->pluck('no')->values());
const csrf = document.querySelector('input[name="_token"]').value;
let activeRun = null;

function showToast(message, type = 'info') {
    const toast = document.createElement('div'); toast.className = `toast ${type}`; toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    const text = document.createElement('span'); text.textContent = message;
    const close = document.createElement('button'); close.type = 'button'; close.setAttribute('aria-label', 'Tutup'); close.textContent = '×'; close.addEventListener('click', () => toast.remove());
    toast.append(text, close); document.getElementById('toast-container').append(toast); setTimeout(() => toast.remove(), 4500);
}

async function responseData(response) {
    let data = {}; try { data = await response.json(); } catch (_) {}
    if (!response.ok) {
        const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(validation || data.message || 'Tidak dapat terhubung ke server.');
    }
    return data;
}

function selectedMerchant() {
    const option = merchantSelect.selectedOptions[0];
    return {
        id: option.value,
        name: option.dataset.name,
        base_url: option.dataset.baseUrl,
        partner_id: option.dataset.partnerId,
        channel_id: option.dataset.channelId,
        qris_channel_code: option.dataset.qrisChannelCode,
    };
}

function syncMerchantSelection() {
    const enabled = Boolean(merchantSelect.value);
    document.getElementById('run-all').disabled = !enabled;
    document.querySelectorAll('.run-case').forEach(button => button.disabled = !enabled);
    const summary = document.getElementById('merchant-summary');
    summary.classList.toggle('hidden', !enabled);
    if (!enabled) return;
    const merchant = selectedMerchant();
    document.getElementById('summary-name').textContent = merchant.name;
    document.getElementById('summary-id').textContent = merchant.partner_id;
    document.getElementById('summary-environment').textContent = merchant.base_url.includes('sandbox') ? 'Sandbox' : 'Production';
    document.getElementById('summary-channel').textContent = merchant.qris_channel_code;
}
merchantSelect.addEventListener('change', syncMerchantSelection);
syncMerchantSelection();

async function createRun() {
    if (!merchantSelect.value) { showToast('Pilih merchant terlebih dahulu.', 'warning'); throw new Error('Pilih merchant terlebih dahulu.'); }
    const response = await fetch(form.action, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}, body: new FormData(form)});
    activeRun = await responseData(response);
    return activeRun;
}

async function executeCase(caseNo) {
    if (!activeRun) await createRun();
    const row = document.querySelector(`[data-case="${caseNo}"]`);
    const badge = row.querySelector('.case-status');
    const button = row.querySelector('.run-case');
    badge.textContent = 'RUNNING'; badge.className = 'badge case-status running';
    const response = await fetch(`/${routePrefix}/runs/${activeRun.run_id}/cases/${caseNo}/execute`, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}});
    if (response.status === 404) { badge.textContent = 'FAIL'; badge.className = 'badge case-status fail'; throw new Error(`Case ${caseNo} tidak ditemukan oleh aplikasi.`); }
    const data = await responseData(response);
    badge.textContent = data.result.result; badge.className = `badge case-status ${data.result.result.toLowerCase()}`;
    if (button) button.textContent = 'Run Again';
    return data.result;
}

document.querySelectorAll('.run-case').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true; const previous = button.textContent; button.textContent = 'Running...';
    try { const result = await executeCase(button.dataset.case); showToast(`Case ${button.dataset.case}: ${result.result}`, result.result === 'PASS' ? 'success' : 'error'); }
    catch (error) { showToast(error.message === 'Failed to fetch' ? 'Tidak dapat terhubung ke server.' : error.message, 'error'); button.textContent = previous; }
    button.disabled = false;
}));

const merchantForm = document.getElementById('merchant-form');
document.getElementById('edit-merchant').addEventListener('click', () => {
    const merchant = selectedMerchant(); merchantForm.dataset.editId = merchant.id;
    document.getElementById('merchant-form-title').textContent = 'Edit Merchant'; document.getElementById('merchant-submit').textContent = 'Update Merchant'; document.getElementById('merchant-cancel').classList.remove('hidden');
    for (const name of ['name', 'base_url', 'partner_id', 'channel_id', 'qris_channel_code']) merchantForm.elements[name].value = merchant[name];
    merchantForm.elements.private_key.value = ''; merchantForm.elements.private_key.required = false; merchantForm.elements.private_key.placeholder = 'Leave blank to keep existing key';
    merchantForm.scrollIntoView({behavior: 'smooth'});
});
document.getElementById('merchant-cancel').addEventListener('click', () => location.reload());
merchantForm.addEventListener('submit', async event => {
    if (!merchantForm.dataset.editId) return;
    event.preventDefault(); const payload = Object.fromEntries(new FormData(merchantForm));
    try {
        const response = await fetch(`/${routePrefix}/merchants/${merchantForm.dataset.editId}`, {method: 'PUT', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(payload)});
        const data = await responseData(response);
        const option = merchantSelect.querySelector(`option[value="${data.merchant.id}"]`);
        option.dataset.name = data.merchant.name; option.dataset.baseUrl = data.merchant.base_url; option.dataset.partnerId = data.merchant.partner_id; option.dataset.channelId = data.merchant.channel_id; option.dataset.qrisChannelCode = data.merchant.qris_channel_code;
        option.textContent = `${data.merchant.name} — ${data.merchant.partner_id}`;
        syncMerchantSelection();
        showToast(data.message, 'success');
    } catch (error) { showToast(error.message, 'error'); }
});

form.addEventListener('submit', async event => {
    event.preventDefault(); activeRun = null;
    const panel = document.getElementById('progress-panel'); panel.classList.remove('hidden');
    document.getElementById('run-all').disabled = true;
    let passed = 0; let failed = 0;
    try {
        await createRun();
        for (let index = 0; index < automatedCases.length; index++) {
            document.getElementById('progress-copy').textContent = `Running ${index + 1} of ${automatedCases.length}`;
            try { const result = await executeCase(automatedCases[index]); result.result === 'PASS' ? passed++ : failed++; } catch (error) { failed++; showToast(error.message, 'error'); }
            document.getElementById('progress-bar').style.width = `${Math.round(((index + 1) / automatedCases.length) * 100)}%`;
            document.getElementById('progress-summary').textContent = `${passed} PASS · ${failed} FAIL`;
        }
        document.getElementById('progress-copy').textContent = 'Automated tests complete';
        const view = document.getElementById('view-results'); view.href = activeRun.show_url; view.classList.remove('hidden');
        const exportLink = document.getElementById('export-results'); exportLink.href = activeRun.export_url; exportLink.classList.remove('hidden');
        showToast(`Automated test selesai: ${passed} PASS, ${failed} FAIL`, failed ? 'warning' : 'success');
    } catch (error) { document.getElementById('progress-copy').textContent = error.message; showToast(error.message, 'error'); }
    document.getElementById('run-all').disabled = !merchantSelect.value;
});
</script>
</body>
</html>
