{extends file='page.tpl'}
{block name='page_content'}
  <h1>Błąd weryfikacji</h1>
  <p>Nieprawidłowy lub nieautoryzowany token weryfikacyjny.</p>
  <a href="{$link->getPageLink('contact')}" class="btn btn-outline-danger mt-3">Skontaktuj się z nami</a>
{/block}
