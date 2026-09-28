<?php

/**
 * m4pverifyorder
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class M4pVerifyOrder extends Module
{
    const STATE_PENDING = 'M4PVERIFYORDER_STATE_PENDING';
    const STATE_APPROVED = 'M4PVERIFYORDER_STATE_APPROVED';
    const LINK_DAYS = 'M4PVERIFYORDER_LINK_DAYS';

    public function __construct()
    {
        $this->name = 'm4pverifyorder';
        $this->tab = 'checkout';
        $this->version = '2.0.0';
        $this->author = 'Modules4Presta';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Order approval', [], 'Modules.M4pverifyorder.Admin');
        $this->description = $this->trans('Holds an order until the person responsible at the customer company approves it by e-mail.', [], 'Modules.M4pverifyorder.Admin');
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->installOrderStates()
            && Configuration::updateValue(self::LINK_DAYS, 7)
            && $this->registerHook('displayAdminCustomers')
            && $this->registerHook('actionValidateOrderAfter')
            && $this->installTab();
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->disableOrderStates();

        Configuration::deleteByName(self::LINK_DAYS);
        Configuration::deleteByName(self::STATE_PENDING);
        Configuration::deleteByName(self::STATE_APPROVED);

        return $this->uninstallDb() && parent::uninstall();
    }

    protected function installDb()
    {
        $settings = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'm4pverifyorder_customer` (
            `id_customer` INT(10) UNSIGNED NOT NULL,
            `enabled` TINYINT(1) NOT NULL DEFAULT 0,
            `email` VARCHAR(255) NOT NULL DEFAULT "",
            PRIMARY KEY (`id_customer`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $approvals = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'm4pverifyorder_approval` (
            `id_order` INT(10) UNSIGNED NOT NULL,
            `id_customer` INT(10) UNSIGNED NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `token` CHAR(64) NOT NULL,
            `approved` TINYINT(1) NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_expiry` DATETIME NOT NULL,
            `date_approved` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id_order`),
            UNIQUE KEY `token` (`token`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return (bool) Db::getInstance()->execute($settings) && (bool) Db::getInstance()->execute($approvals);
    }

    protected function uninstallDb()
    {
        return (bool) Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'm4pverifyorder_customer`')
            && (bool) Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'm4pverifyorder_approval`');
    }

    /**
     * The module brings its own order states instead of assuming which ids a
     * shop happens to have.
     */
    protected function installOrderStates()
    {
        $states = [
            self::STATE_PENDING => [
                'en' => 'Waiting for approval',
                'pl' => 'Oczekuje na zatwierdzenie',
                'color' => '#FF8C00',
                'logable' => false,
            ],
            self::STATE_APPROVED => [
                'en' => 'Approved by the customer company',
                'pl' => 'Zatwierdzone przez firmę klienta',
                'color' => '#32CD32',
                'logable' => true,
            ],
        ];

        foreach ($states as $key => $data) {
            $existing = (int) Configuration::get($key);
            if ($existing && Validate::isLoadedObject(new OrderState($existing))) {
                continue;
            }

            $state = new OrderState();
            $state->color = $data['color'];
            $state->send_email = false;
            $state->invoice = false;
            $state->logable = $data['logable'];
            $state->hidden = false;
            $state->delivery = false;
            $state->shipped = false;
            $state->paid = false;
            $state->module_name = $this->name;
            $state->name = [];

            foreach (Language::getLanguages(false) as $language) {
                $iso = Tools::strtolower($language['iso_code']);
                $state->name[(int) $language['id_lang']] = $data[$iso] ?? $data['en'];
            }

            if (!$state->add()) {
                return false;
            }

            Configuration::updateValue($key, (int) $state->id);
        }

        return true;
    }

    /**
     * Orders keep their history, so the states are hidden rather than removed.
     */
    protected function disableOrderStates()
    {
        foreach ([self::STATE_PENDING, self::STATE_APPROVED] as $key) {
            $state = new OrderState((int) Configuration::get($key));
            if (Validate::isLoadedObject($state)) {
                $state->deleted = true;
                $state->save();
            }
        }
    }

    protected function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminM4pVerifyOrder';
        $tab->module = $this->name;
        $tab->id_parent = -1;
        $tab->active = true;
        $tab->name = [];

        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = 'Order approval';
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminM4pVerifyOrder');
        if (!$idTab) {
            return true;
        }

        $tab = new Tab($idTab);

        return (bool) $tab->delete();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitM4pVerifyOrder')) {
            $days = (int) Tools::getValue(self::LINK_DAYS);
            if ($days < 1 || $days > 365) {
                $output .= $this->displayError($this->trans('The link has to stay valid between 1 and 365 days.', [], 'Modules.M4pverifyorder.Admin'));
            } else {
                Configuration::updateValue(self::LINK_DAYS, $days);
                $output .= $this->displayConfirmation($this->trans('Settings updated.', [], 'Modules.M4pverifyorder.Admin'));
            }
        }

        $form = [
            'form' => [
                'legend' => ['title' => $this->trans('Approval settings', [], 'Modules.M4pverifyorder.Admin')],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Days the approval link stays valid', [], 'Modules.M4pverifyorder.Admin'),
                        'desc' => $this->trans('After that, the link stops working and the order has to be approved in the back office.', [], 'Modules.M4pverifyorder.Admin'),
                        'name' => self::LINK_DAYS,
                        'required' => true,
                    ],
                ],
                'submit' => ['title' => $this->trans('Save', [], 'Modules.M4pverifyorder.Admin')],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitM4pVerifyOrder';
        $helper->fields_value = [self::LINK_DAYS => (int) Configuration::get(self::LINK_DAYS)];

        return $output . $helper->generateForm([$form]);
    }

    /**
     * Read-only panel on the customer page; saving goes through the module's
     * own admin controller, which requires an employee session.
     */
    public function hookDisplayAdminCustomers($params)
    {
        $idCustomer = (int) $params['id_customer'];
        $settings = $this->getCustomerSettings($idCustomer);

        $this->context->smarty->assign([
            'm4pverifyorder_id_customer' => $idCustomer,
            'm4pverifyorder_enabled' => (bool) $settings['enabled'],
            'm4pverifyorder_email' => $settings['email'],
            'm4pverifyorder_action' => $this->context->link->getAdminLink('AdminM4pVerifyOrder'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/customer_panel.tpl');
    }

    public function hookActionValidateOrderAfter($params)
    {
        $order = $params['order'] ?? null;
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        $settings = $this->getCustomerSettings((int) $order->id_customer);
        if (!$settings['enabled'] || !Validate::isEmail($settings['email'])) {
            return;
        }

        $days = max(1, (int) Configuration::get(self::LINK_DAYS));
        $token = bin2hex(random_bytes(32));

        $stored = Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'm4pverifyorder_approval`
            (`id_order`, `id_customer`, `email`, `token`, `approved`, `date_add`, `date_expiry`)
            VALUES (' . (int) $order->id . ', ' . (int) $order->id_customer . ', "' . pSQL($settings['email']) . '", "'
            . pSQL($token) . '", 0, NOW(), DATE_ADD(NOW(), INTERVAL ' . $days . ' DAY))'
        );

        if (!$stored) {
            return;
        }

        $this->changeOrderState((int) $order->id, (int) Configuration::get(self::STATE_PENDING));
        $this->sendApprovalRequest($order, $settings['email'], $token);
    }

    /**
     * @return array|null null when the token is unknown, already used or expired
     */
    public function findPendingApproval(string $token)
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'm4pverifyorder_approval`
            WHERE `token` = "' . pSQL($token) . '" AND `approved` = 0 AND `date_expiry` >= NOW()'
        );

        return $row ?: null;
    }

    public function approve(array $approval): bool
    {
        $done = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'm4pverifyorder_approval`
            SET `approved` = 1, `date_approved` = NOW()
            WHERE `id_order` = ' . (int) $approval['id_order'] . ' AND `approved` = 0'
        );

        if (!$done || !Db::getInstance()->Affected_Rows()) {
            return false;
        }

        $this->changeOrderState((int) $approval['id_order'], (int) Configuration::get(self::STATE_APPROVED));
        $this->sendApprovalConfirmation((int) $approval['id_customer'], (int) $approval['id_order']);

        return true;
    }

    public function getCustomerSettings(int $idCustomer): array
    {
        $row = Db::getInstance()->getRow(
            'SELECT `enabled`, `email` FROM `' . _DB_PREFIX_ . 'm4pverifyorder_customer` WHERE `id_customer` = ' . $idCustomer
        );

        return [
            'enabled' => (bool) ($row['enabled'] ?? false),
            'email' => (string) ($row['email'] ?? ''),
        ];
    }

    public function saveCustomerSettings(int $idCustomer, bool $enabled, string $email): bool
    {
        return (bool) Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'm4pverifyorder_customer` (`id_customer`, `enabled`, `email`)
            VALUES (' . $idCustomer . ', ' . (int) $enabled . ', "' . pSQL($email) . '")
            ON DUPLICATE KEY UPDATE `enabled` = VALUES(`enabled`), `email` = VALUES(`email`)'
        );
    }

    private function changeOrderState(int $idOrder, int $idState): void
    {
        if (!$idState || !Validate::isLoadedObject(new OrderState($idState))) {
            return;
        }

        $history = new OrderHistory();
        $history->id_order = $idOrder;
        $history->changeIdOrderState($idState, $idOrder);
        $history->addWithemail();
    }

    private function sendApprovalRequest(Order $order, string $email, string $token): void
    {
        $customer = new Customer((int) $order->id_customer);
        $link = $this->context->link->getModuleLink($this->name, 'verify', ['token' => $token], true);

        Mail::Send(
            (int) $order->id_lang,
            'verify_order',
            $this->trans('An order is waiting for your approval', [], 'Modules.M4pverifyorder.Admin'),
            [
                '{customer_name}' => $customer->firstname . ' ' . $customer->lastname,
                '{order_reference}' => $order->reference,
                '{order_total}' => $this->formatPrice((float) $order->total_paid, (int) $order->id_currency),
                '{product_table_rows}' => $this->buildProductRows($order),
                '{verify_link}' => $link,
                '{shop_name}' => (string) Configuration::get('PS_SHOP_NAME'),
            ],
            $email,
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__) . '/mails/',
            false,
            (int) $order->id_shop
        );
    }

    private function sendApprovalConfirmation(int $idCustomer, int $idOrder): void
    {
        $customer = new Customer($idCustomer);
        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            return;
        }

        Mail::Send(
            (int) $order->id_lang,
            'order_approved',
            $this->trans('Your order has been approved', [], 'Modules.M4pverifyorder.Admin'),
            [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{order_reference}' => $order->reference,
                '{shop_name}' => (string) Configuration::get('PS_SHOP_NAME'),
            ],
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            dirname(__FILE__) . '/mails/',
            false,
            (int) $order->id_shop
        );
    }

    private function buildProductRows(Order $order): string
    {
        $rows = '';

        foreach ($order->getProducts() as $product) {
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars((string) $product['product_name'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($this->formatPrice((float) $product['unit_price_tax_incl'], (int) $order->id_currency), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . (int) $product['product_quantity'] . '</td>'
                . '<td>' . htmlspecialchars($this->formatPrice((float) $product['total_price_tax_incl'], (int) $order->id_currency), ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }

        return $rows;
    }

    /**
     * The order hook also runs from the back office and the webservice, where
     * the context has no locale, so it is fetched rather than assumed.
     */
    private function formatPrice(float $price, int $idCurrency): string
    {
        $currency = new Currency($idCurrency);
        $locale = $this->context->currentLocale;

        if ($locale === null && method_exists($this->context, 'getCurrentLocale')) {
            $locale = $this->context->getCurrentLocale();
        }

        if ($locale === null) {
            return Tools::ps_round($price, 2) . ' ' . $currency->iso_code;
        }

        return $locale->formatPrice($price, $currency->iso_code);
    }
}
