<?php

namespace Webimpian\BayarcashSdk\Resources;

class PaymentIntentResource extends Resource
{
    public ?string $orderNumber;
    public ?string $payerName;
    public ?string $payerEmail;
    public ?string $payerTelephoneNumber;
    public ?float $amount;
    public ?string $url;
}
