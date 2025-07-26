<?php

class M4pverifyorderSendVerifyModuleFrontController extends ModuleFrontController
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
            || Tools::getValue('token') != md5(_COOKIE_KEY_ . '_send_email_verify')
        ) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
            ]);
            die();
        }

        if (!$this->context->customer->isLogged()) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Brak autoryzacji']));
        }

        $id_customer = (int)$this->context->customer->id;
        $customer = new Customer($id_customer);
        $cart = $this->context->cart;
        $products = $cart->getProducts();

        $productRows = '';
        foreach ($products as $product) {
            $productName = Product::getProductName(
                $product['id_product'],
                $product['id_product_attribute']
                
            );
            $unitPrice = Tools::displayPrice($product['price']);
            $totalPrice = Tools::displayPrice($product['price'] * $product['cart_quantity']);
            $productRows .= '<tr>';
            $productRows .= '<td>' . htmlspecialchars($productName) . '</td>';
            $productRows .= '<td>' . $unitPrice . '</td>';
            $productRows .= '<td>' . (int)$product['cart_quantity'] . '</td>';
            $productRows .= '<td>' . $totalPrice . '</td>';
            $productRows .= '</tr>';
        }        

        // Stały token weryfikacyjny
        $token = md5(_COOKIE_KEY_ . '_verify_order_' . $this->context->cart->id);
        $verifyUrl = $this->context->link->getModuleLink(
            'm4pverifyorder',
            'verify',
            [
                'token' => $token,
                'id_customer' => $this->context->customer->id,
                'id_cart' => $this->context->cart->id,
            ]
        );

        $mailVars = [
            '{customer_name}' => $customer->firstname . ' ' . $customer->lastname,
            '{product_table_rows}' => $productRows,
            '{verify_link}' => $verifyUrl
        ];        

        $mailSent = Mail::Send(
            (int)$this->context->language->id,
            'verify_email',
            'Potwierdzenie weryfikacji zamówienia',
            $mailVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null, null, null, null,
            _PS_MODULE_DIR_ . 'm4pverifyorder/mails/',
            false,
            $this->context->shop->id
        );

        if ($mailSent) {
            $this->ajaxDie(json_encode(['success' => true]));
        } else {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Nie udało się wysłać maila']));
        }
    }
}
