<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class M4pverifyorder extends Module
{
    const TO_VERIFY_ORDER_STATE = 1;
    const ORDER_VERIFIED_STATE = 2;

    public function __construct()
    {
        $this->name = 'm4pverifyorder';
        $this->version = '1.0.0';
        $this->author = 'Modules4Presta.io';
        $this->tab = 'front_office_features';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->context = Context::getContext();

        parent::__construct();

        $this->displayName = $this->l('M4P Verify Order');
        $this->description = $this->l('Dodaje checkbox i email do konta klienta w panelu Moje Konto.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayAdminCustomers')
            && $this->registerHook('actionValidateOrderAfter')
            && $this->alterCustomerTable();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    protected function alterCustomerTable()
    {
        return Db::getInstance()->execute('
            ALTER TABLE `'._DB_PREFIX_.'customer`
            ADD IF NOT EXISTS `m4p_checkbox` TINYINT(1) DEFAULT 0,
            ADD IF NOT EXISTS `m4p_email` VARCHAR(255) DEFAULT NULL,
            ADD IF NOT EXISTS `m4p_order_verified` TINYINT(1) DEFAULT 0
        ');
    }

    public function hookDisplayAdminCustomers($params)
    {
        $id_customer = (int) $params['id_customer'];
        $row = Db::getInstance()->getRow(
            "SELECT m4p_checkbox, m4p_email FROM "._DB_PREFIX_."customer
            WHERE id_customer = " . pSQL($id_customer)
        );

        $this->context->smarty->assign([
            'm4p_id_customer' => $id_customer,
            'm4p_checkbox' => (bool)$row['m4p_checkbox'],
            'm4p_email' => $row['m4p_email'],
            'm4p_action_url' => $this->context->link->getModuleLink(
                $this->name,
                'savefields',
                [
                    'token' => md5(_COOKIE_KEY_ . '_customer_email_verify')
                ]
            )
        ]);

        return $this->display(__FILE__, 'views/templates/admin/my-account.tpl');
    }

    public function changeOrderState($idOrder, $idOrderStatus)
    {
        $orderHistory = (new OrderHistory)->changeIdOrderState(
            (int) $idOrderStatus,
            (int) $idOrder
        );

        /*
        $sql = "UPDATE " . _DB_PREFIX_ . "orders
            SET current_state = " . pSQL($idOrderStatus) . "
            WHERE id_order = " . pSQL($idOrder);

        return Db::getInstance()->execute($sql);
        */
    }

    private function checkExistOfRequiredOrderAccept($idCustomer)
    {
        $customerCustomData = Db::getInstance()->getRow("SELECT m4p_checkbox, m4p_email FROM " . _DB_PREFIX_ . "customer
            WHERE id_customer = " . pSQL($idCustomer)
        );

        return $customerCustomData;
    }

    public function hookActionValidateOrderAfter($params)
    {
        $id_customer = (int)$params['cart']->id_customer;

        $customerCustomData = $this->checkExistOfRequiredOrderAccept($id_customer);

        if (
            empty($params['order']->id)
            || empty($customerCustomData['m4p_checkbox'])
            || empty($customerCustomData['m4p_email'])
        ) {
            return;
        }

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
            '{product_table_rows}' => $productRows,
            '{verify_link}' => $verifyUrl
        ];

        $mailSent = Mail::Send(
            (int)$this->context->language->id,
            'verify_email',
            'Weryfikacja zamówienia',
            $mailVars,
            $customerCustomData['m4p_email'],
            '',
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'm4pverifyorder/mails/',
            false,
            $this->context->shop->id
        );

        $this->changeOrderState($params['order']->id, self::TO_VERIFY_ORDER_STATE);
    }
}
