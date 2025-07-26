<?php

class M4pverifyorderVerifyModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        $this->module = Module::getInstanceByName('m4pverifyorder');
        $this->context = Context::getContext();

        parent::__construct();
    }

    private function sendEmailEmailIsVerified($idCustomer)
    {
        $customer = new Customer($idCustomer);

        $mailVars = [
            '{name}' => $customer->firstname . ' ' . $customer->lastname,
        ];

        $mailSent = Mail::Send(
            (int)$this->context->language->id,
            'verified_order_email',
            'Zamówienie potwierdzone',
            $mailVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'm4pverifyorder/mails/',
            false,
            $this->context->shop->id
        );
    }

    private function getOrderByCartId($idCart)
    {
        return Db::getInstance()->getValue("SELECT id_order FROM " . _DB_PREFIX_ . "orders
            WHERE id_cart = " . pSQL($idCart));
    }

    public function initContent()
    {
        parent::initContent();

        $token = Tools::getValue('token');
        $idCustomer = Tools::getValue('id_customer') ?? 0;
        $idCart = Tools::getValue('id_cart') ?? 0;
        $expectedToken = md5(_COOKIE_KEY_ . '_verify_order_' . $idCart);

        if (
            empty($token)
            || empty($idCustomer)
            || empty($idCart)
            || $token !== $expectedToken
        ) {
            $this->setTemplate('module:m4pverifyorder/views/templates/front/verify_error.tpl');
            return;
        }

        $idOrder = $this->getOrderByCartId($idCart);

        $result = Db::getInstance()->update('customer', [
            'm4p_order_verified' => 1
        ], 'id_customer = ' . $idCustomer);

        if ($result) {
            $this->sendEmailEmailIsVerified($idCustomer);
            $this->module->changeOrderState($idOrder, $this->module::ORDER_VERIFIED_STATE);

            $this->setTemplate('module:m4pverifyorder/views/templates/front/verify_success.tpl');
        } else {
            $this->setTemplate('module:m4pverifyorder/views/templates/front/verify_error.tpl');
        }
    }
}
