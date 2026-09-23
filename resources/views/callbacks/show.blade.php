@extends('faspay-test-lab::layouts.app')

@section('title', 'Detail Callback')

@section('content')
@php
    $headers = $callback->request_headers ?? [];
    $body = $callback->request_body ?? [];
    $requiredHeaders = $callback->service === 'direct_debit'
        ? ['Content-Type']
        : ['Content-Type', 'X-TIMESTAMP', 'X-SIGNATURE', 'X-PARTNER-ID', 'X-EXTERNAL-ID', 'CHANNEL-ID'];
    $missingHeaders = collect($requiredHeaders)->filter(fn ($header) => blank($headers[$header] ?? null))->values();
    $detectedRequest = match (true) {
        $callback->service === 'qris' && isset($body['originalPartnerReferenceNo'], $body['originalReferenceNo'], $body['latestTransactionStatus']) => 'QRIS Payment Notification',
        $callback->service === 'va_inquiry' => 'Static VA Inquiry',
        $callback->service === 'va_payment' => 'Static VA Payment Notification',
        $callback->service === 'direct_debit' => 'Direct Debit Payment Notification',
        default => 'Request tidak dikenali',
    };
    $successful = $callback->http_status >= 200 && $callback->http_status < 300;
    $failed = $callback->http_status >= 500;
    $outcome = $successful ? 'Berhasil diproses' : ($failed ? 'Gagal diproses' : 'Ditolak oleh endpoint');
    $responseMessage = data_get($callback->response_body, 'responseMessage', data_get($callback->response_body, 'response_desc'));
    $partnerId = $headers['X-PARTNER-ID'] ?? data_get($body, 'additionalInfo.merchantId', data_get($body, 'merchant_id'));
    $channelCode = $headers['CHANNEL-ID'] ?? data_get($body, 'additionalInfo.channelCode', data_get($body, 'payment_channel_uid'));
@endphp
<div class="page-head">
    <div>
        <a class="subtle" href="{{ route('faspay-test-lab.callbacks.index') }}">← Kembali ke riwayat</a>
        <h1 style="margin-top:10px">{{ $detectedRequest }}</h1>
        <p class="lead">Callback #{{ $callback->id }} · {{ $callback->created_at->format('d M Y, H:i:s') }}</p>
    </div>
    <span class="outcome {{ $successful ? 'success' : ($failed ? 'error' : 'rejected') }}">{{ $callback->http_status }} · {{ $outcome }}</span>
</div>

<section class="panel diagnosis {{ $successful ? 'is-success' : ($failed ? 'is-error' : 'is-rejected') }}">
    <p class="diagnosis-label">{{ $successful ? 'Hasil' : 'Alasan ditolak' }}</p>
    <h2>{{ $responseMessage ?: ($callback->error ?: $outcome) }}</h2>
    @if($missingHeaders->isNotEmpty())
        <p>Pengirim tidak menyertakan {{ $missingHeaders->count() === 1 ? 'header wajib' : 'header wajib' }}:</p>
        <div class="missing-list">@foreach($missingHeaders as $header)<code>{{ $header }}</code>@endforeach</div>
    @elseif($callback->error)
        <p>{{ $callback->error }}</p>
    @else
        <p>{{ $successful ? 'Request diterima dan direspons sesuai protokol.' : 'Buka bukti teknis jika perlu memeriksa payload.' }}</p>
    @endif
</section>

<section class="panel facts">
    <div class="fact"><span>Merchant</span><strong>{{ $partnerId ?: '—' }}</strong></div>
    <div class="fact"><span>Channel</span><strong>{{ $channelCode ?: '—' }}</strong></div>
    <div class="fact"><span>Reference</span><strong class="code">{{ $callback->reference_no ?? '—' }}</strong></div>
    <div class="fact"><span>External ID</span><strong class="code">{{ $callback->external_id ?? '—' }}</strong></div>
    <div class="fact"><span>Signature</span><strong>{{ match($callback->signature_status) {'valid' => 'Valid', 'invalid' => 'Tidak valid', default => 'Belum diperiksa'} }}</strong></div>
    <div class="fact"><span>Sumber IP</span><strong>{{ $callback->client_ip ?? '—' }}</strong></div>
