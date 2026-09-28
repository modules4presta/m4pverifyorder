{**
 * m4pverifyorder
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

{extends file='page.tpl'}

{block name='page_content'}
    <h1>{l s='This approval link no longer works' d='Modules.M4pverifyorder.Shop'}</h1>
    <p>{l s='The link has expired, the order was already approved, or the address was mistyped.' d='Modules.M4pverifyorder.Shop'}</p>
    <a href="{$link->getPageLink('contact')}" class="btn btn-primary">{l s='Contact us' d='Modules.M4pverifyorder.Shop'}</a>
{/block}
