@extends('faspay-test-lab::layouts.app')

@section('title', 'Detail Callback')

@section('content')
<div class="page-head">
    <div>
        <a class="subtle" href="{{ route('faspay-test-lab.callbacks.index') }}">← Kembali ke riwayat</a>
        <h1 style="margin-top:10px">Detail callback #{{ $callback->id }}</h1>
        <p class="lead">Diterima {{ $callback->created_at->format('d M Y, H:i:s') }} dari {{ $callback->client_ip ?? 'IP tidak diketahui' }}.</p>
    </div>
    <form method="post" action="{{ route('faspay-test-lab.callbacks.destroy', $callback) }}" onsubmit="return confirm('Hapus callback ini?')">
        @csrf
        @method('DELETE')
        <button class="btn secondary" type="submit">Hapus</button>
    </form>
</div>

<section class="panel">
    <div class="panel-head"><div><h2>Ringkasan</h2></div></div>
    <table>
        <tbody>
            <tr><th>Layanan</th><td>{{ $callback->service }}</td><th>HTTP</th><td><span class="code">{{ $callback->http_status ?: 'Belum selesai' }}</span></td></tr>
            <tr><th>Request</th><td>{{ $callback->request_method }} /{{ $callback->request_path }}</td><th>Response code</th><td><span class="code">{{ $callback->response_code ?? '—' }}</span></td></tr>
            <tr><th>External ID</th><td><span class="code">{{ $callback->external_id ?? '—' }}</span></td><th>Reference</th><td><span class="code">{{ $callback->reference_no ?? '—' }}</span></td></tr>
            <tr><th>Signature</th><td>{{ $callback->signature_status ?? 'not_checked' }}</td><th>Content-Type</th><td>{{ $callback->content_type ?? '—' }}</td></tr>
        </tbody>
    </table>
    @if($callback->error)<div class="notice" style="margin-top:16px;background:var(--red-soft);color:var(--red);border-color:#ffc9c9"><strong>Error:</strong> {{ $callback->error }}</div>@endif
</section>

<section class="panel">
    <div class="panel-head"><div><h2>Request headers</h2><p class="subtle">Nilai signature tidak disimpan.</p></div></div>
    <pre class="code" style="white-space:pre-wrap;word-break:break-word;margin:0">{{ json_encode($callback->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
</section>

<section class="panel">
    <div class="panel-head"><div><h2>Request body</h2></div></div>
    <pre class="code" style="white-space:pre-wrap;word-break:break-word;margin:0">{{ json_encode($callback->request_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
</section>

<section class="panel">
    <div class="panel-head"><div><h2>Response body</h2></div></div>
    <pre class="code" style="white-space:pre-wrap;word-break:break-word;margin:0">{{ json_encode($callback->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
</section>
@endsection
