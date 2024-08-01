(function ($, Drupal, once) {
  Drupal.behaviors.mobileNavigation = {
    attach: function (context, settings) {
      // Use Drupal's once() to ensure this runs only once per element
      once('mobileNavigation', '.mobile-buttons', context).forEach(function (burger) {
        // Get reference to the menu element
        const menu = document.getElementById('block-exam-navigation');

        // Add click event listener to the burger icon
        burger.addEventListener('click', function () {
          // Toggle 'open' class on burger icon for visual feedback
          burger.classList.toggle('open');
          // Toggle 'show' class on menu to display/hide it
          menu.classList.toggle('show');
        });
      });
    }
  };
})(jQuery, Drupal, once);
