define(['jquery'], function($) {
    return {
        init: function() {
            $('#id_add_rules_button').on('click', function() {
                $('input[name="add_rules_value"]').val(true);
                this.form.submit();
            });
        }
    };
});
