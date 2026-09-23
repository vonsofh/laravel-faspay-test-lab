@extends('faspay-test-lab::layouts.app')

@section('title', 'Riwayat Callback')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Notification inbox</p>
        <h1>Riwayat callback</h1>
        <p class="lead">Semua request QRIS, VA, dan Direct Debit yang mencapai Laravel, termasuk request yang ditolak.</p>
    </div>
    @if($callbacks->total() > 0)
        <form method="post" action="{{ route('faspay-test-lab.callbacks.clear') }}" onsubmit="return confirm('Hapus semua riwayat callback?')">
            @csrf
            @method('DELETE')
            <button class="btn secondary" type="submit">Hapus semua</button>
        </form>
    @endif
</div>

<section class="panel">
    <form method="get" action="{{ route('faspay-test-lab.callbacks.index') }}">
        <div class="grid">
            <div class="field">
                <label for="service">Layanan</label>
                <select id="service" name="service">
                    <option value="">Semua layanan</option>
                    <option value="qris" @selected($service === 'qris')>QRIS</option>
                    <option value="va_inquiry" @selected($service === 'va_inquiry')>VA Inquiry</option>
                    <option value="va_payment" @selected($service === 'va_payment')>VA Payment</option>
                    <option value="direct_debit" @selected($service === 'direct_debit')>Direct Debit</option>
                </select>
            </div>
            <div class="field">
                <label for="status">Hasil</label>
                <select id="status" name="status">
                    <option value="">Semua hasil</option>
                    <option value="success" @selected($status === 'success')>Berhasil (2xx)</option>
                    <option value="rejected" @selected($status === 'rejected')>Ditolak (4xx)</option>
                    <option value="error" @selected($status === 'error')>Error (5xx)</option>
                </select>
            </div>
        </div>
        <div class="btn-row">
            <button class="btn" type="submit">Terapkan filter</button>
            @if($service !== '' || $status !== '')<a class="btn secondary" href="{{ route('faspay-test-lab.callbacks.index') }}">Reset</a>@endif
        </div>
    </form>
</section>

<section class="panel">
    @if($callbacks->isEmpty())
        <p class="subtle">Belum ada callback yang mencapai endpoint Test Lab.</p>
    @else
        <table>
            <thead><tr><th>Waktu</th><th>Layanan</th><th>Referensi</th><th>Signature</th><th>Respons</th><th>IP</th><th></th></tr></thead>
            <tbody>
                @foreach($callbacks as $callback)
                    @php($successful = $callback->http_status >= 200 && $callback->http_status < 300)
                    <tr>
                        <td>{{ $callback->created_at->format('d M Y, H:i:s') }}</td>
                        <td><strong>{{ match($callback->service) {'qris' => 'QRIS', 'va_inquiry' => 'VA Inquiry', 'va_payment' => 'VA Payment', 'direct_debit' => 'Direct Debit', default => $callback->service} }}</strong></td>
                        <td><span class="code">{{ $callback->reference_no ?? $callback->external_id ?? '—' }}</span></td>
                        <td><span class="status {{ $callback->signature_status === 'valid' ? 'pass' : ($callback->signature_status === 'invalid' ? 'fail' : 'waiting') }}">{{ $callback->signature_status ?? 'not_checked' }}</span></td>
                        <td><span class="status {{ $successful ? 'pass' : ($callback->http_status >= 500 ? 'fail' : 'waiting') }}">{{ $callback->http_status ?: '...' }} · {{ $callback->response_code ?? '—' }}</span></td>
                        <td>{{ $callback->client_ip ?? '—' }}</td>
                        <td style="text-align:right"><a class="btn secondary" href="{{ route('faspay-test-lab.callbacks.show', $callback) }}">Detail</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($callbacks->hasPages())
            <div class="btn-row" style="justify-content:space-between">
                <div>@if($callbacks->previousPageUrl())<a class="btn secondary" href="{{ $callbacks->previousPageUrl() }}">Sebelumnya</a>@endif</div>
                <span class="subtle">Halaman {{ $callbacks->currentPage() }} dari {{ $callbacks->lastPage() }}</span>
                <div>@if($callbacks->nextPageUrl())<a class="btn secondary" href="{{ $callbacks->nextPageUrl() }}">Berikutnya</a>@endif</div>
            </div>
        @endif
    @endif
</section>
@endsection
