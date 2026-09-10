@extends('faspay-test-lab::layouts.app')

@section('title', 'Merchant')

@section('content')
<div class="page-head">
    <div><h1>Merchant</h1></div>
    <a class="btn" href="{{ route('faspay-test-lab.merchants.create') }}">Tambah merchant</a>
</div>

<section class="panel">
@if($merchants->isEmpty())
    <div class="empty"><h2>Belum ada merchant</h2><p>Tambahkan merchant untuk memulai pengujian.</p><a class="btn" href="{{ route('faspay-test-lab.merchants.create') }}">Tambah merchant</a></div>
@else
    <table>
        <thead><tr><th>Merchant</th><th>Partner ID</th><th>Channel</th><th>Pengujian</th><th></th></tr></thead>
        <tbody>
        @foreach($merchants as $merchant)
            <tr>
                <td><strong>{{ $merchant->name }}</strong><div class="subtle">{{ $merchant->base_url }}</div></td>
                <td><span class="code">{{ $merchant->partner_id }}</span></td>
                <td>{{ $merchant->channel_id }} / {{ $merchant->qris_channel_code }}</td>
                <td>{{ $merchant->runs_count }}</td>
                <td style="text-align:right"><a class="btn secondary" href="{{ route('faspay-test-lab.merchants.edit', $merchant) }}">Ubah</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</section>
@endsection
