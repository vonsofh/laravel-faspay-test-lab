@extends('faspay-test-lab::layouts.app')

@section('title', 'Pengujian QRIS')

@section('content')
<div class="page-head">
    <div>
        <h1>Pengujian QRIS</h1>
        <p class="lead">Pilih merchant, lalu jalankan delapan skenario otomatis. Jangan tutup halaman selama proses berjalan.</p>
    </div>
</div>

<section class="panel">
    <form id="run-form" method="post" action="{{ route('faspay-test-lab.runs.store') }}">
        @csrf
        <div class="panel-head">
            <div><h2>Pilih merchant</h2></div>
            <a class="btn secondary" href="{{ route('faspay-test-lab.merchants.create') }}">Tambah merchant</a>
        </div>
        <div class="field" style="max-width:440px">
            <label for="merchant-select">Merchant sandbox</label>
            <select id="merchant-select" name="merchant_id" required>
                @foreach($merchants as $merchant)
                    <option value="{{ $merchant->id }}" data-partner-id="{{ $merchant->partner_id }}" data-channel-id="{{ $merchant->channel_id }}" data-qris-code="{{ $merchant->qris_channel_code }}" {{ (string) request('merchant') === (string) $merchant->id ? 'selected' : '' }}>{{ $merchant->name }}</option>
                @endforeach
            </select>
            <div id="merchant-meta" class="help"></div>
        </div>

        <div class="field">
            <label>URL notifikasi QRIS</label>
            <input value="{{ route('faspay-test-lab.qris.notification') }}" readonly>
        </div>

        <div class="scope">
            <div><strong>{{ collect($cases)->where('execution_type', 'automated')->count() }}</strong><span>otomatis</span></div>
            <div><strong>1</strong><span>cek pembayaran</span></div>
            <div><strong>1</strong><span>callback manual</span></div>
            <div><strong>{{ collect($cases)->where('execution_type', 'not_applicable')->count() }}</strong><span>tidak digunakan</span></div>
        </div>

        <details>
            <summary>Lihat semua {{ count($cases) }} skenario</summary>
            <ul class="case-list">
                @foreach($cases as $case)
                    <li data-case="{{ $case['no'] }}">
                        <span class="code">{{ $case['no'] }}</span>
                        <div><strong>{{ $case['scenario'] }}</strong><div class="subtle">{{ $case['service'] }}@if($case['expected_code']) · Expected {{ $case['expected_code'] }}@endif</div></div>
                        <span class="status case-status">{{ $case['execution_type'] === 'automated' ? 'Belum diuji' : ($case['execution_type'] === 'not_applicable' ? 'N/A' : ($case['execution_type'] === 'manual' ? 'Manual' : 'Menunggu')) }}</span>
                    </li>
                @endforeach
            </ul>
        </details>

        <div id="progress-panel" class="hidden" style="margin-top:20px">
            <strong id="progress-title">Memulai pengujian…</strong>
            <div class="progress"><span id="progress-bar"></span></div>
            <div class="meta-line"><span id="progress-copy">Menyiapkan request</span><span id="progress-summary"></span></div>
        </div>

        <div class="btn-row" style="margin-top:24px">
            <button class="btn" id="run-all" type="submit">Jalankan 8 skenario</button>
            <a id="view-results" class="btn secondary hidden" href="#">Buka hasil</a>
            <a id="export-results" class="btn secondary hidden" href="#">Unduh Excel</a>
        </div>
    </form>
</section>
<p class="footer-note">Saat ini hanya pengujian QRIS QR MPM yang tersedia.</p>
@endsection

@push('head')<style>.hidden{display:none!important}</style>@endpush

@push('scripts')
<script>
const form = document.getElementById('run-form');
const merchantSelect = document.getElementById('merchant-select');
const automatedCases = @json(collect($cases)->where('execution_type', 'automated')->pluck('no')->values());
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const executeUrlTemplate = @json(route('faspay-test-lab.runs.cases.execute', ['run' => '__RUN__', 'caseNo' => '__CASE__']));
let activeRun = null;

function toast(message, type = '') {
    const item = document.createElement('div');
    item.className = 'toast';
    if (type === 'error') item.style.background = '#8f1d1d';
    item.textContent = message;
    document.getElementById('toast').append(item);
    setTimeout(() => item.remove(), 4500);
}

async function json(response) {
    let data = {};
    try { data = await response.json(); } catch (_) {}
    if (!response.ok) throw new Error((data.errors && Object.values(data.errors).flat()[0]) || data.message || 'Request gagal.');
    return data;
}

function syncMerchant() {
    const option = merchantSelect.selectedOptions[0];
    document.getElementById('merchant-meta').textContent = `Partner ${option.dataset.partnerId} · Channel ${option.dataset.channelId} · QRIS ${option.dataset.qrisCode}`;
}
merchantSelect.addEventListener('change', () => { activeRun = null; syncMerchant(); });
syncMerchant();

async function createRun() {
    const response = await fetch(form.action, {method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf}, body:new FormData(form)});
    activeRun = await json(response);
}

async function executeCase(caseNo) {
    const row = document.querySelector(`[data-case="${caseNo}"]`);
    const badge = row.querySelector('.case-status');
    badge.textContent = 'Berjalan';
    badge.className = 'status case-status waiting';
    const url = executeUrlTemplate.replace('__RUN__', activeRun.run_id).replace('__CASE__', caseNo);
    const result = await json(await fetch(url, {method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf}}));
    badge.textContent = result.result.result;
    badge.className = `status case-status ${result.result.result.toLowerCase()}`;
    return result.result;
}

form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = document.getElementById('run-all');
    const panel = document.getElementById('progress-panel');
    button.disabled = true;
    panel.classList.remove('hidden');
    activeRun = null;
    let passed = 0;
    let failed = 0;
    try {
        await createRun();
        for (let index = 0; index < automatedCases.length; index++) {
            const caseNo = automatedCases[index];
            document.getElementById('progress-title').textContent = `Menjalankan skenario ${caseNo}`;
            try { (await executeCase(caseNo)).result === 'PASS' ? passed++ : failed++; }
            catch (error) { failed++; toast(error.message, 'error'); }
            const done = index + 1;
            document.getElementById('progress-bar').style.width = `${done / automatedCases.length * 100}%`;
            document.getElementById('progress-copy').textContent = `${done} dari ${automatedCases.length} selesai`;
            document.getElementById('progress-summary').textContent = `${passed} berhasil · ${failed} gagal`;
        }
        document.getElementById('progress-title').textContent = 'Pengujian otomatis selesai';
        const view = document.getElementById('view-results'); view.href = activeRun.show_url; view.classList.remove('hidden');
        const exportLink = document.getElementById('export-results'); exportLink.href = activeRun.export_url; exportLink.classList.remove('hidden');
        toast(`${passed} berhasil, ${failed} gagal.`);
    } catch (error) {
        document.getElementById('progress-title').textContent = 'Pengujian tidak dapat dimulai';
        toast(error.message, 'error');
    } finally { button.disabled = false; }
});
</script>
@endpush
