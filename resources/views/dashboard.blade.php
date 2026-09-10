@extends('faspay-test-lab::layouts.app')

@section('title', 'Faspay Test Lab')

@section('content')
<div class="page-head">
    <div>
        <h1>Pengujian QRIS</h1>
        <p class="lead">Jalankan pengujian dan unduh hasilnya dalam format Excel.</p>
    </div>
    <a class="btn" href="{{ route('faspay-test-lab.qris.runs.create') }}">Mulai pengujian</a>
</div>

<section class="panel">
    <div class="panel-head">
        <div><h2>Pengujian terakhir</h2></div>
        <a class="btn secondary" href="{{ route('faspay-test-lab.runs.index') }}">Lihat semua</a>
    </div>
    @if($runs->isEmpty())
        <p class="subtle">Belum ada pengujian.</p>
    @else
        <table>
            <thead><tr><th>Merchant</th><th>Waktu</th><th>Hasil otomatis</th><th></th></tr></thead>
            <tbody>
            @foreach($runs as $run)
                @php($results = collect($run->results))
                @php($automated = $results->where('execution_type', 'automated'))
                <tr>
                    <td><strong>{{ $run->merchant?->name ?? 'Merchant dihapus' }}</strong></td>
                    <td>{{ $run->created_at->format('d M Y, H:i') }}</td>
                    <td><span class="status {{ $automated->where('result', 'FAIL')->isNotEmpty() ? 'fail' : ($run->status === 'completed' ? 'pass' : 'waiting') }}">{{ $automated->where('result', 'PASS')->count() }}/{{ $automated->count() }} berhasil</span></td>
                    <td style="text-align:right"><a class="btn secondary" href="{{ route('faspay-test-lab.runs.show', $run) }}">Buka</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection
