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

/**
 * Saves the per-customer approval settings. Being an admin controller, it runs
 * behind the employee session and the back office token.
 */
class AdminM4pVerifyOrderController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (!Tools::isSubmit('submitM4pVerifyOrderCustomer')) {
            return parent::postProcess();
        }

        $idCustomer = (int) Tools::getValue('id_customer');
        $customer = new Customer($idCustomer);

        if (!Validate::isLoadedObject($customer)) {
            $this->errors[] = $this->trans('Unknown customer.', [], 'Modules.M4pverifyorder.Admin');

            return false;
        }

        if (!$this->access('edit')) {
            $this->errors[] = $this->trans('You do not have permission to edit this.', [], 'Admin.Notifications.Error');

            return false;
        }

        $enabled = (bool) Tools::getValue('m4pverifyorder_enabled');
        $email = trim((string) Tools::getValue('m4pverifyorder_email'));

        if ($enabled && !Validate::isEmail($email)) {
            $this->errors[] = $this->trans('Enter a valid e-mail address for the person approving the orders.', [], 'Modules.M4pverifyorder.Admin');

            return false;
        }

        $this->module->saveCustomerSettings($idCustomer, $enabled, $email);

        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminCustomers', true, [], ['id_customer' => $idCustomer, 'viewcustomer' => 1, 'conf' => 4])
        );
    }
}
