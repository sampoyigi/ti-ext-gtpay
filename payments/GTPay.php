<?php

namespace SamPoyigi\GTPay\Payments;

use Exception;
use Igniter\Admin\Classes\BasePaymentGateway;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\PayRegister\Traits\PaymentHelpers;
use Illuminate\Support\Facades\Redirect;
use Omnipay\Gtpay\Gateway;
use Omnipay\Gtpay\Message\Data;
use Omnipay\Omnipay;

class GTPay extends BasePaymentGateway
{
    use PaymentHelpers;

    public function registerEntryPoints()
    {
        return [
            'gtpay_notify_url' => 'processNotifyUrl',
        ];
    }

    public function isApplicable($total, $host)
    {
        return $host->order_total <= $total;
    }

    public function isTestMode()
    {
        return (bool)$this->model->test_mode;
    }

    public function getMerchantId()
    {
        return $this->isTestMode() ? '17' : $this->model->merchant_id;
    }

    public function getHashKey()
    {
        return $this->isTestMode()
            ? 'D3D1D05AFE42AD50818167EAC73C109168A0F108F32645C8B59E897FA930DA44F9230910DAC9E20641823799A107A02068F7BC0F4CC41D2952E249552255710F'
            : $this->model->hash_key;
    }

    /**
     * Processes payment using passed data.
     *
     * @param array $data
     * @param \Igniter\Admin\Models\Payment $host
     * @param \Igniter\Admin\Models\Order $order
     *
     * @return bool|\Illuminate\Http\RedirectResponse
     * @throws \Igniter\Flame\Exception\ApplicationException
     */
    public function processPaymentForm($data, $host, $order)
    {
        if (!$paymentMethod = $order->payment)
            throw new ApplicationException('Payment method not found');

        if (!$this->isApplicable($order->order_total, $host))
            throw new ApplicationException(sprintf(
                lang('igniter.payregister::default.alert_min_order_total'),
                currency_format($host->order_total),
                $host->name
            ));

        $fields = $this->getPaymentFormFields($order, $data);

        try {
            $gateway = $this->createGateway();
            $response = $gateway->purchase($fields)->send();

            if ($response->isRedirect())
                return Redirect::to($response->getRedirectUrl());

            $this->handlePaymentResponse($response, $order, $host, $fields);
        }
        catch (Exception $ex) {
            $order->logPaymentAttempt('Payment error -> '.$ex->getMessage(), 0, $fields, []);
            throw new ApplicationException('Sorry, there was an error processing your payment. Please try again later.');
        }
    }

    //
    //
    //

    /**
     * @return \Omnipay\Common\GatewayInterface|\Omnipay\Mollie\Gateway
     */
    protected function createGateway()
    {
        $gateway = Omnipay::create('Gtpay');

        $gateway->setMerchantId($this->getMerchantId());
        $gateway->setHashKey($this->getHashKey());
        $gateway->setGatewayFirst('no');
        $gateway->setGatewayName(Gateway::GATEWAY_BANK);
        $gateway->setCurrency(currency()->getUserCurrency());

        return $gateway;
    }

    protected function getPaymentFormFields($order, $data = [])
    {
        $notifyUrl = $this->makeEntryPointUrl('gtpay_notify_url').'/'.$order->hash;

        $fields = [
            'amount' => number_format($order->order_total, 2, '.', ''),
            'notifyUrl' => $notifyUrl,

            Data::GATEWAY_NAME => Gateway::GATEWAY_WEBPAY,
            Data::TRANSACTION_MEMO => 'Payment for Order '.$order->order_id,
            Data::CUSTOMER_NAME => $order->customer_name,
            Data::CUSTOMER_ID => $order->customer_id ?? 1,
            Data::TRANSACTION_ID => $order->order_id,
        ];

        return $fields;
    }
}
