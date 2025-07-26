<div class="card customer-messages-card col">
    <h3 class="card-header">
        <i class="material-icons">verify</i>
        Weryfikacja zamówień przez
    </h3>
    <div class="card-body">
        <form id="m4pverifyorder-form">
            <input type="hidden" name="id" id="m4p_id_customer" value="{$m4p_id_customer}">
            <fieldset>
                <label>
                    <input type="checkbox" name="m4pverifyorder_checkbox" id="m4pverifyorder_checkbox" value="1" {if $m4p_checkbox}checked{/if}>
                    Weryfikacja włączona
                </label>

                <br><br>

                <label>
                    Email na który będą przychodzić maile z weryfikacją zamówienia
                    <input class="form-control" type="email" name="m4pverifyorder_email" id="m4pverifyorder_email" value="{$m4p_email}">
                </label>

                <br><br>

                <button type="submit" class="btn btn-primary">Zapisz</button>
            </fieldset>
        </form>
    </div>
</div>

{literal}
<script>

$(document).ready(function () {
    const m4p_ajax_url = '{/literal}{$m4p_action_url nofilter}{literal}';

  $('#m4pverifyorder-form').on('submit', function (e) {
    e.preventDefault();

    var checkbox = $('#m4pverifyorder_checkbox').is(':checked') ? 1 : 0;
    var email = $('#m4pverifyorder_email').val();
    var idCustomer = $('#m4p_id_customer').val();

    $.ajax({
      type: 'POST',
      url: m4p_ajax_url,
      data: {
        m4p_checkbox: checkbox,
        m4p_email: email,
        id_customer: idCustomer
      },
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          alert('Zapisano pomyślnie!');
        } else {
          alert('Błąd: ' + response.error);
        }
      },
      error: function () {
        alert('Wystąpił błąd połączenia z serwerem.');
      }
    });
  });
});

</script>
{/literal}
