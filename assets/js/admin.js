jQuery(document).ready(function ($) {
    var submit = $('#submit'); // Replace with your form's selector

    submit.on('click', function (e) {
        var allFieldsValid = true;

        // Remove existing warnings
        $('.acf-warning').remove();

        // Iterate over each required field
        $('.acf-field.is-required').each(function () {
            var field = $(this);
            var input = field.find('input, textarea, select');

            if (input.length === 0 || !input.val() || !input.val().trim()) {
                // Field is empty - show warning and prevent form submission
                allFieldsValid = false;
                var warning = $('<div class="acf-notice -error acf-error-message"><p>This is a required field.</p></div>');
                field.prepend(warning);
            }

            $(input).on('change', function () {
                $(field).find('.acf-notice').remove();
            });
        });

        if (!allFieldsValid) {
            submit.attr('disabled', 'true');
            submit.attr('value', 'All required fields must be set');
            e.preventDefault(); // Prevent form submission
        } else {
            submit.removeAttr('disabled');
            submit.attr('value', 'Save Changes');
        }
    });

    submit.on('mouseleave', function (e) {
        submit.removeAttr('disabled');
        submit.attr('value', 'Save Changes');
    });
});


jQuery(document).ready(function($) {
    // Select the Shortname field row
    var shortnameRow = $('#shortname_field').closest('tr');

    // Find the Site Title field row
    var siteTitleRow = $('#blogname').closest('tr');

    // Insert the Shortname field after the Site Title field
    if (shortnameRow.length && siteTitleRow.length) {
        shortnameRow.insertAfter(siteTitleRow);
    }
});

jQuery(document).ready(function($) {
    // Modify the label within the row for the 'shortname' field
    $("tr:has(#shortname_field)").find('th[scope="row"]').html("<label for='shortname_field'>Site Short Name</label>");
});