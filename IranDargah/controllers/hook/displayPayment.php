<?php

class IranDargahDisplayPaymentController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        if ($this->module) {
            $this->context->controller->addCSS($this->_path . 'views/css/irandargah.css', 'all');
            return $this->module->display($this->file, 'displayPayment.tpl');
        }
    }
}
