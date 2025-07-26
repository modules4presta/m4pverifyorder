{if isset($cart)}
    <div class="m4p-request-approval" style="margin-top: 20px;">
        <form action="{$link->getModuleLink('m4pverifyorder', 'request', [], true)|escape:'htmlall':'UTF-8'}" method="post">
            <input type="hidden" name="id_cart" value="{$cart->id}" />
            <button type="submit" class="btn btn-primary">
                {l s='Wyślij prośbę o zatwierdzenie zamówienia' mod='m4pverifyorder'}
            </button>
        </form>
    </div>
{/if}
