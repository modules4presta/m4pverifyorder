<?php

class M4pverifyorderSaveFieldsModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        $this->context = Context::getContext();

        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        if (
            empty(Tools::getValue('token'))
            || Tools::getValue('token') != md5(_COOKIE_KEY_ . '_customer_email_verify')
        ) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
            ]);
            die();
        }

        $checkbox = Tools::getValue('m4p_checkbox') ? 1 : 0;
        $email = pSQL(Tools::getValue('m4p_email'));
        $idCustomer = pSQL(Tools::getValue('id_customer')) ?? 0;

        Db::getInstance()->execute("
            UPDATE "._DB_PREFIX_."customer
            SET m4p_checkbox = $checkbox, m4p_email = '$email'
            WHERE id_customer = $idCustomer
        ");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
        ]);
        die();
    }
}
