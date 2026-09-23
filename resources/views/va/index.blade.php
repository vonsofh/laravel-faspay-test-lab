@extends('faspay-test-lab::layouts.app')

@section('title', 'VA Sandbox')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Virtual Account</p>
        <h1>Sandbox callback lab</h1>
        <p class="lead">Buat nomor VA uji yang terisolasi, lalu gunakan Inquiry dan Payment URL ini di simulator Faspay.</p>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <div><h2>1. Siapkan transaksi uji</h2><p class="subtle">Nomor lama otomatis dinonaktifkan. Callback sandbox tidak menyentuh data pembayaran aplikasi utama.</p></div>
    </div>
    @if($merchants->isEmpty())
        <p class="subtle">Tambahkan profil merchant terlebih dahulu.</p>
        <div class="btn-row"><a class="btn" href="{{ route('faspay-test-lab.merchants.create') }}">Tambah merchant</a></div>
    @else
        <form method="post" action="{{ route('faspay-test-lab.va.accounts.store') }}">
            @csrf
            <div class="grid">
                <div class="field">
                    <label for="merchant_id">Merchant</label>
                    <select id="merchant_id" name="merchant_id" required>
                        @foreach($merchants as $merchant)
                            <option value="{{ $merchant->id }}" @selected((string) old('merchant_id', $account?->faspay_merchant_id) === (string) $merchant->id)>{{ $merchant->name }} · {{ $merchant->partner_id }}</option>
                        @endforeach
                    </select>
                    @error('merchant_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="channel_code">Bank</label>
                    <select id="channel_code" name="channel_code" required>
                        @foreach($channels as $code => $channel)
                            <option value="{{ $code }}" @selected(old('channel_code', $account?->channel_code) === (string) $code)>{{ $channel['name'] }} · {{ $channel['prefix'] }}</option>
                        @endforeach
                    </select>
                    @error('channel_code')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="display_name">Nama yang tampil</label>
                    <input id="display_name" name="display_name" maxlength="30" required value="{{ old('display_name', $account?->display_name ?? 'FASPAY SANDBOX') }}">
                    @error('display_name')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="amount">Nominal uji</label>
                    <input id="amount" name="amount" type="number" min="1000" max="999999999" step="1000" required value="{{ old('amount', $account ? intdiv($account->amount_minor, 100) : 10000) }}">
                    @error('amount')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="btn-row"><button class="btn" type="submit">{{ $account ? 'Buat nomor uji baru' : 'Buat nomor VA uji' }}</button></div>
        </form>
    @endif
</section>

@if($account)
<section class="panel">
    <div class="panel-head"><div><h2>2. Masukkan data ini di simulator Faspay</h2><p class="subtle">Akun aktif untuk {{ $account->merchant->name }}.</p></div></div>
    <table>
        <tbody>
            <tr><th>Nomor Virtual Account</th><td><strong class="code">{{ $account->virtual_account_no }}</strong></td><th>Nominal</th><td>Rp {{ number_format($account->amount_minor / 100, 0, ',', '.') }}</td></tr>
            <tr><th>Partner Service ID</th><td>{{ $account->partner_service_id }}</td><th>Customer No</th><td>{{ $account->customer_no }}</td></tr>
            <tr><th>Nama</th><td>{{ $account->display_name }}</td><th>Bank</th><td>{{ $account->channel_name }}</td></tr>
            <tr><th>Inquiry URL</th><td colspan="3"><span class="code">{{ route('faspay-test-lab.va.inquiry') }}</span></td></tr>
            <tr><th>Payment URL</th><td colspan="3"><span class="code">{{ route('faspay-test-lab.va.payment') }}</span></td></tr>
        </tbody>
    </table>
</section>
@endif

<section class="panel">
    <div class="panel-head">
        <div><h2>3. Riwayat callback</h2><p class="subtle">Semua callback QRIS, VA, dan Direct Debit tersimpan di Test Lab.</p></div>
        <a class="btn secondary" href="{{ route('faspay-test-lab.callbacks.index', ['service' => 'va_payment']) }}">Buka riwayat</a>
    </div>
</section>
@endsection
