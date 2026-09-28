{**
 * m4pverifyorder
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

{extends file='page.tpl'}

{block name='page_content'}
    <h1>{l s='Order approved' d='Modules.M4pverifyorder.Shop'}</h1>
    {if $m4pverifyorder_reference}
        <p>{l s='Order %reference% has been approved and is now being processed.' d='Modules.M4pverifyorder.Shop' sprintf=['%reference%' => $m4pverifyorder_reference]}</p>
    {else}
        <p>{l s='The order has been approved and is now being processed.' d='Modules.M4pverifyorder.Shop'}</p>
    {/if}
{/block}