</section>

<section class="panel evidence">
    <div class="panel-head"><div><h2>Bukti teknis</h2><p class="subtle">Buka hanya saat debugging atau mengirim bukti ke Faspay.</p></div></div>
    <details>
        <summary>Request · {{ $callback->request_method }} /{{ $callback->request_path }}</summary>
        <div class="evidence-block"><h3>Headers</h3><pre>{{ json_encode($callback->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
        <div class="evidence-block"><h3>Body</h3><pre>{{ json_encode($callback->request_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
    </details>
    <details>
        <summary>Response · {{ $callback->http_status }} {{ $callback->response_code ? '· '.$callback->response_code : '' }}</summary>
        <div class="evidence-block"><pre>{{ json_encode($callback->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
    </details>
</section>

<div class="danger-zone">
    <form method="post" action="{{ route('faspay-test-lab.callbacks.destroy', $callback) }}" onsubmit="return confirm('Hapus callback ini?')">
        @csrf
        @method('DELETE')
        <button class="delete-link" type="submit">Hapus record ini</button>
    </form>
</div>
@endsection

@push('head')
<style>
.outcome{display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;font-weight:700;font-size:12px;white-space:nowrap}.outcome.success{background:var(--green-soft);color:var(--green)}.outcome.rejected{background:var(--amber-soft);color:var(--amber)}.outcome.error{background:var(--red-soft);color:var(--red)}
.diagnosis{border-left:4px solid var(--amber);padding-left:22px}.diagnosis.is-success{border-left-color:var(--green)}.diagnosis.is-error{border-left-color:var(--red)}.diagnosis-label{margin:0 0 6px;color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.09em}.diagnosis h2{font-size:20px;margin-bottom:8px}.diagnosis p:last-of-type{margin:0;color:var(--muted)}.missing-list{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}.missing-list code{padding:5px 8px;border:1px solid #ffd8a8;border-radius:6px;background:#fff9db;color:#8f5b00;font-size:12px;font-weight:700}
.facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));padding:0;overflow:hidden}.fact{min-width:0;padding:18px 20px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}.fact:nth-child(3n){border-right:0}.fact:nth-last-child(-n+3){border-bottom:0}.fact span{display:block;margin-bottom:6px;color:var(--muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.fact strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px}
.evidence details{border-top:1px solid var(--line)}.evidence details:last-child{border-bottom:1px solid var(--line)}.evidence summary{padding:14px 2px;cursor:pointer;font-weight:700;list-style:none}.evidence summary::-webkit-details-marker{display:none}.evidence summary::after{content:'+';float:right;color:var(--muted);font-size:18px}.evidence details[open] summary::after{content:'−'}.evidence-block{padding:0 0 18px}.evidence-block+.evidence-block{padding-top:4px}.evidence-block h3{margin-bottom:8px}.evidence pre{max-height:420px;overflow:auto;margin:0;padding:14px;border-radius:8px;background:#f7f8fa;color:#344054;white-space:pre-wrap;word-break:break-word;font:12px/1.6 ui-monospace,SFMono-Regular,Menlo,monospace}
.danger-zone{display:flex;justify-content:flex-end;margin-top:18px}.delete-link{border:0;background:none;color:var(--muted);font:600 12px/1 inherit;cursor:pointer}.delete-link:hover{color:var(--red)}
@media(max-width:700px){.page-head{align-items:flex-start;flex-direction:column}.facts{grid-template-columns:repeat(2,minmax(0,1fr))}.fact:nth-child(3n){border-right:1px solid var(--line)}.fact:nth-child(2n){border-right:0}.fact:nth-last-child(-n+3){border-bottom:1px solid var(--line)}.fact:nth-last-child(-n+2){border-bottom:0}}
</style>
@endpush
