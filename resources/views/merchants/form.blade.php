@extends('faspay-test-lab::layouts.app')

@php($editing = isset($merchant))
@section('title', $editing ? 'Edit merchant' : 'Tambah merchant')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $editing ? 'Edit merchant' : 'Tambah merchant' }}</h1>
        @if(!empty($onboarding))<p class="lead">Masukkan kredensial sandbox Faspay.</p>@endif
    </div>
</div>

<form class="panel" method="post" action="{{ $editing ? route('faspay-test-lab.merchants.update', $merchant) : route('faspay-test-lab.merchants.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="panel-head"><div><h2>Data merchant</h2></div></div>
    <div class="grid-2">
        <div class="field">
            <label for="name">Nama merchant</label>
            <input id="name" name="name" value="{{ old('name', $merchant->name ?? '') }}" required autofocus>
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="partner_id">Merchant ID / X-PARTNER-ID</label>
            <input id="partner_id" name="partner_id" value="{{ old('partner_id', $merchant->partner_id ?? '') }}" required autocomplete="off">
            @error('partner_id')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field full">
            <label for="base_url">Faspay base URL</label>
            <input id="base_url" name="base_url" type="url" value="{{ old('base_url', $merchant->base_url ?? 'https://debit-sandbox.faspay.co.id') }}" required>
            <div class="help">Gunakan URL sandbox Faspay.</div>
            @error('base_url')<div class="error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="panel-head" style="margin-top:12px"><div><h2>Kredensial SNAP</h2></div></div>
    <div class="grid-2">
        <div class="field">
            <label for="channel_id">CHANNEL-ID</label>
            <input id="channel_id" name="channel_id" value="{{ old('channel_id', $merchant->channel_id ?? '77001') }}" required>
            @error('channel_id')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="qris_channel_code">QRIS channelCode</label>
            <input id="qris_channel_code" name="qris_channel_code" value="{{ old('qris_channel_code', $merchant->qris_channel_code ?? '836') }}" required>
            @error('qris_channel_code')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field full">
            <label for="private_key">RSA private key (PEM)</label>
            <textarea id="private_key" name="private_key" {{ $editing ? '' : 'required' }} spellcheck="false" placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----">{{ old('private_key') }}</textarea>
            <div class="help">{{ $editing ? 'Kosongkan jika private key tidak diubah.' : 'Tempel private key lengkap beserta baris BEGIN dan END.' }}</div>
            @error('private_key')<div class="error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="panel-head" style="margin-top:12px"><div><h2>Target Pengujian Virtual Account (Self-Test)</h2><p class="subtle">Pengujian VA dijalankan langsung ke endpoint aplikasi Anda sendiri (bukan ke server Faspay).</p></div></div>
    <div class="grid-2">
        <div class="field full">
            <label for="va_target_url">Target App Base URL</label>
            <input id="va_target_url" name="va_target_url" value="{{ old('va_target_url', $merchant->va_target_url ?? 'http://localhost:8000') }}" placeholder="http://localhost:8000">
            <div class="help">Base URL host aplikasi yang memproses callback VA.</div>
            @error('va_target_url')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="va_inquiry_path">Inquiry Path</label>
            <input id="va_inquiry_path" name="va_inquiry_path" value="{{ old('va_inquiry_path', $merchant->va_inquiry_path ?? '/faspay/sandbox/v1.0/transfer-va/inquiry') }}" placeholder="/faspay/sandbox/v1.0/transfer-va/inquiry">
            @error('va_inquiry_path')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="va_payment_path">Payment Path</label>
            <input id="va_payment_path" name="va_payment_path" value="{{ old('va_payment_path', $merchant->va_payment_path ?? '/faspay/sandbox/v1.0/transfer-va/payment') }}" placeholder="/faspay/sandbox/v1.0/transfer-va/payment">
            @error('va_payment_path')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="va_partner_service_id">Partner Service ID (Prefix)</label>
            <input id="va_partner_service_id" name="va_partner_service_id" value="{{ old('va_partner_service_id', $merchant->va_partner_service_id ?? '370731') }}" placeholder="370731">
            @error('va_partner_service_id')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="va_test_customer_no">Nomor VA Uji (Customer No)</label>
            <input id="va_test_customer_no" name="va_test_customer_no" value="{{ old('va_test_customer_no', $merchant->va_test_customer_no ?? '2330370475') }}" placeholder="2330370475">
            @error('va_test_customer_no')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="va_paid_customer_no">Nomor VA Sudah Lunas (Opsional)</label>
            <input id="va_paid_customer_no" name="va_paid_customer_no" value="{{ old('va_paid_customer_no', $merchant->va_paid_customer_no ?? '') }}" placeholder="Contoh: 2330370001">
            <div class="help">Untuk skenario 11.7 (Bill has been paid).</div>
            @error('va_paid_customer_no')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="va_expired_customer_no">Nomor VA Kadaluarsa (Opsional)</label>
            <input id="va_expired_customer_no" name="va_expired_customer_no" value="{{ old('va_expired_customer_no', $merchant->va_expired_customer_no ?? '') }}" placeholder="Contoh: 2330370002">
            <div class="help">Untuk skenario 11.8 (Bill expired).</div>
            @error('va_expired_customer_no')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field full">
            <label for="va_test_amount">Nominal Uji (Closed Amount IDR)</label>
            <input id="va_test_amount" name="va_test_amount" value="{{ old('va_test_amount', $merchant->va_test_amount ?? '10000.00') }}" placeholder="10000.00">
            @error('va_test_amount')<div class="error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="btn-row">
        <button class="btn" type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan merchant' }}</button>
        @if(empty($onboarding))
            <a class="btn secondary" href="{{ $editing ? route('faspay-test-lab.merchants.index') : route('faspay-test-lab.index') }}">Batal</a>
        @endif
    </div>
</form>
@endsection
