<?php

namespace Webimpian\BayarcashSdk\Resources;

class FpxDirectDebitIntentResource extends Resource
{
    public ?string $orderNumber;
    public ?string $payerName;
    public ?int $payerIdType;
    public ?string $payerId;
    public ?string $payerEmail;
    public ?string $payerTelephoneNumber;
    public ?float $amount;
    public ?string $mandateApplicationType;
    public ?string $mandateApplicationReason;
    public ?string $mandateFrequencyMode;
    public ?string $mandateEffectiveDate;
    public ?string $mandateExpiryDate;
    public ?string $url;
}
