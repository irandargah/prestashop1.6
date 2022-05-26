<?php

class IranDargahConfirmationModuleFrontController extends ModuleFrontController
{
    private $redirect_order_id = null;

    public function postProcess()
    {
        $reference = explode('_', $_POST['orderId'])[1];
        $order = Order::getByReference($reference);
        $order = $order->getFirst();
        if ($order === false) {
            $this->returnError('INVALID_ORDER');
        }

        $order_state = $order->getCurrentOrderState();
        $order_state_id = $order_state->id;
        if ($order_state_id != Configuration::get('PS_OS_IRANDARGAH_PENDING')) {
            $this->returnError('INVALID_OS');
        }

        $this->redirect_order_id = $order->id;

        $status = Tools::getValue('code');
        if ($status != 100) {
            $order->setCurrentState(Configuration::get('PS_OS_CANCELED'));
            $order->save();
            $this->returnError($status);
        } else {
            $api_key = Configuration::get('IRANDARGAH_MERCHANT_CODE');
            $current_currency = Currency::getDefaultCurrency();
            $amount = $order->total_paid * ($current_currency->iso_code == 'IRT' ? 10 : 1);

            $data = [
                'merchantID' => $api_key,
                'amount' => (int) $amount,
                'authority' => $_POST['authority'],
                'orderId' => $_POST['orderId'],
            ];

            $res = $this->sendToIrandargah($data);
            if ($res) {
                if ($res->status == 100) {
                    $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                    $order->save();

                    $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
                    $return_url = Tools::getShopProtocol() . $shop->domain . $shop->getBaseURI();
                    $return_url .= "index.php?controller=order-detail";
                    $return_url .= "&id_order={$order->id}";
                    header('Location: ' . $return_url);
                    exit;
                } else {
                    $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                    $order->save();
                    $this->returnError($res->status);
                }
            } else {
                $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                $order->save();
                $this->returnError('CONNECTION_ERROR');
            }
        }
    }

    public function returnError($result)
    {
        if (session_id() == '') {
            session_start();
        }

        $_SESSION['irandargah_error'] = $result;

        $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
        $return_url = Tools::getShopProtocol() . $shop->domain . $shop->getBaseURI();
        $return_url .= "index.php?controller=order-detail";
        $return_url .= "&id_order={$this->redirect_order_id}";
        header('Location: ' . $return_url);
        exit;
    }

    public function sendToIrandargah($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dargaah.com/verification');
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