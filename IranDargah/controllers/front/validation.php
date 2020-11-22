<?php

class IranDargahValidationModuleFrontController extends ModuleFrontController
{
    private $soap_url = "https://dargaah.com/wsdl";

    public function postProcess()
    {
        //Check if cart exists and all the fields are set
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        //Check if module is enabled
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
            }

        }
        if (!$authorized) {
            die('This payment method is not available.');
        }

        //Check if customer exists
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $api_key = Configuration::get('IRANDARGAH_MERCHANT_CODE');
        $callback = $this->context->link->getModuleLink('IranDargah', 'confirmation', [], true);

        $currency = $this->context->currency;
        $extra_vars = array();
        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_IRANDARGAH_PENDING'),
            $cart_total,
            $this->module->displayName,
            null,
            $extra_vars,
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        $amount = $cart->getOrderTotal(true, Cart::BOTH) * (1 / $currency->conversion_rate);
        $client = new SoapClient($this->soap_url, ['cache_wsdl' => WSDL_CACHE_NONE]);
        $res = $client->__soapCall('IRDPayment', [
            [
                'merchantID' => $api_key,
                'amount' => (int) $amount,
                'orderId' => $cart->id . '_' . $this->module->currentOrderReference,
                'callbackURL' => $callback,
                'description' => 'سفارش شماره: ' . $cart->id,
            ],
        ]);
        if ($res) {
            if ($res->status == 200) {
                header('Location: https://dargaah.com/ird/startpay/' . $res->authority);
                die();
            } else {
                $this->returnError($res->status);
            }
        } else {
            $this->returnError('CONNECTION_ERROR');
        }
    }

    public function returnError($error_code)
    {
        echo $error_code;
        if (session_id() == '') {
            session_start();
        }

        $_SESSION['irandargah_error'] = $error_code;
        Tools::redirect('index.php?controller=order?step=3');
        exit;
    }
}
