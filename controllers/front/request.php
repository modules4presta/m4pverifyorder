<?php

class M4pVerifyOrderRequestModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        $id_cart = (int) $cart->id;

        if (!$id_cart) {
            Tools::redirect('index.php?controller=cart');
        }

        $checksum = $this->module->calculateCartChecksum($id_cart);

        $existing = Db::getInstance()->getRow('
            SELECT id_cart FROM '._DB_PREFIX_.'m4p_cart_verification
            WHERE id_cart = '.(int)$id_cart
        );

        if ($existing) {
            Db::getInstance()->update('m4p_cart_verification', [
                'verified' => 0,
                'checksum' => pSQL($checksum),
                'requested' => 1
            ], 'id_cart = '.(int)$id_cart);
        } else {
            Db::getInstance()->insert('m4p_cart_verification', [
                'id_cart' => (int)$id_cart,
                'verified' => 0,
                'checksum' => pSQL($checksum),
                'requested' => 1
            ]);
        }

        $this->context->controller->success[] = $this->module->l('Your request has been sent. Please wait for the administrator to approve it.');
        Tools::redirect('index.php?controller=cart');
    }
}
