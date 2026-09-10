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

    <div class="btn-row">
        <button class="btn" type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan merchant' }}</button>
        @if(empty($onboarding))
            <a class="btn secondary" href="{{ $editing ? route('faspay-test-lab.merchants.index') : route('faspay-test-lab.index') }}">Batal</a>
        @endif
    </div>
</form>
@endsection
