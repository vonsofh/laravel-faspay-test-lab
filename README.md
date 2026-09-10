# Laravel Faspay SNAP Test Lab 🧪

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg?style=flat-square&logo=php)](https://www.php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10%20%7C%20%5E11%20%7C%20%5E12%20%7C%20%5E13-FF2D20.svg?style=flat-square&logo=laravel)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A developer-friendly **Laravel extension / package** that provides an automated testing lab and official UAT certification evidence generator for **Faspay SNAP (QRIS, Virtual Account, Direct Debit)**.

Install it into any existing Laravel project with a single `composer require` command—no need to clone a boilerplate or juggle complicated Postman workspaces.

---

## 🎯 Features & Scope

- **Zero Project Overlap**:
  - Isolated route prefix: `/faspay-test-lab` (configurable).
  - Unique route names: `faspay-test-lab.*`.
  - Dedicated view namespace: `faspay-test-lab::*`.
  - Isolated database tables: `faspay_test_lab_merchants` and `faspay_test_lab_runs`.
  - Environment guard: auto-disabled in production unless explicitly turned on.
- **Automated UAT Certification Scenarios**:
  - Runs Faspay SNAP QRIS scenarios (18.1 – 18.25) with live progress and single-case rerun capability.
  - Generates valid SNAP SHA256withRSA signatures on the fly.
  - Multi-request evidence capture for conflict and query flows.
- **Bundled Official Faspay Excel Templates**:
  - `FASPAY QRIS - Skenario Functional Test_V.3.2.xlsx`
  - `Faspay VA - Skenario Functional Test_V.3.0 static.xlsx`
  - `FASPAY Direct Debit - Skenario Functional Test_V.3.2.xlsx`
  - Directly loads bundled templates via PhpSpreadsheet and outputs official certification evidence in one click.
- **Interactive Payment Flow (Case 18.12)**:
  - Reuses QR generated in case 18.6 with built-in auto-polling for payment confirmation via Faspay Payment Simulator.
- **Security-First**:
  - Private keys are encrypted at rest using Laravel's application key (`APP_KEY`).
  - Private keys are hidden from UI, API responses, logs, and exported spreadsheets.

---

## 📦 Installation

Install the package via Composer into your Laravel application:

```bash
composer require vonsofh/laravel-faspay-test-lab --dev
```

Run the install command to publish the config and run migrations:

```bash
php artisan faspay-test-lab:install
```

*(Or run `php artisan migrate` directly—package auto-discovery takes care of everything).*

---

## 🚀 Usage

1. Open your browser and navigate to:
   ```text
   http://localhost:8000/faspay-test-lab
   ```
2. **Add a Merchant Profile**:
   Enter your Faspay Merchant ID, Base URL (`https://debit-sandbox.faspay.co.id`), Channel ID, QRIS Channel Code, and RSA Private Key PEM.
3. **Run Automated Tests**:
   Click **"Run All Automated Tests"** or execute individual cases.
4. **Test Payment Verification (18.12)**:
   Scan or copy the QR from case 18.6, pay via the Faspay Sandbox Simulator, and click **"Check Payment Status"** or **"Start Auto Check"**.
5. **Download Official Excel Report**:
   Click **"Export XLSX"** on the result page to download the populated official Faspay certification spreadsheet.

---

## ⚙️ Configuration (Optional)

Publish the configuration file:

```bash
php artisan vendor:publish --tag=faspay-test-lab-config
```

In `config/faspay-test-lab.php`:

```php
return [
    // null = enabled only in 'local' and 'testing' environments
    'enabled' => env('FASPAY_TEST_LAB_ENABLED', null),

    // Dashboard route prefix
    'route_prefix' => env('FASPAY_TEST_LAB_PREFIX', 'faspay-test-lab'),

    // Route middleware
    'middleware' => ['web'],

    // Table names
    'tables' => [
        'merchants' => 'faspay_test_lab_merchants',
        'runs' => 'faspay_test_lab_runs',
    ],
];
```

---

## 🎨 Customizing Views or Templates

If you wish to customize the Blade views or templates:

```bash
# Publish views to resources/views/vendor/faspay-test-lab
php artisan vendor:publish --tag=faspay-test-lab-views

# Publish Excel templates to storage/app/templates/faspay
php artisan vendor:publish --tag=faspay-test-lab-templates
```

---

## 🔒 Security

- The package includes `EnsureTestLabEnabled` middleware that automatically returns HTTP 404 in non-local environments unless `FASPAY_TEST_LAB_ENABLED=true` is explicitly set in `.env`.
- Merchant private keys are stored encrypted via Laravel's native `encrypted` model cast and are never exposed in JSON responses or exported files.

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
