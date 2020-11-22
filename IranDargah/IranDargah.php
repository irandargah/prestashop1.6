<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class IranDargah extends PaymentModule
{
    protected $error_messages;

    public function __construct()
    {
        $this->name = 'IranDargah';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->author = 'IranDargah.com';
        $this->author = $this->l('IranDargah.com');
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
        $this->bootstrap = true;

        $this->error_messages = array(
            'CONNECTION_ERROR' => $this->l('Error in connecting to IranDargah Payment Gateway'),
            201 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Already sent to bank gateway'),
            404 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Transaction not found'),
            403 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Invalid Merchant'),
            -11 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Invalid Amount'),
            -12 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Invalid Amount'),
            -10 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Payment amount is less than the permitted amount.'),
            'TOO_MUCH_AMOUNT' => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Payment amount is more than the permitted amount.'),
            -20 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Invalid Reference'),
            -13 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Repeated Transaction.'),
            -21 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Took too long to connect to bank gateway.'),
            -22 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('sent to bank'),
            -23 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Bank Gateway connection error, amount refunded.'),
            -30 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Error in payment process, amount refunded.'),
            -31 => $this->l('Error in calling IranDargah API:') . ' ' . $this->l('Unknown Error'),
            'UNKNOWN_ERROR' => $this->l('Error in confirming payment:') . ' ' . $this->l('Unknown Error'),
        );

        $this->order_states = array(
            'PS_OS_IRANDARGAH_PENDING' => $this->l('IranDargah Pending Payment'),
        );

        parent::__construct();

        $this->displayName = $this->l('IranDargah Payment Gateway');
        $this->description = $this->l('IranDargah Payment Gateway for fast and easy payments via SHETAB cards');
        $this->confirmUninstall = $this->l('Are you sure you wnat to uninstall IranDargah Payment Gateway Module?');
        if (!Configuration::get('IRANDARGAH_MERCHANT_CODE')) {
            $this->warning = $this->l('You have to enter IranDargah Merchant Code inorder to make IranDargah Payment Gateway work correctly!');
        }
    }

    public function install()
    {
        if (
            !parent::install() ||
            !$this->registerHook('displayPayment') ||
            !$this->registerHook('displayPaymentReturn') ||
            !$this->registerHook('DisplayOverrideTemplate') ||
            !$this->installPendingOrderState() ||
            !$this->installTomanCurrency()
        ) {
            return false;
        }

        return true;
    }

    public function installPendingOrderState()
    {
        if (Configuration::get('PS_OS_IRANDARGAH_PENDING') < 1) {
            $order_state = new OrderState();
            $order_state->send_email = false;
            $order_state->module_name = $this->name;
            $order_state->invoice = false;
            $order_state->color = '#FF6F00';
            $order_state->logable = false;
            $order_state->shipped = false;
            $order_state->unremovable = false;
            $order_state->delivery = false;
            $order_state->hidden = false;
            $order_state->paid = false;
            $order_state->deleted = false;
            $order_state->name = array((int) Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('IranDargah Pending Payment')));
            if ($order_state->add()) {
                Configuration::updateValue('PS_OS_IRANDARGAH_PENDING', $order_state->id);
                copy(dirname(__FILE__) . '/logo.gif', dirname(__FILE__) . '/../../img/os/' . $order_state->id . '.gif');
                copy(dirname(__FILE__) . '/logo.gif', dirname(__FILE__) . '/../../img/tmp/order_state_mini_' . $order_state->id . '.gif');
            } else {
                return false;
            }
        }

        return true;
    }

    public function installTomanCurrency()
    {
        $currency = new Currency();
        $currency->name = $this->l('Iranian Toman');
        $currency->iso_code = 'IRT';
        $currency->iso_code_num = 365;
        $currency->sign = 'تومان';
        $currency->blank = true;
        $currency->conversion_rate = 0.1;
        $currency->format = 4;
        $currency->decimals = 0;
        $currency->active = 1;

        $currency->add();

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !Configuration::deleteByName('IRANDARGAH_MERCHANT_CODE') || !Configuration::deleteByName('BAHAMTA_TEST_MODE')) {
            return false;
        }

        return true;
    }

    public function getHookController($hook_name)
    {
        require_once dirname(__FILE__) . '/controllers/hook/' . $hook_name . '.php';
        $controller_name = $this->name . $hook_name . 'Controller';
        $controller = new $controller_name($this, __FILE__, $this->_path);
        return $controller;
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $irandargah_merchant_code = strval(Tools::getValue('IRANDARGAH_MERCHANT_CODE'));
            Configuration::updateValue('IRANDARGAH_MERCHANT_CODE', $irandargah_merchant_code);
            $output .= $this->displayConfirmation($this->l('Settings updated.'));
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Merchant Code'),
                    'name' => 'IRANDARGAH_MERCHANT_CODE',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ),
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ),
        );

        // Load current value
        $helper->fields_value['IRANDARGAH_MERCHANT_CODE'] = Configuration::get('IRANDARGAH_MERCHANT_CODE');

        return $helper->generateForm($fields_form);
    }

    public function hookDisplayPayment($params)
    {
        $controller = $this->getHookController('displayPayment');

        return $controller->run($params);
    }

    public function hookPaymentReturn($params)
    {
        $controller = $this->getHookController('displayPaymentReturn');

        return $controller->run($params);
    }

    public function hookDisplayOverrideTemplate($params)
    {
        if (session_id() == '') {
            session_start();
        }

        if (isset($_SESSION['irandargah_error'])) {
            $this->context->controller->errors[] = $this->getErrorMessage($_SESSION['irandargah_error']);
            unset($_SESSION['irandargah_error']);
        }
    }

    public function getErrorMessage($error_key)
    {
        if (isset($this->error_messages[$error_key])) {
            return $this->error_messages[$error_key];
        } else {
            return $error_key;
        }

    }
}
