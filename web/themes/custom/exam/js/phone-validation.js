(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.phoneNumberValidation = {
    attach: function (context, settings) {
      once('phoneNumberValidation', '#edit-field-mobile-number-0-value--2', context).forEach(function (element) {
        // Set the pattern attribute for HTML5 validation
        element.setAttribute('pattern', '\\+[0-9]{3}\\s[0-9]{3}\\s[0-9]+');
        element.setAttribute('title', 'Please enter a valid phone number in the format: +XXX XXX XXXX...');
        element.setAttribute('placeholder', '+XXX XXX XXXX');

        $(element).on('input', function (event) {
          var input = this.value.replace(/\D/g, '').substring(0, 15); // Strip non-digits and limit to 15 digits
          var formatted = '';

          if (input.length > 0) {
            formatted += '+' + input.substring(0, 3);
            if (input.length > 3) {
              formatted += ' ' + input.substring(3, 6);
              if (input.length > 6) {
                formatted += ' ' + input.substring(6);
              }
            }
          }

          this.value = formatted;
        });

        // Custom validation on form submit
        $(element).closest('form').once('phoneNumberSubmitValidation').on('submit', function (event) {
          var phoneInput = $('#edit-field-mobile-number-0-value--2', this)[0];
          var phoneNumber = phoneInput.value.replace(/\D/g, '');

          if (phoneNumber.length < 8) { // Assuming minimum 8 digits (including country code)
            event.preventDefault();
            phoneInput.setCustomValidity('Please enter a valid phone number with at least 8 digits.');
            phoneInput.reportValidity();
          } else {
            phoneInput.setCustomValidity('');
          }
        });
      });
    }
  };
})(jQuery, Drupal);
