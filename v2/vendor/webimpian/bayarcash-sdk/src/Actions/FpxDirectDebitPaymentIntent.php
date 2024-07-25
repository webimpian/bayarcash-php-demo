<?php

namespace Webimpian\BayarcashSdk\Actions;

use Webimpian\BayarcashSdk\Resources\FpxDirectDebitIntentResource;
use Webimpian\BayarcashSdk\Resources\FpxDirectDebitResource;
use Webimpian\BayarcashSdk\Resources\TransactionResource;

trait FpxDirectDebitPaymentIntent
{
    public function createFpxDirectDebitEnrollmentIntent(array $data)
    {
        return new FpxDirectDebitIntentResource(
            $this->post('mandates/enrollment/payment-intents', $data),
            $this
        );
    }

    public function createFpxDirectDebitMaintenanceIntent(array $data)
    {
        return new FpxDirectDebitIntentResource(
            $this->post('mandates/maintenance/payment-intents', $data),
            $this
        );
    }

    public function createFpxDirectDebitTerminationIntent(array $data)
    {
        return new FpxDirectDebitIntentResource(
            $this->post('mandates/termination/payment-intents', $data),
            $this
        );
    }

    public function getfpxDirectDebitransaction($id)
    {
        return new TransactionResource(
            $this->get('mandates/transactions/' . $id)['data'],
        );
    }

    public function getFpxDirectDebit($id)
    {
        return new FpxDirectDebitResource(
            $this->get('mandates/' . $id)['data'],
        );
    }
}
