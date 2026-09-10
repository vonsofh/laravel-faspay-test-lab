@extends('faspay-test-lab::layouts.app')

@section('title', 'Pengujian #'.$run->id)

@section('content')
@php($results = collect($run->results))
@php($automated = $results->where('execution_type', 'automated'))
@php($payment = $results->firstWhere('test_no', '18.12'))
@php($generated = $results->firstWhere('test_no', '18.6'))
@php($passed = $automated->where('result', 'PASS')->count())
@php($failed = $automated->where('result', 'FAIL')->count())
@php($pending = $results->filter(fn (array $item): bool => in_array($item['result'], ['WAITING', 'NOT RUN'], true) || $item['result'] === 'FAIL')->count())
@php($callback = $results->firstWhere('test_no', '18.25'))
@php($notApplicable = $results->where('result', 'N/A')->count())

<header class="report-header">
    <a class="back-link" href="{{ route('faspay-test-lab.runs.index') }}">← Riwayat pengujian</a>
    <div class="report-title-row">
        <div>
            <h1>Hasil pengujian QRIS</h1>
            <p class="lead">{{ $run->merchant?->name ?? 'Merchant dihapus' }} · {{ $run->created_at->format('d M Y, H:i') }} · #{{ $run->id }}</p>
        </div>
        <a class="btn" href="{{ route('faspay-test-lab.runs.export', $run) }}">Unduh Excel</a>
    </div>
</header>

<section class="result-summary" aria-label="Ringkasan hasil">
    <div class="summary-primary">
        <span class="summary-score {{ $failed > 0 ? 'has-failure' : '' }}">{{ $passed }}/{{ $automated->count() }}</span>
        <span>tes otomatis berhasil</span>
    </div>
    <div class="summary-stat"><strong>{{ $failed }}</strong><span>Gagal</span></div>
    <div class="summary-stat"><strong>{{ $pending }}</strong><span>Perlu tindakan</span></div>
    <div class="summary-stat"><strong class="callback-state">{{ ($callback['result'] ?? null) === 'PASS' ? 'Diterima' : 'Menunggu' }}</strong><span>Notifikasi 18.25</span></div>
</section>

@if(in_array($payment['result'] ?? null, ['WAITING', 'MANUAL'], true))
<section class="attention-panel">
    <div class="attention-copy">
        <span class="status waiting">Tindakan diperlukan</span>
        <h2>Verifikasi pembayaran QRIS</h2>
        <p>Bayar QR dari skenario 18.6 di simulator Faspay, lalu periksa status transaksi.</p>
    </div>
    @if(filled(data_get($generated, 'metadata.referenceNo')))
        <div class="payment-workspace">
            @php($qrImage = data_get($generated, 'metadata.qrUrl') ?: data_get($generated, 'metadata.qrImageUrl'))
            @if($qrImage)<img src="{{ $qrImage }}" alt="QRIS untuk pembayaran pengujian">@endif
            <div class="payment-details">
                <dl>
                    <div><dt>Nominal</dt><dd>Rp {{ number_format((float) data_get($generated, 'metadata.amount'), 0, ',', '.') }}</dd></div>
                    <div><dt>Reference</dt><dd>{{ data_get($generated, 'metadata.referenceNo') }}</dd></div>
                    <div><dt>Partner reference</dt><dd>{{ data_get($generated, 'metadata.partnerReferenceNo') }}</dd></div>
                </dl>
                <div class="btn-row"><button class="btn check-payment" type="button" data-url="{{ route('faspay-test-lab.runs.cases.payment.check', $run) }}">Periksa pembayaran</button><button class="btn secondary" type="button" id="start-polling" data-url="{{ route('faspay-test-lab.runs.cases.payment.check', $run) }}">Periksa otomatis</button></div>
            </div>
        </div>
    @else
        <button class="btn run-again" type="button" data-url="{{ route('faspay-test-lab.runs.cases.execute', [$run, '18.6']) }}">Jalankan 18.6</button>
    @endif
</section>
@endif

