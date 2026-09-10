@extends('faspay-test-lab::layouts.app')

@section('title', 'Pengujian #'.$run->id)

@section('content')
@php($results = collect($run->results))
@php($automated = $results->where('execution_type', 'automated'))
@php($payment = $results->firstWhere('test_no', '18.12'))
@php($generated = $results->firstWhere('test_no', '18.6'))

<div class="page-head">
    <div>
        <p class="eyebrow">Pengujian QRIS #{{ $run->id }}</p>
        <h1>{{ $run->merchant?->name ?? 'Merchant dihapus' }}</h1>
        <p class="lead">{{ $run->created_at->format('d M Y, H:i') }} · {{ $automated->where('result', 'PASS')->count() }}/{{ $automated->count() }} skenario otomatis berhasil</p>
    </div>
    <div class="btn-row">
        <a class="btn" href="{{ route('faspay-test-lab.runs.export', $run) }}">Unduh Excel</a>
        <a class="btn secondary" href="{{ route('faspay-test-lab.runs.index') }}">Kembali</a>
    </div>
</div>

@if(in_array($payment['result'] ?? null, ['WAITING', 'MANUAL'], true))
<section class="panel" style="margin-bottom:16px">
    <div class="panel-head"><div><h2>Verifikasi pembayaran</h2><p class="subtle">Bayar QR dari skenario 18.6 di simulator Faspay, lalu periksa skenario 18.12.</p></div><span class="status waiting">Menunggu</span></div>
    @if(filled(data_get($generated, 'metadata.referenceNo')))
        <div class="meta-line" style="margin-bottom:16px"><span>Reference: <span class="code">{{ data_get($generated, 'metadata.referenceNo') }}</span></span><span>Partner reference: <span class="code">{{ data_get($generated, 'metadata.partnerReferenceNo') }}</span></span></div>
        @php($qrImage = data_get($generated, 'metadata.qrUrl') ?: data_get($generated, 'metadata.qrImageUrl'))
        @if($qrImage)<img src="{{ $qrImage }}" alt="Generated QRIS" style="max-width:180px;border:1px solid var(--line);border-radius:8px;padding:8px;margin-bottom:16px">@endif
        <div class="btn-row"><button class="btn check-payment" type="button" data-url="{{ route('faspay-test-lab.runs.cases.payment.check', $run) }}">Periksa pembayaran</button><button class="btn secondary" type="button" id="start-polling" data-url="{{ route('faspay-test-lab.runs.cases.payment.check', $run) }}">Periksa otomatis 2 menit</button></div>
    @else
        <p class="subtle">Skenario 18.6 belum menghasilkan transaksi QR.</p>
        <button class="btn run-again" type="button" data-url="{{ route('faspay-test-lab.runs.cases.execute', [$run, '18.6']) }}">Jalankan 18.6</button>
    @endif
</section>
@endif

<section class="panel">
    <div class="panel-head"><div><h2>Hasil skenario</h2><p class="subtle">Rincian request dan response untuk 25 skenario sertifikasi.</p></div></div>
    @foreach($run->results as $item)
        @php($class = match($item['result']) {'PASS'=>'pass','FAIL'=>'fail','WAITING'=>'waiting',default=>''})
        <article class="result-card">
            <div class="result-top">
                <div><span class="code">{{ $item['test_no'] }}</span><h3 style="display:inline;margin-left:10px">{{ $item['scenario'] }}</h3><div class="subtle">{{ $item['service'] }}</div></div>
                <span class="status {{ $class }}">{{ $item['result'] }}</span>
            </div>
            @if(!empty($item['notes']))<p class="subtle">{{ $item['notes'] }}</p>@endif
            @if($item['execution_type'] === 'automated' && in_array($item['result'], ['FAIL', 'NOT RUN'], true))
                <button class="btn secondary run-again" type="button" style="margin-top:12px" data-url="{{ route('faspay-test-lab.runs.cases.execute', [$run, $item['test_no']]) }}">Uji ulang</button>
            @endif
            @if(is_array($item['request']) && !in_array($item['result'], ['N/A', 'MANUAL', 'NOT RUN'], true))
                <details class="evidence" style="margin-top:14px"><summary>Request</summary><pre>{{ json_encode($item['request'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details>
                <details class="evidence"><summary>Response</summary><pre>{{ json_encode($item['response'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details>
            @endif
        </article>
    @endforeach
</section>
@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
function toast(message, error = false){const item=document.createElement('div');item.className='toast';if(error)item.style.background='#8f1d1d';item.textContent=message;document.getElementById('toast').append(item);setTimeout(()=>item.remove(),4500)}
async function post(url){const response=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:'{}'});let data={};try{data=await response.json()}catch(_){}if(!response.ok)throw new Error(data.message||data.status||'Request gagal.');return data}
document.querySelectorAll('.run-again').forEach(button=>button.addEventListener('click',async()=>{button.disabled=true;button.textContent='Berjalan…';try{await post(button.dataset.url);location.reload()}catch(error){toast(error.message,true);button.disabled=false;button.textContent='Uji ulang'}}));
async function check(button){button.disabled=true;button.textContent='Memeriksa…';try{const data=await post(button.dataset.url);if(data.result.result==='PASS'){toast('Pembayaran terdeteksi.');location.reload()}else{toast('Pembayaran masih menunggu.')}return data.result}catch(error){toast(error.message,true);throw error}finally{button.disabled=false;button.textContent='Periksa pembayaran'}}
document.querySelector('.check-payment')?.addEventListener('click',event=>check(event.currentTarget));
let polling=false;document.getElementById('start-polling')?.addEventListener('click',async event=>{if(polling)return;polling=true;const button=event.currentTarget;const deadline=Date.now()+120000;button.disabled=true;while(Date.now()<deadline){try{const result=await check(button);if(result.result==='PASS')return}catch(_){break}await new Promise(resolve=>setTimeout(resolve,5000))}button.disabled=false;button.textContent='Periksa otomatis 2 menit';polling=false;toast('Pemeriksaan otomatis selesai. Pembayaran masih menunggu.')});
</script>
@endpush
