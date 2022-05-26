<?php

class IranDargahValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        //Check if cart exists and all the fields are set
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
         * Verify if this payment module is authorized
         */
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->l('This payment method is not available.'));
        }

        //Check if customer exists
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $api_key = Configuration::get('IRANDARGAH_MERCHANT_CODE');
        $callback = $this->context->link->getModuleLink('IranDargah', 'confirmation', [], true);

        $currency = $this->context->currency;

        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_IRANDARGAH_PENDING'),
            $cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            array(),
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        /**
         * If the order has been validated we try to retrieve it
         */
        $order_id = Order::getOrderByCartId((int) $cart->id);
        if (!$order_id) {
            die($this->module->l('Failed to create an order.'));
        }

        $amount = $cart->getOrderTotal(true, Cart::BOTH) * ($currency->iso_code == 'IRT' ? 10 : 1);

        $data = [
            'merchantID' => $api_key,
            'amount' => (int) $amount,
            'callbackURL' => $callback,
            'orderId' => $cart->id . '_' . $this->module->currentOrderReference,
            'description' => 'سفارش شماره: ' . $cart->id,
        ];

        $res = $this->sendToIrandargah($data);
        if ($res) {
            if ($res->status == 200) {
                Tools::redirect('https://dargaah.com/ird/startpay/' . $res->authority);
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

    public function sendToIrandargah($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dargaah.com/payment');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response  = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }
}