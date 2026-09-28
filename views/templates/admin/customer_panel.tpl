{**
 * m4pverifyorder
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div class="card mt-2">
    <h3 class="card-header">
        <i class="material-icons">assignment_turned_in</i>
        {l s='Order approval' d='Modules.M4pverifyorder.Admin'}
    </h3>
    <div class="card-body">
        <form method="post" action="{$m4pverifyorder_action|escape:'html':'UTF-8'}">
            <input type="hidden" name="id_customer" value="{$m4pverifyorder_id_customer|intval}">

            <div class="form-group">
                <label class="form-check-label">
                    <input type="checkbox" name="m4pverifyorder_enabled" value="1" {if $m4pverifyorder_enabled}checked{/if}>
                    {l s='Orders from this customer need approval' d='Modules.M4pverifyorder.Admin'}
                </label>
            </div>

            <div class="form-group">
                <label for="m4pverifyorder_email">{l s='E-mail of the person approving them' d='Modules.M4pverifyorder.Admin'}</label>
                <input type="email" class="form-control" id="m4pverifyorder_email" name="m4pverifyorder_email"
                       value="{$m4pverifyorder_email|escape:'html':'UTF-8'}">
            </div>

            <button type="submit" name="submitM4pVerifyOrderCustomer" class="btn btn-primary">
                {l s='Save' d='Modules.M4pverifyorder.Admin'}
            </button>
        </form>
    </div>
</div>