<section class="test-report">
    <div class="test-toolbar">
        <div><h2>Hasil skenario</h2><p>{{ $results->count() }} skenario sertifikasi</p></div>
        <div class="result-filters" aria-label="Filter hasil">
            <button class="active" type="button" data-filter="all">Semua</button>
            <button type="button" data-filter="action">Perlu tindakan</button>
            <button type="button" data-filter="FAIL">Gagal</button>
            <button type="button" data-filter="PASS">Berhasil</button>
            <button type="button" data-filter="N/A">N/A</button>
        </div>
    </div>

    <div class="test-table" role="table">
        <div class="test-table-head" role="row">
            <span>Kasus</span><span>Skenario</span><span>Service</span><span>Hasil</span><span>Aksi</span>
        </div>
        @foreach($run->results as $item)
            @php($class = match($item['result']) {'PASS'=>'pass','FAIL'=>'fail','WAITING'=>'waiting',default=>''})
            @php($hasEvidence = is_array($item['request']) && !in_array($item['result'], ['N/A', 'MANUAL', 'NOT RUN'], true))
            @php($needsAction = in_array($item['result'], ['FAIL', 'WAITING', 'NOT RUN'], true))
            <article class="test-row" role="row" data-result="{{ $item['result'] }}" data-action="{{ $needsAction ? 'true' : 'false' }}">
                <div class="test-row-main">
                    <span class="case-number">{{ $item['test_no'] }}</span>
                    <span class="scenario-cell"><strong>{{ $item['scenario'] }}</strong>@if(!empty($item['notes']))<small>{{ $item['notes'] }}</small>@endif</span>
                    <span class="service-cell">{{ $item['service'] }}</span>
                    <span><span class="status {{ $class }}">{{ $item['result'] }}</span></span>
                    <span class="row-actions">
                        @if($item['execution_type'] === 'automated' && in_array($item['result'], ['FAIL', 'NOT RUN'], true))
                            <button class="text-button run-again" type="button" data-url="{{ route('faspay-test-lab.runs.cases.execute', [$run, $item['test_no']]) }}">Uji ulang</button>
                        @endif
                        @if($hasEvidence)
                            <button class="text-button open-evidence" type="button" data-case="{{ $item['test_no'] }}">Detail</button>
                        @else
                            <span class="no-action">—</span>
                        @endif
                    </span>
                </div>
            </article>

            @if($hasEvidence)
                <template id="evidence-{{ str_replace('.', '-', $item['test_no']) }}">
                    <div class="evidence-dialog-heading">
                        <span>{{ $item['test_no'] }}</span>
                        <div><h2>{{ $item['scenario'] }}</h2><p>{{ $item['service'] }}</p></div>
                    </div>
                    <div class="evidence-tabs" role="tablist">
                        <button class="active" type="button" data-pane="request">Request</button>
                        <button type="button" data-pane="response">Response</button>
                    </div>
                    <pre data-pane-content="request">{{ json_encode($item['request'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    <pre data-pane-content="response" hidden>{{ json_encode($item['response'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                </template>
            @endif
        @endforeach
    </div>
</section>

<dialog class="evidence-dialog" id="evidence-dialog">
    <div class="dialog-bar"><span>Detail pengujian</span><button type="button" id="close-evidence" aria-label="Tutup">×</button></div>
    <div id="evidence-dialog-content"></div>
</dialog>
@endsection

@push('head')
<style>
    .report-header{margin-bottom:24px}.back-link{display:inline-block;margin-bottom:20px;color:var(--muted);font-size:13px;text-decoration:none}.back-link:hover{color:var(--ink)}.report-title-row{display:flex;align-items:flex-end;justify-content:space-between;gap:20px}.result-summary{display:grid;grid-template-columns:2fr repeat(3,1fr);margin-bottom:16px;border:1px solid var(--line);border-radius:10px;background:#fff}.summary-primary,.summary-stat{min-height:88px;padding:18px 20px;display:flex;flex-direction:column;justify-content:center}.summary-stat{border-left:1px solid var(--line)}.summary-primary{flex-direction:row;align-items:baseline;justify-content:flex-start;gap:10px}.summary-primary>span:last-child,.summary-stat span{color:var(--muted);font-size:12px}.summary-score{color:var(--green);font-size:30px;font-weight:750;letter-spacing:-.04em}.summary-score.has-failure{color:var(--red)}.summary-stat strong{font-size:20px}.attention-panel{margin-bottom:16px;padding:22px;border:1px solid #ead7a5;border-radius:10px;background:#fffdf7}.attention-copy h2{margin:10px 0 4px}.attention-copy p{margin:0;color:var(--muted)}.payment-workspace{display:flex;gap:20px;align-items:flex-start;margin-top:18px}.payment-workspace img{width:150px;height:150px;object-fit:contain;padding:8px;border:1px solid var(--line);border-radius:8px;background:#fff}.payment-details{flex:1}.payment-details dl{margin:0 0 18px}.payment-details dl div{display:grid;grid-template-columns:140px 1fr;padding:7px 0;border-bottom:1px solid #eee}.payment-details dt{color:var(--muted);font-size:12px}.payment-details dd{margin:0;font:600 12px ui-monospace,SFMono-Regular,Menlo,monospace;overflow-wrap:anywhere}.test-report{overflow:hidden;border:1px solid var(--line);border-radius:10px;background:#fff}.test-toolbar{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:20px;border-bottom:1px solid var(--line)}.test-toolbar p{margin:3px 0 0;color:var(--muted);font-size:12px}.result-filters{display:flex;gap:3px;padding:3px;border-radius:8px;background:var(--soft)}.result-filters button{padding:6px 9px;border:0;border-radius:6px;background:transparent;color:var(--muted);font:600 12px inherit;cursor:pointer}.result-filters button.active{background:#fff;color:var(--ink);box-shadow:0 1px 2px #17203318}.test-table-head,.test-row-main{display:grid;grid-template-columns:64px minmax(220px,2fr) minmax(130px,1fr) 92px 76px;gap:12px;align-items:center}.test-table-head{padding:9px 16px;border-bottom:1px solid var(--line);background:#fafbfc;color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}.test-row{border-bottom:1px solid #e9edf1}.test-row:last-child{border-bottom:0}.test-row-main{width:100%;padding:13px 16px;background:#fff;color:var(--ink)}.test-row:hover .test-row-main{background:#fafbfc}.case-number{font:600 12px ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--muted)}.scenario-cell strong{display:block;font-size:13px}.scenario-cell small{display:block;margin-top:3px;color:var(--muted);font-size:11px;line-height:1.35}.service-cell{color:var(--muted);font-size:12px}.row-actions{display:flex;justify-content:flex-end;gap:8px}.text-button{padding:4px 0;border:0;background:transparent;color:#334b72;font:650 12px inherit;cursor:pointer}.text-button:hover{text-decoration:underline}.no-action{color:var(--muted)}.test-row.is-hidden{display:none}.evidence-dialog{width:min(920px,calc(100% - 32px));max-height:min(760px,calc(100vh - 40px));padding:0;border:1px solid var(--line);border-radius:12px;background:#fff;box-shadow:0 24px 70px #17203340}.evidence-dialog::backdrop{background:#17203366}.dialog-bar{height:52px;padding:0 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);font-weight:700}.dialog-bar button{width:32px;height:32px;border:0;border-radius:6px;background:transparent;color:var(--muted);font-size:24px;cursor:pointer}.dialog-bar button:hover{background:var(--soft);color:var(--ink)}#evidence-dialog-content{padding:20px}.evidence-dialog-heading{display:flex;gap:12px;align-items:flex-start;margin-bottom:18px}.evidence-dialog-heading>span{padding:4px 7px;border-radius:6px;background:var(--soft);font:600 12px ui-monospace,SFMono-Regular,Menlo,monospace}.evidence-dialog-heading h2{margin:0}.evidence-dialog-heading p{margin:3px 0 0;color:var(--muted);font-size:12px}.evidence-tabs{display:flex;gap:16px;border-bottom:1px solid var(--line)}.evidence-tabs button{padding:8px 0;border:0;border-bottom:2px solid transparent;background:transparent;color:var(--muted);font:650 13px inherit;cursor:pointer}.evidence-tabs button.active{border-color:var(--ink);color:var(--ink)}.evidence-dialog pre{max-height:520px;margin:16px 0 0;overflow:auto;background:#172033;color:#e7edf5;border-radius:8px;padding:16px;font:12px/1.55 ui-monospace,SFMono-Regular,Menlo,monospace;white-space:pre-wrap;word-break:break-word}@media(max-width:760px){.report-title-row,.test-toolbar{align-items:flex-start;flex-direction:column}.result-summary{grid-template-columns:1fr 1fr}.summary-primary{grid-column:1/-1}.summary-stat{border-top:1px solid var(--line);border-left:0}.summary-stat+.summary-stat{border-left:1px solid var(--line)}.test-table-head{display:none}.test-row-main{grid-template-columns:48px minmax(0,1fr) auto}.service-cell{display:none}.row-actions{grid-column:2;justify-content:flex-start}.payment-workspace{flex-direction:column}.result-filters{max-width:100%;overflow:auto}}
</style>
@endpush

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
function toast(message, error = false){const item=document.createElement('div');item.className='toast';if(error)item.style.background='#8f1d1d';item.textContent=message;document.getElementById('toast').append(item);setTimeout(()=>item.remove(),4500)}
async function post(url){const response=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:'{}'});let data={};try{data=await response.json()}catch(_){}if(!response.ok)throw new Error(data.message||data.status||'Request gagal.');return data}
document.querySelectorAll('.run-again').forEach(button=>button.addEventListener('click',async()=>{button.disabled=true;button.textContent='Berjalan…';try{await post(button.dataset.url);location.reload()}catch(error){toast(error.message,true);button.disabled=false;button.textContent='Uji ulang'}}));

const evidenceDialog = document.getElementById('evidence-dialog');
const evidenceContent = document.getElementById('evidence-dialog-content');
document.querySelectorAll('.open-evidence').forEach(button => button.addEventListener('click', () => {
    const template = document.getElementById(`evidence-${button.dataset.case.replace('.', '-')}`);
    evidenceContent.replaceChildren(template.content.cloneNode(true));
    evidenceDialog.showModal();

    evidenceContent.querySelectorAll('.evidence-tabs button').forEach(tab => tab.addEventListener('click', () => {
        evidenceContent.querySelectorAll('.evidence-tabs button').forEach(item => item.classList.toggle('active', item === tab));
        evidenceContent.querySelectorAll('[data-pane-content]').forEach(pane => {
            pane.hidden = pane.dataset.paneContent !== tab.dataset.pane;
        });
    }));
}));
document.getElementById('close-evidence').addEventListener('click', () => evidenceDialog.close());
evidenceDialog.addEventListener('click', event => {
    if (event.target === evidenceDialog) evidenceDialog.close();
});
evidenceDialog.addEventListener('cancel', event => {
    event.preventDefault();
    evidenceDialog.close();
});

document.querySelectorAll('.result-filters button').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.result-filters button').forEach(item => item.classList.remove('active'));
    button.classList.add('active');
    const filter = button.dataset.filter;
    document.querySelectorAll('.test-row').forEach(row => {
        const visible = filter === 'all'
            || row.dataset.result === filter
            || (filter === 'action' && row.dataset.action === 'true');
        row.classList.toggle('is-hidden', !visible);
    });
}));

async function check(button){button.disabled=true;button.textContent='Memeriksa…';try{const data=await post(button.dataset.url);if(data.result.result==='PASS'){toast('Pembayaran terdeteksi.');location.reload()}else{toast('Pembayaran masih menunggu.')}return data.result}catch(error){toast(error.message,true);throw error}finally{button.disabled=false;button.textContent='Periksa pembayaran'}}
document.querySelector('.check-payment')?.addEventListener('click',event=>check(event.currentTarget));
let polling=false;document.getElementById('start-polling')?.addEventListener('click',async event=>{if(polling)return;polling=true;const button=event.currentTarget;const deadline=Date.now()+120000;button.disabled=true;while(Date.now()<deadline){try{const result=await check(button);if(result.result==='PASS')return}catch(_){break}await new Promise(resolve=>setTimeout(resolve,5000))}button.disabled=false;button.textContent='Periksa otomatis 2 menit';polling=false;toast('Pemeriksaan otomatis selesai. Pembayaran masih menunggu.')});
</script>
@endpush
