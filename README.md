# phdevutils/bir

[![Packagist version](https://img.shields.io/packagist/v/phdevutils/bir?label=Packagist&color=f28d1a&logo=packagist&logoColor=white)](https://packagist.org/packages/phdevutils/bir)
[![npm version](https://img.shields.io/npm/v/@ph-dev-utils/bir?label=npm&color=cb3837&logo=npm)](https://www.npmjs.com/package/@ph-dev-utils/bir)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/kon2raya24/ph-bir/blob/main/LICENSE)
[![Made in PH](https://img.shields.io/badge/made%20in-🇵🇭%20Philippines-0038A8)](https://github.com/kon2raya24)

**BIR (Bureau of Internal Revenue) tax utilities for the Philippines** — VAT, percentage tax, individual income tax, expanded withholding tax (EWT), and a reference list of common BIR forms. PHP + JavaScript, MIT-licensed.

Companion to [phdevutils/payroll](https://github.com/kon2raya24/ph-payroll), which covers the employee side (SSS / PhilHealth / Pag-IBIG / 13th-month / BIR withholding tax on compensation). This package covers the **non-employee** side: VAT on goods and services, percentage tax for small non-VAT businesses, graduated/8% income tax, creditable withholding on income payments, and form references.

```bash
composer require phdevutils/bir
```

## PHP usage

```php
use PhDevUtils\Bir\{Vat, PercentageTax, IncomeTax, Ewt, Forms};

// VAT
Vat::add(1000.0);           // ['gross' => 1120.0, 'net' => 1000.0, 'vat' => 120.0, …]
Vat::extract(1120.0);       // ['gross' => 1120.0, 'net' => 1000.0, 'vat' => 120.0, …]
Vat::isRegistrationRequired(3_500_000.0); // true (above ₱3M threshold)

// Percentage tax — date-aware
PercentageTax::compute(100000.0);                    // 3,000 (3% current)
PercentageTax::compute(100000.0, '2022-06-15');      // 1,000 (1% CREATE-era)
PercentageTax::rate('2023-07-01');                   // 0.03 (revert date)

// Income tax — graduated vs 8%
IncomeTax::graduated(500000.0)['tax'];               // 42500.0
IncomeTax::eightPercent(500000.0);                   // ['tax' => 20000.0, 'eligible' => true, …]

// Expanded withholding tax (creditable) on income payments
Ewt::compute(50000.0, 'rental');                     // ['rate' => 0.05, 'tax' => 2500.0, 'net' => 47500.0, …]
Ewt::compute(100000.0, 'professional_individual');   // 10% by default (must qualify for 5%)
Ewt::compute(100000.0, 'professional_individual',
  ['swornDeclaration' => true, 'payeeAnnualGross' => 800000]); // 5%
Ewt::listCategories();                               // 8 common categories

// BIR forms reference
Forms::find('2316');
Forms::find('2550M');                       // status: 'superseded', superseded_by: '2550Q' (EOPT Act RA 11976)
Forms::list(['frequency' => 'quarterly']);  // 1701Q, 1702Q, 2550Q, 2551Q, …
```

## JS usage

A parity JavaScript/TypeScript package is published as [`@ph-dev-utils/bir`](https://www.npmjs.com/package/@ph-dev-utils/bir):

```ts
import { addVat, computeEWT, incomeTaxGraduated, findForm } from '@ph-dev-utils/bir';

addVat(1000);                            // { gross: 1120, net: 1000, vat: 120, rate: 0.12 }
computeEWT(50000, 'rental');             // { rate: 0.05, tax: 2500, net: 47500, … }
incomeTaxGraduated(500000).tax;          // 42500
findForm('2316');
```

## What's verified

| Capability | Rate / Value | Legal basis | Effective |
|---|---|---|---|
| VAT rate | 12% | RA 9337 | 2005-11-01 |
| VAT threshold | ₱3,000,000 | TRAIN RA 10963 | 2018-01-01 |
| Percentage tax | 3% | Tax Code § 116 | reverted 2023-07-01 |
| Percentage tax (historical) | 1% | RA 11534 (CREATE) | 2020-07-01 → 2023-06-30 |
| Individual income tax | graduated + 8% option | TRAIN RA 10963 | 2018 & 2023 tables |
| EWT — professional/commission | 5%/10% (indiv.), 10%/15% (corp.) | RR 11-2018 | TRAIN era |
| EWT — rental / contractor / TWA | 5% / 2% / 1%–2% | RR 11-2018 | TRAIN era |
| Form 2550M supersession | retired | EOPT Act RA 11976 | 2024-01-22 |

Each rate is sourced to the originating Republic Act / Revenue Regulation, with `_meta` on each data file recording `source`, `source_url`, and `verified_on`. **DST is deferred** pending verification of the 2025 CMEPA (RA 12214) revisions.

## ⚠️ Disclaimer

Real-money tax math. Verify outputs against official BIR calculators before production use. PRs welcome for additions, corrections, and EWT/DST/ATC contributions.

## License

MIT
