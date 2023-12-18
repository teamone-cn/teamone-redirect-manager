// function to show dependent dropdowns for "Site Display" field.
function redirect_confirm_delete()
{
    return confirm("Are you sure you want to delete this redirect rule?");
}

// Toggle switch
jQuery('.nnr-switch input').on(
    'click', function () {
        var t = jQuery(this),
            togvalue = t.is(':checked') ? 'on' : 'off',
            scriptid = t.data('id'),
            security = jQuery('#_wpnonce').val(),
            data = {
                toggle: true,
                id: scriptid,
                togvalue: togvalue,
                security: security,
            };
        jQuery.post(
            window.location.href,
            data
        );
    }
);

// All check

jQuery('#re-allcheck').on(
    'click', function () {
        var checkboxValue = this.checked;
        var checkboxes = document.querySelectorAll("input[type='checkbox']");
         checkboxes.forEach(function(checkbox) {
          if (checkboxValue) {
            checkbox.checked = true;
          } else {
            checkbox.checked = false;
          }
        });
    }
);

