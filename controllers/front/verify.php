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
 * Opens the approval link from the e-mail. The token alone identifies the
 * order, so nothing is taken from the URL beyond it.
 */
class M4pVerifyOrderVerifyModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $approval = $this->module->findPendingApproval((string) Tools::getValue('token'));

        if ($approval === null || !$this->module->approve($approval)) {
            $this->setTemplate('module:m4pverifyorder/views/templates/front/verify_error.tpl');

            return;
        }

        $order = new Order((int) $approval['id_order']);
        $this->context->smarty->assign([
            'm4pverifyorder_reference' => Validate::isLoadedObject($order) ? $order->reference : '',
        ]);

        $this->setTemplate('module:m4pverifyorder/views/templates/front/verify_success.tpl');
    }
}
