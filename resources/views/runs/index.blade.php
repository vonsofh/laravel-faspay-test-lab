@extends('faspay-test-lab::layouts.app')

@section('title', 'Riwayat pengujian')

@section('content')
<div class="page-head">
    <div><h1>Riwayat pengujian</h1></div>
    <a class="btn" href="{{ route('faspay-test-lab.qris.runs.create') }}">Mulai pengujian</a>
</div>

<section class="panel">
@if($runs->isEmpty())
    <div class="empty"><h2>Belum ada pengujian</h2><p>Mulai pengujian QRIS untuk membuat hasil.</p><a class="btn" href="{{ route('faspay-test-lab.qris.runs.create') }}">Mulai pengujian</a></div>
@else
    <table>
        <thead><tr><th>ID</th><th>Merchant</th><th>Waktu</th><th>Hasil otomatis</th><th></th></tr></thead>
        <tbody>
        @foreach($runs as $run)
            @php($auto = collect($run->results)->where('execution_type', 'automated'))
            <tr>
                <td><span class="code">{{ substr($run->public_id, 0, 8) }}</span></td>
                <td><strong>{{ $run->merchant?->name ?? 'Merchant dihapus' }}</strong></td>
                <td>{{ $run->created_at->format('d M Y, H:i') }}</td>
                <td><span class="status {{ $auto->where('result', 'FAIL')->isNotEmpty() ? 'fail' : ($run->status === 'completed' ? 'pass' : 'waiting') }}">{{ $auto->where('result', 'PASS')->count() }}/{{ $auto->count() }} berhasil</span></td>
                <td style="text-align:right"><a class="btn secondary" href="{{ route('faspay-test-lab.runs.show', $run) }}">Buka</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:20px">{{ $runs->links() }}</div>
@endif
</section>
@endsection
