<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Faspay Test Result</title>
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

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .breadcrumb a:hover { color: var(--text-main); }
        .breadcrumb-sep { color: var(--text-subtle); }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-subtle);
            flex-wrap: wrap;
        }
        .page-title h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 6px;
        }
        .page-title p {
            color: var(--text-muted);
            margin: 0;
            font-size: 13px;
            font-family: var(--font-mono);
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: var(--radius-sm);
            padding: 8px 14px;
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

        /* Summary Metric Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        @media (max-width: 800px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 500px) {
            .summary-grid { grid-template-columns: 1fr; }
        }
        .stat-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 16px;
            box-shadow: var(--shadow-card);
        }
        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-weight: 600;
        }
        .stat-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Card container */
        .card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: var(--shadow-card);
            margin-bottom: 16px;
        }
        .card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 10px;
        }
        .case-title {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 4px;
            color: var(--text-main);
        }
        .case-code-pill {
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            padding: 2px 7px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            margin-right: 6px;
        }

        /* Pill Badges */
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
        .pill-badge.pass { background: var(--success-bg); color: var(--success); border-color: var(--success-border); }
        .pill-badge.fail { background: var(--danger-bg); color: var(--danger); border-color: var(--danger-border); }
        .pill-badge.waiting { background: var(--warning-bg); color: var(--warning); border-color: var(--warning-border); }
        .pill-badge.manual { background: #f8fafc; color: #475569; border-color: var(--border-strong); }
        .pill-badge.na { background: #f1f5f9; color: #94a3b8; border-color: var(--border-subtle); }
        .pill-badge.running { background: var(--info-bg); color: var(--info); border-color: var(--info-border); }

        /* QRIS Box */
        .qris-box {
            background: #f8fafc;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin: 14px 0;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .qris-img {
            max-width: 180px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-subtle);
            background: #ffffff;
            padding: 8px;
        }
        .qris-details dl {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 6px;
            font-size: 13px;
            margin: 0 0 12px;
        }
        .qris-details dt { color: var(--text-muted); font-size: 12px; }
        .qris-details dd { margin: 0; font-family: var(--font-mono); font-size: 12px; font-weight: 600; }

        /* Expectation table */
        .spec-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin: 12px 0;
            background: #f8fafc;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-subtle);
            overflow: hidden;
        }
        .spec-table th, .spec-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--border-subtle);
            text-align: left;
        }
        .spec-table tr:last-child th, .spec-table tr:last-child td { border-bottom: none; }
        .spec-table th { width: 120px; color: var(--text-muted); font-weight: 600; font-size: 12px; }
        .spec-table td { font-family: var(--font-mono); font-size: 12px; }

        /* Evidence Collapsible */
        details.evidence {
            margin-top: 10px;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        details.evidence summary {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
            list-style: none;
        }
        details.evidence summary::-webkit-details-marker { display: none; }
        details.evidence summary:hover { background: #f1f5f9; color: var(--text-main); }
        .evidence-chevron {
            display: inline-block;
            transition: transform 0.15s ease;
        }
        details.evidence[open] .evidence-chevron {
            transform: rotate(90deg);
        }
        pre {
            margin: 0;
            padding: 14px;
            background: #0f172a;
            color: #f8fafc;
            font-family: var(--font-mono);
            font-size: 12px;
            line-height: 1.5;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Manual payment form */
        .manual-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 12px 0;
        }
        @media (max-width: 600px) { .manual-form { grid-template-columns: 1fr; } }
        .manual-form label { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
        .manual-form input { width: 100%; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); padding: 7px 10px; font-size: 13px; }

        /* Toast Container */
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
    </style>
</head>
<body>

<div class="container">
    <!-- Breadcrumbs -->
    <div class="breadcrumb">
        <a href="{{ route('faspay-test-lab.index') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Faspay SNAP Test Lab</span>
        </a>
        <span class="breadcrumb-sep">/</span>
        <span>Run #{{ $run->id }}</span>
        <span class="breadcrumb-sep">/</span>
        <span style="color:var(--text-main); font-weight:600;">Evidence & Hasil</span>
    </div>

    @if(session('status'))
        <div class="card" style="padding:12px 16px; margin-bottom:16px; background:#f0fdf4; border-color:#bbf7d0; color:#166534; font-size:13px; font-weight:500;">
            {{ session('status') }}
        </div>
    @endif

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>{{ $run->merchant?->name ?? 'Merchant' }} — Hasil QRIS UAT</h1>
            <p>Dieksekusi pada {{ $run->created_at->format('d F Y, H:i:s') }} · Merchant ID: {{ $run->merchant?->partner_id }}</p>
        </div>

        <div class="header-actions">
            <a class="btn success" href="{{ route('faspay-test-lab.runs.export', $run) }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Unduh Laporan Excel (.xlsx)</span>
            </a>
            <a class="btn secondary" href="{{ route('faspay-test-lab.index') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    @php($results = collect($run->results))
    @php($autoCases = $results->where('execution_type', 'automated'))
    @php($passCount = $autoCases->where('result', 'PASS')->count())
    @php($failCount = $autoCases->where('result', 'FAIL')->count())
    @php($payCase = $results->firstWhere('test_no', '18.12'))
    @php($callbackCase = $results->firstWhere('test_no', '18.25'))

    <!-- Summary Metrics -->
    <div class="summary-grid">
        <div class="stat-card">
            <div class="stat-label">Otomatis (18.2–18.11)</div>
            <div class="stat-value" style="color: {{ $failCount > 0 ? 'var(--danger)' : 'var(--success)' }};">
                {{ $passCount }} PASS · {{ $failCount }} FAIL
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Simulasi Bayar (18.12)</div>
            <div class="stat-value">
                <span class="pill-badge {{ ($payCase['result'] ?? '') === 'PASS' ? 'pass' : 'waiting' }}">
                    {{ $payCase['result'] ?? 'WAITING' }}
                </span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Callback Faspay (18.25)</div>
            <div class="stat-value">
                <span class="pill-badge manual">Menunggu Notifikasi</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Not Applicable</div>
            <div class="stat-value" style="color:var(--text-muted);">
                {{ $results->where('result', 'N/A')->count() }} Kasus (Auto-filled)
            </div>
        </div>
    </div>

    <!-- Detailed Case Evidence List -->
    <div>
        @foreach($run->results as $item)
            @php($status = strtolower($item['result']))
            @php($badgeClass = match($item['result']) {
                'PASS' => 'pass',
                'FAIL' => 'fail',
                'WAITING' => 'waiting',
                'MANUAL' => 'manual',
                'N/A' => 'na',
                default => 'not-run',
            })
            <div class="card">
                <div class="card-top">
                    <div>
                        <h2 class="case-title">
                            <span class="case-code-pill">{{ $item['test_no'] }}</span>
                            <span>{{ $item['scenario'] }}</span>
                        </h2>
                        <div style="font-size:12px; color:var(--text-muted);">
                            Modul: {{ $item['service'] ?? 'QR MPM' }}
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="pill-badge {{ $badgeClass }}">{{ $item['result'] }}</span>
                        @if($item['execution_type'] === 'automated' && in_array($item['result'], ['FAIL', 'NOT RUN'], true))
                            <button class="btn secondary btn-sm run-again" type="button" data-run="{{ $run->id }}" data-case="{{ $item['test_no'] }}">
                                Uji Ulang
                            </button>
                        @endif
                    </div>
                </div>

                @if($item['test_no'] === '18.6' && $item['result'] === 'PASS')
                    <div class="qris-box">
                        @php($qrImage = data_get($item, 'metadata.qrUrl') ?: data_get($item, 'metadata.qrImageUrl'))
                        @if($qrImage)
                            <img src="{{ $qrImage }}" alt="QRIS Code" class="qris-img">
                        @endif
                        <div class="qris-details" style="flex:1;">
                            <div style="font-weight:600; font-size:14px; margin-bottom:8px; color:var(--text-main);">
                                QRIS Berhasil Di-generate
                            </div>
                            <dl>
                                <dt>Reference No:</dt>
                                <dd>{{ data_get($item, 'metadata.referenceNo') }}</dd>
                                <dt>Partner Ref No:</dt>
                                <dd>{{ data_get($item, 'metadata.partnerReferenceNo') }}</dd>
                                <dt>Nominal:</dt>
                                <dd>{{ data_get($item, 'metadata.amount') }}</dd>
                            </dl>
                            <p style="font-size:12px; color:var(--text-muted); margin:0 0 10px;">
                                Bayar QR ini di simulator Faspay sandbox untuk memverifikasi skenario 18.12.
                            </p>
                            <button class="btn success btn-sm check-payment" type="button" data-run="{{ $run->id }}">
                                Periksa Status Pembayaran Sekarang
                            </button>
                        </div>
                    </div>
                @endif

                @if($item['test_no'] === '18.12' && in_array($item['result'], ['WAITING', 'MANUAL'], true))
                    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:var(--radius-sm); padding:12px 16px; margin:10px 0; font-size:13px;">
                        <div style="font-weight:600; color:#92400e; margin-bottom:4px;">Menunggu Pembayaran QRIS</div>
                        @if(filled(data_get($results->firstWhere('test_no', '18.6'), 'metadata.referenceNo')))
                            <p style="margin:0 0 8px; color:#78350f; font-size:12px;">
                                Reference No: <code>{{ data_get($results->firstWhere('test_no', '18.6'), 'metadata.referenceNo') }}</code>
                            </p>
                            <div style="display:flex; gap:8px;">
                                <button class="btn secondary btn-sm check-payment" type="button" data-run="{{ $run->id }}">
                                    Periksa Status Pembayaran
                                </button>
                                <button class="btn btn-sm" type="button" id="start-polling" data-run="{{ $run->id }}">
                                    Mulai Auto-Check (Polling)
                                </button>
                            </div>
                        @else
                            <p style="margin:0 0 8px; color:#78350f;">
                                Belum ada transaksi QR. Jalankan pengujian 18.6 terlebih dahulu.
                            </p>
                            <button class="btn secondary btn-sm run-again" type="button" data-run="{{ $run->id }}" data-case="18.6">
                                Generate QR untuk 18.12
                            </button>
                        @endif
                    </div>
                @endif

                @if($item['test_no'] === '18.12' && $item['result'] === 'PASS')
                    <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:var(--radius-sm); padding:12px 16px; margin:10px 0; font-size:13px; color:#065f46;">
                        <div style="font-weight:600; margin-bottom:4px;">Pembayaran Terdeteksi & Berhasil</div>
                        @if(data_get($item, 'metadata.transactionStatusDesc'))
                            <div>Keterangan: {{ data_get($item, 'metadata.transactionStatusDesc') }}</div>
                        @endif
                        @if(data_get($item, 'metadata.paidTime'))
                            <div>Waktu Bayar: <code>{{ data_get($item, 'metadata.paidTime') }}</code></div>
                        @endif
                        @if(data_get($item, 'metadata.paymentReff'))
                            <div>Payment Reff: <code>{{ data_get($item, 'metadata.paymentReff') }}</code></div>
                        @endif
                    </div>
                @endif

                @if($item['test_no'] === '18.25')
                    <div style="background:#f8fafc; border:1px solid var(--border-subtle); border-radius:var(--radius-sm); padding:10px 14px; margin:10px 0; font-size:12px; color:var(--text-muted);">
                        Setelah QRIS dibayar di simulator Faspay, Faspay akan mengirimkan request HTTP POST notifikasi callback ke endpoint webhook aplikasi Anda.
                    </div>
                @endif

                @if(in_array($item['result'], ['N/A', 'MANUAL'], true) && !empty($item['notes']))
                    <p style="font-size:12px; color:var(--text-muted); margin:6px 0 0;">
                        Catatan: {{ $item['notes'] }}
                    </p>
                @endif

                @if(! in_array($item['result'], ['N/A', 'MANUAL', 'NOT RUN'], true) && is_array($item['request']))
                    <table class="spec-table">
                        <tr>
                            <th>Expected</th>
                            <td>{{ $item['expected_code'] }} — {{ $item['expected_message'] ?? 'Respons Sukses' }}</td>
                        </tr>
                        <tr>
                            <th>Actual</th>
                            <td>{{ $item['actual_code'] }} — {{ $item['actual_message'] ?? '-' }}</td>
                        </tr>
                    </table>

                    <details class="evidence">
                        <summary>
                            <svg class="evidence-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            <span>Lihat Request Payload & Headers</span>
                        </summary>
                        <pre>{{ json_encode($item['request'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>

                    <details class="evidence">
                        <summary>
                            <svg class="evidence-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            <span>Lihat Response Payload & Headers</span>
                        </summary>
                        <pre>{{ json_encode($item['response'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </div>
        @endforeach
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="toast-container" aria-live="polite"></div>

<script>
const routePrefix = '{{ config('faspay-test-lab.route_prefix', 'faspay-test-lab') }}';
const csrf = '{{ csrf_token() }}';

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
    close.onclick = () => toast.remove();

    toast.append(text, close);
    document.getElementById('toast-container').append(toast);
    setTimeout(() => toast.remove(), 4500);
}

async function postJson(url, body = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(body),
    });
    let data = {};
    try { data = await response.json(); } catch (_) {}
    if (!response.ok) {
        const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(validation || data.message || data.status || 'Tidak dapat terhubung ke server.');
    }
    return data;
}

document.querySelectorAll('.run-again').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true;
    button.textContent = 'Memproses...';
    try {
        await postJson(`/${routePrefix}/runs/${button.dataset.run}/cases/${button.dataset.case}/execute`);
        location.reload();
    } catch (error) {
        button.textContent = 'Uji Ulang';
        button.disabled = false;
        showToast(error.message, 'error');
    }
}));

async function checkPayment(button, body = {}) {
    button.disabled = true;
    button.textContent = 'Memeriksa...';
    try {
        const data = await postJson(`/${routePrefix}/runs/${button.dataset.run}/cases/18.12/check`, body);
        button.textContent = data.result.payment_status;
        if (data.result.result === 'PASS') {
            showToast('Pembayaran terdeteksi!', 'success');
            location.reload();
        } else {
            showToast('Pembayaran masih ditunggu di sandbox.', 'info');
        }
        return data.result;
    } catch (error) {
        button.textContent = 'Periksa Pembayaran';
        showToast(error.message, 'error');
        throw error;
    } finally {
        button.disabled = false;
    }
}

document.querySelectorAll('.check-payment').forEach(button => button.addEventListener('click', () => checkPayment(button)));

let polling = false;
document.getElementById('start-polling')?.addEventListener('click', async event => {
    if (polling) return;
    polling = true;
    const button = event.currentTarget;
    const deadline = Date.now() + 120000;
    button.disabled = true;
    button.textContent = 'Auto-checking...';

    while (Date.now() < deadline) {
        try {
            const result = await checkPayment(button);
            if (result.result === 'PASS') {
                polling = false;
                return;
            }
        } catch (_) {
            button.disabled = false;
            polling = false;
            return;
        }
        await new Promise(resolve => setTimeout(resolve, 5000));
    }

    button.textContent = 'Waktu habis';
    button.disabled = false;
    polling = false;
    showToast('Auto-check waktu habis. Silakan periksa kembali nanti.', 'warning');
});
</script>
</body>
</html>
