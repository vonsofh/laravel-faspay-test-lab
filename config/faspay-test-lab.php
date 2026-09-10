<?php

$case = static fn (
    string $no,
    string $service,
    string $scenario,
    string $executionType,
    ?string $expectedCode = null,
    ?string $expectedMessage = null,
    string $notes = '',
    string $handler = '',
): array => [
    'no' => $no,
    'service' => $service,
    'scenario' => $scenario,
    'expected_code' => $expectedCode,
    'expected_message' => $expectedMessage,
    'execution_type' => $executionType,
    'notes' => $notes,
    'handler' => $handler,
];

return [
    /*
    |--------------------------------------------------------------------------
    | Faspay Test Lab Activation
    |--------------------------------------------------------------------------
    |
    | When null, Test Lab is enabled only in 'local' and 'testing' environments.
    | You can explicitly enable or disable it via FASPAY_TEST_LAB_ENABLED.
    |
    */
    'enabled' => env('FASPAY_TEST_LAB_ENABLED', null),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | The prefix and middleware for Test Lab web routes.
    |
    */
    'route_prefix' => env('FASPAY_TEST_LAB_PREFIX', 'faspay-test-lab'),
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | QRIS Payment Notification
    |--------------------------------------------------------------------------
    |
    | This machine-to-machine route is intentionally separate from the Test Lab
    | dashboard middleware. Faspay must be able to call it without a session or
    | CSRF token, including when the dashboard is disabled in production.
    |
    */
    'qris_notification' => [
        'enabled' => env('FASPAY_TEST_LAB_QRIS_NOTIFICATION_ENABLED', false),
        'path' => env(
            'FASPAY_TEST_LAB_QRIS_NOTIFICATION_PATH',
            'faspay/sandbox/v1.0/qr/qr-mpm-notify',
        ),
        // Faspay's public key verifies inbound SHA256withRSA signatures.
        'faspay_public_key' => env('FASPAY_TEST_LAB_FASPAY_PUBLIC_KEY'),
        'faspay_public_key_path' => env(
            'FASPAY_TEST_LAB_FASPAY_PUBLIC_KEY_PATH',
            env('FASPAY_SANDBOX_PUBLIC_KEY_PATH'),
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    |
    | Isolated table names to prevent collisions with existing application tables.
    |
    */
    'tables' => [
        'merchants' => 'faspay_test_lab_merchants',
        'runs' => 'faspay_test_lab_runs',
    ],

    /*
    |--------------------------------------------------------------------------
    | QRIS Functional Test Cases (18.1 – 18.25)
    |--------------------------------------------------------------------------
    */
    'qris_cases' => [
        $case('18.1', 'Any Service', 'Access Token Invalid', 'not_applicable', notes: 'Tidak Menggunakan Service Token'),
        $case('18.2', 'QR MPM - Generate QR', 'Unauthorized Signature', 'automated', '4014700', 'Unauthorized. [Signature]', handler: 'invalid_signature'),
        $case('18.3', 'QR MPM - Generate QR', 'Missing Mandatory Field', 'automated', '4004702', 'Invalid Mandatory Field X-TIMESTAMP', handler: 'missing_timestamp'),
        $case('18.4', 'QR MPM - Generate QR', 'Invalid Field Format', 'automated', '4004701', 'Invalid Field Format X-EXTERNAL-ID', handler: 'invalid_external_id'),
        $case('18.5', 'QR MPM - Generate QR', 'Cannot use the same X-EXTERNAL-ID', 'automated', '4094700', 'Conflict', handler: 'duplicate_external_id'),
        $case('18.6', 'QR MPM - Generate QR', 'Show QR Code', 'automated', '2004700', handler: 'generate_success'),
        $case('18.7', 'QR MPM - Generate QR', 'Invalid Merchant', 'automated', '4044708', 'Invalid Merchant', handler: 'invalid_merchant'),
        $case('18.8', 'Cancel Payment', 'Cancel Success', 'not_applicable', notes: 'Tidak menggunakan Service Cancel Payment'),
        $case('18.9', 'Cancel Payment', 'Cancel In Progress', 'not_applicable', notes: 'Tidak menggunakan Service Cancel Payment'),
        $case('18.10', 'Query Payment', 'Pending Transaction', 'automated', '2005100', handler: 'query_pending'),
        $case('18.11', 'Query Payment', 'Transaction Not Found', 'automated', '4045101', 'Transaction Not Found', handler: 'query_not_found'),
        $case('18.12', 'Query Payment', 'Query Successful Transaction', 'requires_payment', '2005100', notes: 'Bayar QRIS dari case 18.6, lalu periksa status pembayaran.', handler: 'query_success'),
        $case('18.13', 'Refund Payment', 'Refund Success', 'not_applicable', notes: 'Tidak menggunakan Service Refund Payment'),
        $case('18.14', 'Refund Payment', 'Refund In Progress', 'not_applicable', notes: 'Tidak menggunakan Service Refund Payment'),
        $case('18.15', 'Payment Notification', 'Notification for Successful Transaction', 'not_applicable', notes: 'Tidak Menggunakan Service Payment Notification'),
        $case('18.16', 'Payment Notification', 'Notification for Failed Transaction', 'not_applicable', notes: 'Tidak Menggunakan Service Payment Notification'),
        $case('18.17', 'Transaction Status Inquiry', 'Inquiry Status atas transaksi berhasil', 'not_applicable', notes: 'Tidak Menggunakan Service Transaction Status Inquiry'),
        $case('18.18', 'QR MPM - Decode QR', 'Parsing Payload QR Code', 'not_applicable', notes: 'Tidak menggunakan Service QR MPM - Decode QR'),
        $case('18.19', 'QR MPM - Decode QR', 'QR Code Payload Parsing Error', 'not_applicable', notes: 'Tidak menggunakan Service QR MPM - Decode QR'),
        $case('18.20', 'QR MPM - Payment Redirect', 'Payment Success', 'not_applicable', notes: 'Tidak menggunakan Service QR MPM - Payment Redirect'),
        $case('18.21', 'QR MPM - Payment Redirect', 'Amount Exceed Limit Reach', 'not_applicable', notes: 'Tidak menggunakan Service QR MPM - Payment Redirect'),
        $case('18.22', 'QR MPM - Apply OTT', 'Get Auth Code', 'not_applicable', notes: 'Tidak menggunakan Service QR MPM - Apply OTT'),
        $case('18.23', 'QR MPM - Payment Host to Host', 'Payment Success', 'not_applicable', notes: 'Tidak Menggunakan Service QR MPM - Payment Host to Host'),
        $case('18.24', 'QR MPM - Payment Host to Host', 'Amount Exceed Limit Reach', 'not_applicable', notes: 'Tidak Menggunakan Service QR MPM - Payment Host to Host'),
        $case('18.25', 'Payment Notification', 'Merchant membuat transaksi hingga pembayaran sukses. Saat menerima request notifikasi sukses dari Faspay, merchant mengembalikan response sesuai dokumentasi.', 'manual', notes: 'Menunggu notification sukses aktual dari Faspay setelah QRIS dibayar. Localhost memerlukan URL publik/tunnel agar callback Faspay dapat diterima.', handler: 'notification_e2e'),
    ],
];
