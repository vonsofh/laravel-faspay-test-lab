<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faspay SNAP Test Lab</title>
    <style>
        :root {
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            --bg-page: #f8fafc;
            --bg-surface: #ffffff;
            --border-subtle: #e2e8f0;
            --border-strong: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-subtle: #94a3b8;
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --success: #059669;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --warning-border: #fde68a;
            --info: #2563eb;
            --info-bg: #eff6ff;
            --info-border: #bfdbfe;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --shadow-card: 0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05);
            --shadow-elevated: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.05);
        }

        * { box-sizing: border-box; }
        body {
            font-family: var(--font-sans);
            background-color: var(--bg-page);
            color: var(--text-main);
            margin: 0;
            padding: 24px 20px 60px;
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        .container { max-width: 1160px; margin: 0 auto; }

        /* App Header */
        .app-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .app-title-group h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .app-title-group p {
            color: var(--text-muted);
            margin: 0;
            font-size: 14px;
        }
        .badge-version {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-subtle);
        }
        .env-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid var(--border-strong);
            color: var(--text-main);
        }
        .env-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
        }

        /* Workflow Steps */
        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 28px;
        }
        @media (max-width: 768px) {
            .workflow-steps { grid-template-columns: 1fr; }
            .app-header { flex-direction: column; }
        }
        .step-item {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: var(--shadow-card);
            position: relative;
        }
        .step-item.active {
            border-color: var(--primary);
            background: #ffffff;
        }
        .step-item.completed {
            border-color: var(--success-border);
            background: #ffffff;
        }
        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #f1f5f9;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .step-item.active .step-number {
            background: var(--primary);
            color: #ffffff;
        }
        .step-item.completed .step-number {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid var(--success-border);
        }
        .step-content h3 {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 2px;
            color: var(--text-main);
        }
        .step-content p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.4;
        }

        /* Section Cards */
        .card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-card);
            margin-bottom: 24px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .card-title-group h2 {
            font-size: 16px;
            font-weight: 600;
            letter-spacing: -0.01em;
            margin: 0 0 4px;
        }
        .card-title-group p {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: var(--radius-sm);
            padding: 9px 16px;
            background: var(--primary);
            color: #ffffff;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .btn:hover { background: var(--primary-hover); }
        .btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .btn.secondary {
            background: #ffffff;
            color: var(--text-main);
            border-color: var(--border-strong);
        }
        .btn.secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }
        .btn.success {
            background: var(--success);
            color: #ffffff;
        }
        .btn.success:hover { background: #047857; }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: var(--radius-sm);
        }

        /* Module Selector Grid */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 8px;
        }
        @media (max-width: 900px) {
            .module-grid { grid-template-columns: 1fr; }
        }
        .module-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: var(--shadow-card);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .module-card.active {
            border-color: #0f172a;
            box-shadow: 0 0 0 1px #0f172a, var(--shadow-card);
        }
        .module-card.disabled {
            background: #fafbfc;
            border-style: dashed;
        }
        .module-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
            gap: 8px;
        }
        .module-title {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 4px;
            color: var(--text-main);
        }
        .module-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0 0 16px;
            line-height: 1.45;
        }
        .module-meta {
            font-size: 12px;
            color: var(--text-subtle);
            border-top: 1px solid var(--border-subtle);
            padding-top: 12px;
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Status Badges */
        .pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid transparent;
        }
        .pill-badge.active-ready {
            background: var(--success-bg);
            color: var(--success);
            border-color: var(--success-border);
        }
        .pill-badge.upcoming {
            background: #f1f5f9;
            color: var(--text-muted);
            border-color: var(--border-subtle);
        }
        .pill-badge.pass {
            background: var(--success-bg);
            color: var(--success);
            border-color: var(--success-border);
        }
        .pill-badge.fail {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: var(--danger-border);
        }
        .pill-badge.running {
            background: var(--info-bg);
            color: var(--info);
            border-color: var(--info-border);
        }
        .pill-badge.waiting {
            background: var(--warning-bg);
            color: var(--warning);
            border-color: var(--warning-border);
        }
        .pill-badge.not-run {
            background: #f1f5f9;
            color: var(--text-muted);
            border-color: var(--border-subtle);
        }
        .pill-badge.manual {
            background: #f8fafc;
            color: #475569;
            border-color: var(--border-strong);
        }
        .pill-badge.na {
            background: #f1f5f9;
            color: #94a3b8;
            border-color: var(--border-subtle);
        }

        /* Spinner */
        .spin {
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* Merchant Active Bar */
        .merchant-active-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .merchant-info-block {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .merchant-specs-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: var(--text-muted);
            font-family: var(--font-mono);
        }
        .merchant-specs-row span strong {
            color: var(--text-main);
            font-family: var(--font-sans);
        }

        /* Form Drawer */
        .merchant-drawer {
            display: none;
            background: #f8fafc;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 20px;
        }
        .merchant-drawer.open {
            display: block;
            animation: fadeIn 0.2s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .form-full { grid-column: 1 / -1; }
        .field { margin: 0 0 14px; }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .field .help {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .field input, .field textarea, .field select {
            width: 100%;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-size: 13px;
            color: var(--text-main);
            background: #ffffff;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .field input:focus, .field textarea:focus, .field select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
        }
        .field textarea {
            min-height: 110px;
            font-family: var(--font-mono);
            font-size: 12px;
            line-height: 1.4;
        }

        /* Filter Tabs */
        .filter-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-subtle);
            padding-bottom: 10px;
            overflow-x: auto;
        }
        .filter-btn {
            background: none;
            border: none;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-btn:hover { color: var(--text-main); background: #f1f5f9; }
        .filter-btn.active {
            color: var(--text-main);
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            font-weight: 600;
        }
        .filter-count {
            display: inline-block;
            margin-left: 4px;
            font-size: 11px;
            padding: 1px 6px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #475569;
        }

        /* Cases Table */
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-subtle);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-subtle);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbfc; }
        .case-code {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .case-scenario {
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 3px;
        }
        .case-sub {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Progress Panel */
        .progress-box {
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            margin-bottom: 20px;
        }
        .progress-bar-bg {
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.25s ease;
        }
        .progress-status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 380px;
            width: 100%;
        }
        .toast {
            background: #0f172a;
            color: #ffffff;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: 13px;
            box-shadow: var(--shadow-elevated);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            animation: slideIn 0.2s ease-out;
        }
        .toast.success { background: #065f46; }
        .toast.error { background: #991b1b; }
        .toast.warning { background: #92400e; }
        .toast-close {
            background: none;
            border: none;
            color: currentColor;
            cursor: pointer;
            opacity: 0.8;
            padding: 0;
            display: flex;
            align-items: center;
        }
        .toast-close:hover { opacity: 1; }
        @keyframes slideIn {
            from { transform: translateY(12px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .hidden { display: none !important; }
    </style>
</head>
<body>

<div class="container">
    <!-- Top Header -->
    <header class="app-header">
        <div class="app-title-group">
            <h1>
                Faspay SNAP Test Lab
                <span class="badge-version">v1.0.0</span>
            </h1>
            <p>Automated certification test runner & official Excel evidence exporter.</p>
        </div>
        <div>
            <span class="env-badge">
                <span class="env-dot"></span>
                <span>Faspay Sandbox Ready</span>
            </span>
        </div>
    </header>

    @if(session('status'))
        <div class="card" style="padding:14px 18px; margin-bottom:20px; background:#f0fdf4; border-color:#bbf7d0; color:#166534; font-size:14px; font-weight:500;">
            {{ session('status') }}
        </div>
    @endif

    <!-- 3-Step Guided Workflow Bar -->
    <div class="workflow-steps">
        <div class="step-item {{ $merchants->isNotEmpty() ? 'completed' : 'active' }}" id="step-nav-1">
            <div class="step-number">
                @if($merchants->isNotEmpty())
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                @else
                    1
                @endif
            </div>
            <div class="step-content">
                <h3>1. Merchant & Kredensial</h3>
                <p>{{ $merchants->isNotEmpty() ? ($merchants->count().' merchant terdaftar') : 'Input kredensial sandbox' }}</p>
            </div>
        </div>

        <div class="step-item active" id="step-nav-2">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3>2. Pilih Modul UAT</h3>
                <p>QRIS QR MPM (Aktif · Siap Uji)</p>
            </div>
        </div>

        <div class="step-item" id="step-nav-3">
            <div class="step-number">3</div>
            <div class="step-content">
                <h3>3. Eksekusi & Bukti Excel</h3>
                <p>Jalankan runner & unduh format resmi</p>
            </div>
        </div>
    </div>

    <!-- Step 1: Merchant Management Section -->
    <section class="card" id="merchant-section">
        <div class="card-header">
            <div class="card-title-group">
                <h2>Profil Merchant Sandbox</h2>
                <p>Kredensial API dan Private Key RSA yang digunakan untuk signing SNAP X-SIGNATURE.</p>
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" class="btn secondary btn-sm" id="btn-toggle-add-merchant">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Tambah Merchant</span>
                </button>
            </div>
        </div>

        <!-- If merchants exist: show active bar + selector -->
        @if($merchants->isNotEmpty())
            <div class="merchant-active-bar" id="merchant-bar">
                <div class="merchant-info-block">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <label for="merchant-select" style="font-size:12px; font-weight:600; color:var(--text-muted);">Pilih Merchant:</label>
                        <select id="merchant-select" style="padding:6px 12px; border-radius:var(--radius-sm); border:1px solid var(--border-strong); font-size:13px; font-weight:600; background:#fff;">
                            @foreach($merchants as $merchant)
                                <option value="{{ $merchant->id }}"
                                    data-name="{{ $merchant->name }}"
                                    data-base-url="{{ $merchant->base_url }}"
                                    data-partner-id="{{ $merchant->partner_id }}"
                                    data-channel-id="{{ $merchant->channel_id }}"
                                    data-qris-channel-code="{{ $merchant->qris_channel_code }}">
                                    {{ $merchant->name }} ({{ $merchant->partner_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="merchant-specs-row">
                        <span>Partner ID: <strong id="bar-partner-id">-</strong></span>
                        <span>Channel: <strong id="bar-channel-id">-</strong></span>
                        <span>QRIS Code: <strong id="bar-qris-code">-</strong></span>
                        <span class="pill-badge active-ready" id="bar-env-badge">Sandbox</span>
                    </div>
                </div>

                <div>
                    <button type="button" class="btn secondary btn-sm" id="btn-edit-active-merchant">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        <span>Edit Kredensial</span>
                    </button>
                </div>
            </div>
        @else
            <!-- Empty state banner when no merchant exists -->
            <div style="background:#f8fafc; border:1px dashed var(--border-strong); border-radius:var(--radius-md); padding:20px; margin-bottom:20px; text-align:center;">
                <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:var(--text-main);">Belum ada merchant sandbox terdaftar</p>
                <p style="margin:0 0 16px; font-size:13px; color:var(--text-muted); max-width:540px; margin-left:auto; margin-right:auto;">
                    Untuk memulai pengujian sertifikasi Faspay SNAP, daftarkan merchant sandbox Anda beserta Private Key RSA di bawah ini.
                </p>
            </div>
        @endif

        <!-- Merchant Form Drawer (Create / Edit) -->
        <div class="merchant-drawer {{ $merchants->isEmpty() ? 'open' : '' }}" id="merchant-drawer">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h3 id="merchant-form-heading" style="margin:0; font-size:15px; font-weight:600;">Tambah Merchant Baru</h3>
                @if($merchants->isNotEmpty())
                    <button type="button" class="btn secondary btn-sm" id="btn-close-drawer">Tutup</button>
                @endif
            </div>

            <form id="merchant-form" method="post" action="{{ route('faspay-test-lab.merchants.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="field">
                        <label for="field-name">Nama Merchant</label>
                        <input id="field-name" name="name" required placeholder="Contoh: PT Toko Berkah Sejahtera">
                    </div>

                    <div class="field">
                        <label for="field-base-url">Base URL Faspay Sandbox</label>
                        <input id="field-base-url" name="base_url" value="https://debit-sandbox.faspay.co.id" required>
                        <div class="help">Default sandbox: https://debit-sandbox.faspay.co.id</div>
                    </div>

                    <div class="field">
                        <label for="field-partner-id">Merchant ID / X-PARTNER-ID</label>
                        <input id="field-partner-id" name="partner_id" required placeholder="Contoh: 1000000000000000">
                    </div>

                    <div class="field">
                        <label for="field-channel-id">CHANNEL-ID</label>
                        <input id="field-channel-id" name="channel_id" value="77001" required>
                        <div class="help">Default SNAP channel ID: 77001</div>
                    </div>

                    <div class="field form-full">
                        <label for="field-qris-code">QRIS channelCode</label>
                        <input id="field-qris-code" name="qris_channel_code" value="836" required style="max-width:320px;">
                        <div class="help">Kode channel QRIS Faspay (default: 836)</div>
                    </div>

                    <div class="field form-full">
                        <label for="field-private-key">Private Key PEM (RSA 2048)</label>
                        <textarea id="field-private-key" name="private_key" placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----" required></textarea>
                        <div class="help">Digunakan untuk menghasilkan header X-SIGNATURE (SHA256withRSA) sesuai standar SNAP Open API. Disimpan aman pada database lokal.</div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                    <button class="btn" type="submit" id="merchant-submit-btn">Simpan Merchant</button>
                    <button class="btn secondary hidden" type="button" id="merchant-cancel-edit-btn">Batal</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Step 2: Module Selection (Clarifying QRIS vs VA vs Direct Debit) -->
    <section class="card">
        <div class="card-header">
            <div class="card-title-group">
                <h2>Cakupan Modul Sertifikasi Faspay SNAP</h2>
                <p>Status dukungan modul sertifikasi saat ini. Modul QRIS saat ini aktif penuh.</p>
            </div>
        </div>

        <div class="module-grid">
            <!-- Active Module: QRIS -->
            <div class="module-card active">
                <div class="module-top">
                    <div>
                        <h3 class="module-title">QRIS (QR MPM)</h3>
                        <p class="module-desc">Pengujian otomatis SNAP QRIS skenario 18.1 – 18.25. Mendukung interactive QR generator, query payment status simulator, dan export template Excel resmi Faspay V3.2.</p>
                    </div>
                    <span class="pill-badge active-ready">Aktif & Siap Uji</span>
                </div>
                <div class="module-meta">
                    <span>25 Skenario UAT (V3.2)</span>
                    <strong style="color:var(--success);">Tersedia Sekarang</strong>
                </div>
            </div>

            <!-- Virtual Account (VA) -->
            <div class="module-card disabled">
                <div class="module-top">
                    <div>
                        <h3 class="module-title">Virtual Account (Transfer VA)</h3>
                        <p class="module-desc">Pengujian SNAP Virtual Account untuk skenario inquiry, payment, dan status VA. Template resmi Faspay V3.0 sudah di-bundle dalam package.</p>
                    </div>
                    <span class="pill-badge upcoming">Runner Segera Hadir</span>
                </div>
                <div class="module-meta">
                    <span>Template V3.0 Static Ready</span>
                    <span>Tahap Pengembangan</span>
                </div>
            </div>

            <!-- Direct Debit -->
            <div class="module-card disabled">
                <div class="module-top">
                    <div>
                        <h3 class="module-title">Direct Debit</h3>
                        <p class="module-desc">Pengujian SNAP Direct Debit untuk registrasi akun dan debit transaksi instan. Template resmi Faspay V3.2 sudah di-bundle dalam package.</p>
                    </div>
                    <span class="pill-badge upcoming">Runner Segera Hadir</span>
                </div>
                <div class="module-meta">
                    <span>Template V3.2 Ready</span>
                    <span>Tahap Pengembangan</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Step 3: QRIS Test Runner Section -->
    <section class="card" id="runner-section">
        <div class="card-header">
            <div class="card-title-group">
                <h2>QRIS Functional Test Runner</h2>
                <p>Eksekusi skenario uji otomatis, verifikasi respons, dan generate berkas bukti sertifikasi.</p>
            </div>

            <form id="run-form" method="post" action="{{ route('faspay-test-lab.runs.store') }}">
                @csrf
                <input type="hidden" name="merchant_id" id="hidden-run-merchant-id">
                <button class="btn success" id="run-all-btn" type="submit" {{ $merchants->isEmpty() ? 'disabled' : '' }}>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    <span>Jalankan Semua Tes Otomatis (8 Kasus)</span>
                </button>
            </form>
        </div>

        <!-- Live Progress Box (Hidden until running) -->
        <div id="progress-panel" class="progress-box hidden">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:600; font-size:14px;" id="progress-heading">Memulai Eksekusi Pengujian...</div>
                <div class="pill-badge running" id="progress-badge">
                    <svg class="spin" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10"></path></svg>
                    <span>Memproses</span>
                </div>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progress-bar"></div>
            </div>
            <div class="progress-status-row">
                <span id="progress-copy">Menyiapkan request...</span>
                <span id="progress-counter" style="font-weight:600; font-family:var(--font-mono); color:var(--text-main);">0 / 8 Selesai</span>
            </div>

            <div id="progress-actions" class="hidden" style="margin-top:14px; display:flex; gap:10px; align-items:center;">
                <a id="btn-export-results" class="btn success btn-sm" href="#">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>Unduh Laporan Excel Resmi (.xlsx)</span>
                </a>
                <a id="btn-view-results" class="btn secondary btn-sm" href="#">
                    <span>Buka Rincian & Bukti Respons</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-nav">
            <button type="button" class="filter-btn active" data-filter="all">Semua Skenario <span class="filter-count">{{ count($cases) }}</span></button>
            <button type="button" class="filter-btn" data-filter="automated">Otomatis <span class="filter-count">{{ collect($cases)->where('execution_type', 'automated')->count() }}</span></button>
            <button type="button" class="filter-btn" data-filter="requires_payment">Simulasi Bayar <span class="filter-count">1</span></button>
            <button type="button" class="filter-btn" data-filter="manual">Callback <span class="filter-count">1</span></button>
            <button type="button" class="filter-btn" data-filter="not_applicable">N/A (Auto-filled) <span class="filter-count">{{ collect($cases)->where('execution_type', 'not_applicable')->count() }}</span></button>
        </div>

        <!-- Cases Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:70px;">Kasus</th>
                        <th>Skenario Pengujian</th>
                        <th style="width:140px;">Tipe</th>
                        <th style="width:140px;">Status</th>
                        <th style="width:90px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($cases as $case)
                    @php($type = $case['execution_type'])
                    <tr data-case="{{ $case['no'] }}" data-type="{{ $type }}">
                        <td class="case-code">{{ $case['no'] }}</td>
                        <td>
                            <div class="case-scenario">{{ $case['scenario'] }}</div>
                            <div class="case-sub">
                                Service: {{ $case['service'] }}
                                @if(!empty($case['expected_code']))
                                    · Expected: <code>{{ $case['expected_code'] }}</code> {{ $case['expected_message'] ?? '' }}
                                @endif
                                @if(!empty($case['notes']) && $type !== 'automated')
                                    · <span style="color:var(--text-subtle);">{{ $case['notes'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($type === 'automated')
                                <span class="pill-badge not-run">Automated</span>
                            @elseif($type === 'requires_payment')
                                <span class="pill-badge waiting">Perlu Pembayaran</span>
                            @elseif($type === 'manual')
                                <span class="pill-badge manual">Callback Manual</span>
                            @else
                                <span class="pill-badge na">Not Applicable</span>
                            @endif
                        </td>
                        <td>
                            <span class="pill-badge case-status {{ $type === 'automated' ? 'not-run' : ($type === 'not_applicable' ? 'na' : ($type === 'manual' ? 'manual' : 'waiting')) }}">
                                {{ $type === 'automated' ? 'NOT RUN' : ($type === 'not_applicable' ? 'N/A' : ($type === 'manual' ? 'MANUAL' : 'WAITING')) }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            @if($type === 'automated')
                                <button class="btn secondary btn-sm run-case" type="button" data-case="{{ $case['no'] }}" {{ $merchants->isEmpty() ? 'disabled' : '' }}>
                                    Uji
                                </button>
                            @else
                                <span style="font-size:12px; color:var(--text-subtle);">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <!-- Recent Runs History -->
    <section class="card">
        <div class="card-header">
            <div class="card-title-group">
                <h2>Riwayat Eksekusi UAT Terakhir</h2>
                <p>Daftar log pengujian sebelumnya yang dapat ditinjau atau diunduh kembali berkas bukti Excel-nya.</p>
            </div>
        </div>

        @if($runs->isNotEmpty())
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu Eksekusi</th>
                            <th>Merchant</th>
                            <th>Modul</th>
                            <th>Hasil Pengujian</th>
                            <th style="text-align:right;">Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($runs as $run)
                        @php($results = collect($run->results))
                        @php($passCount = $results->where('execution_type', 'automated')->where('result', 'PASS')->count())
                        @php($autoTotal = $results->where('execution_type', 'automated')->count())
                        <tr>
                            <td style="font-family:var(--font-mono); font-size:12px; color:var(--text-muted);">
                                {{ $run->created_at->format('d M Y, H:i') }}
                            </td>
                            <td>
                                <strong>{{ $run->merchant?->name ?? 'Merchant Dihapus' }}</strong>
                                <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono);">
                                    {{ $run->merchant?->partner_id }}
                                </div>
                            </td>
                            <td>
                                <span class="pill-badge" style="background:#f1f5f9; color:var(--text-main); font-weight:600;">
                                    {{ strtoupper($run->service) }}
                                </span>
                            </td>
                            <td>
                                <span class="pill-badge {{ $passCount === $autoTotal ? 'pass' : 'waiting' }}">
                                    {{ $passCount }} / {{ $autoTotal }} PASS
                                </span>
                                <span style="font-size:12px; color:var(--text-muted); margin-left:6px;">
                                    (15 N/A auto-filled)
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a class="btn secondary btn-sm" href="{{ route('faspay-test-lab.runs.show', $run) }}">
                                        Buka Evidence
                                    </a>
                                    <a class="btn secondary btn-sm" href="{{ route('faspay-test-lab.runs.export', $run) }}" title="Download Excel">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px;">
                Belum ada riwayat pengujian. Daftarkan merchant dan klik "Jalankan Semua Tes Otomatis" untuk memulai.
            </div>
        @endif
    </section>
</div>

<!-- Toast Container -->
<div id="toast-container" class="toast-container" aria-live="polite"></div>

<script>
const routePrefix = '{{ config('faspay-test-lab.route_prefix', 'faspay-test-lab') }}';
const form = document.getElementById('run-form');
const merchantSelect = document.getElementById('merchant-select');
const hiddenRunMerchantId = document.getElementById('hidden-run-merchant-id');
const automatedCases = @json(collect($cases)->where('execution_type', 'automated')->pluck('no')->values());
const csrf = document.querySelector('input[name="_token"]').value;
let activeRun = null;

// Toast Utility
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const text = document.createElement('span');
    text.textContent = message;

    const close = document.createElement('button');
    close.className = 'toast-close';
    close.type = 'button';
    close.setAttribute('aria-label', 'Tutup');
    close.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
    close.addEventListener('click', () => toast.remove());

    toast.append(text, close);
    document.getElementById('toast-container').append(toast);
    setTimeout(() => toast.remove(), 4500);
}

async function responseData(response) {
    let data = {};
    try { data = await response.json(); } catch (_) {}
    if (!response.ok) {
        const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(validation || data.message || 'Tidak dapat terhubung ke server.');
    }
    return data;
}

function selectedMerchant() {
    if (!merchantSelect || !merchantSelect.selectedOptions.length) return null;
    const option = merchantSelect.selectedOptions[0];
    return {
        id: option.value,
        name: option.dataset.name,
        base_url: option.dataset.baseUrl,
        partner_id: option.dataset.partnerId,
        channel_id: option.dataset.channelId,
        qris_channel_code: option.dataset.qrisChannelCode,
    };
}

function syncMerchantSelection() {
    const merchant = selectedMerchant();
    const enabled = Boolean(merchant && merchant.id);

    if (hiddenRunMerchantId && merchant) {
        hiddenRunMerchantId.value = merchant.id;
    }

    const runAllBtn = document.getElementById('run-all-btn');
    if (runAllBtn) runAllBtn.disabled = !enabled;

    document.querySelectorAll('.run-case').forEach(button => button.disabled = !enabled);

    if (!enabled) return;

    const barPartnerId = document.getElementById('bar-partner-id');
    const barChannelId = document.getElementById('bar-channel-id');
    const barQrisCode = document.getElementById('bar-qris-code');
    const barEnvBadge = document.getElementById('bar-env-badge');

    if (barPartnerId) barPartnerId.textContent = merchant.partner_id;
    if (barChannelId) barChannelId.textContent = merchant.channel_id;
    if (barQrisCode) barQrisCode.textContent = merchant.qris_channel_code;
    if (barEnvBadge) {
        const isSandbox = merchant.base_url.includes('sandbox');
        barEnvBadge.textContent = isSandbox ? 'Sandbox' : 'Production';
    }
}

if (merchantSelect) {
    merchantSelect.addEventListener('change', syncMerchantSelection);
    syncMerchantSelection();
}

// Drawer Toggle Logic
const drawer = document.getElementById('merchant-drawer');
const merchantForm = document.getElementById('merchant-form');
const btnToggleAdd = document.getElementById('btn-toggle-add-merchant');
const btnEditActive = document.getElementById('btn-edit-active-merchant');
const btnCloseDrawer = document.getElementById('btn-close-drawer');
const cancelEditBtn = document.getElementById('merchant-cancel-edit-btn');
const formHeading = document.getElementById('merchant-form-heading');
const submitBtn = document.getElementById('merchant-submit-btn');

if (btnToggleAdd) {
    btnToggleAdd.addEventListener('click', () => {
        merchantForm.reset();
        delete merchantForm.dataset.editId;
        merchantForm.action = '{{ route('faspay-test-lab.merchants.store') }}';
        merchantForm.querySelector('input[name="_method"]')?.remove();
        formHeading.textContent = 'Tambah Merchant Baru';
        submitBtn.textContent = 'Simpan Merchant';
        cancelEditBtn.classList.add('hidden');
        drawer.classList.toggle('open');
        if (drawer.classList.contains('open')) {
            drawer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
}

if (btnCloseDrawer) {
    btnCloseDrawer.addEventListener('click', () => {
        drawer.classList.remove('open');
    });
}

if (btnEditActive) {
    btnEditActive.addEventListener('click', () => {
        const merchant = selectedMerchant();
        if (!merchant) return;
        merchantForm.dataset.editId = merchant.id;
        formHeading.textContent = 'Edit Kredensial: ' + merchant.name;
        submitBtn.textContent = 'Perbarui Kredensial';
        cancelEditBtn.classList.remove('hidden');

        document.getElementById('field-name').value = merchant.name;
        document.getElementById('field-base-url').value = merchant.base_url;
        document.getElementById('field-partner-id').value = merchant.partner_id;
        document.getElementById('field-channel-id').value = merchant.channel_id;
        document.getElementById('field-qris-code').value = merchant.qris_channel_code;

        const privKey = document.getElementById('field-private-key');
        privKey.value = '';
        privKey.required = false;
        privKey.placeholder = 'Biarkan kosong jika tidak ingin mengubah Private Key';

        drawer.classList.add('open');
        drawer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', () => {
        drawer.classList.remove('open');
    });
}

merchantForm.addEventListener('submit', async event => {
    if (!merchantForm.dataset.editId) return; // Standard POST handled by browser
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(merchantForm));
    try {
        const response = await fetch(`/${routePrefix}/merchants/${merchantForm.dataset.editId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
        });
        const data = await responseData(response);
        const option = merchantSelect.querySelector(`option[value="${data.merchant.id}"]`);
        if (option) {
            option.dataset.name = data.merchant.name;
            option.dataset.baseUrl = data.merchant.base_url;
            option.dataset.partnerId = data.merchant.partner_id;
            option.dataset.channelId = data.merchant.channel_id;
            option.dataset.qrisChannelCode = data.merchant.qris_channel_code;
            option.textContent = `${data.merchant.name} (${data.merchant.partner_id})`;
            syncMerchantSelection();
        }
        drawer.classList.remove('open');
        showToast(data.message || 'Kredensial merchant berhasil disimpan.', 'success');
    } catch (error) {
        showToast(error.message, 'error');
    }
});

// Case Execution Logic
async function createRun() {
    const merchant = selectedMerchant();
    if (!merchant || !merchant.id) {
        showToast('Pilih merchant terlebih dahulu.', 'warning');
        throw new Error('Pilih merchant terlebih dahulu.');
    }
    const formData = new FormData();
    formData.append('merchant_id', merchant.id);
    const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: formData,
    });
    activeRun = await responseData(response);
    return activeRun;
}

async function executeCase(caseNo) {
    if (!activeRun) await createRun();
    const row = document.querySelector(`[data-case="${caseNo}"]`);
    const badge = row.querySelector('.case-status');
    const button = row.querySelector('.run-case');

    badge.textContent = 'RUNNING';
    badge.className = 'pill-badge case-status running';

    const response = await fetch(`/${routePrefix}/runs/${activeRun.run_id}/cases/${caseNo}/execute`, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });

    if (response.status === 404) {
        badge.textContent = 'FAIL';
        badge.className = 'pill-badge case-status fail';
        throw new Error(`Case ${caseNo} tidak ditemukan oleh aplikasi.`);
    }

    const data = await responseData(response);
    const resultStatus = data.result.result;
    badge.textContent = resultStatus;
    badge.className = `pill-badge case-status ${resultStatus.toLowerCase()}`;

    if (button) button.textContent = 'Uji Ulang';
    return data.result;
}

document.querySelectorAll('.run-case').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true;
    const previous = button.textContent;
    button.textContent = 'Memproses...';
    try {
        const result = await executeCase(button.dataset.case);
        showToast(`Kasus ${button.dataset.case}: ${result.result}`, result.result === 'PASS' ? 'success' : 'error');
    } catch (error) {
        showToast(error.message === 'Failed to fetch' ? 'Tidak dapat terhubung ke server.' : error.message, 'error');
        button.textContent = previous;
    }
    button.disabled = false;
}));

// Run All Automated Tests
form.addEventListener('submit', async event => {
    event.preventDefault();
    activeRun = null;

    const panel = document.getElementById('progress-panel');
    panel.classList.remove('hidden');
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    const runAllBtn = document.getElementById('run-all-btn');
    runAllBtn.disabled = true;

    const progressBar = document.getElementById('progress-bar');
    const progressHeading = document.getElementById('progress-heading');
    const progressCopy = document.getElementById('progress-copy');
    const progressCounter = document.getElementById('progress-counter');
    const progressActions = document.getElementById('progress-actions');
    const progressBadge = document.getElementById('progress-badge');

    progressActions.classList.add('hidden');
    progressBadge.classList.remove('hidden');

    let passed = 0;
    let failed = 0;

    try {
        await createRun();

        for (let i = 0; i < automatedCases.length; i++) {
            const caseNo = automatedCases[i];
            progressHeading.textContent = `Menjalankan Skenario ${caseNo}...`;
            progressCopy.textContent = `Kasus ${i + 1} dari ${automatedCases.length} dalam antrean`;
            progressCounter.textContent = `${i} / ${automatedCases.length} Selesai`;
            progressBar.style.width = `${Math.round((i / automatedCases.length) * 100)}%`;

            try {
                const result = await executeCase(caseNo);
                result.result === 'PASS' ? passed++ : failed++;
            } catch (error) {
                failed++;
                showToast(error.message, 'error');
            }
        }

        progressBar.style.width = '100%';
        progressHeading.textContent = 'Eksekusi Otomatis Selesai';
        progressCopy.textContent = `${passed} Kasus Berhasil (PASS), ${failed} Kasus Gagal (FAIL)`;
        progressCounter.textContent = `${automatedCases.length} / ${automatedCases.length} Selesai`;
        progressBadge.classList.add('hidden');

        const btnView = document.getElementById('btn-view-results');
        btnView.href = activeRun.show_url;

        const btnExport = document.getElementById('btn-export-results');
        btnExport.href = activeRun.export_url;

        progressActions.classList.remove('hidden');

        showToast(`UAT selesai: ${passed} PASS, ${failed} FAIL`, failed ? 'warning' : 'success');
    } catch (error) {
        progressHeading.textContent = 'Eksekusi Gagal';
        progressCopy.textContent = error.message;
        showToast(error.message, 'error');
    }

    runAllBtn.disabled = !merchantSelect || !merchantSelect.value;
});

// Category Filter Tabs
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.dataset.filter;
        document.querySelectorAll('tbody tr').forEach(row => {
            if (filter === 'all' || row.dataset.type === filter) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    });
});
</script>
</body>
</html>
