(function ($) {
  Drupal.behaviors.pins_folder_hs = {
    attach: function (context, settings) {
      $(document).ajaxComplete(
        function (event, xhr, settings) {
          if (xhr.status === 200) {
            var response = xhr.responseText;
            if (response.includes('edit-field-kl-doc-folder-wrapper')) {
              var tabButtonValue = 2;
              var tabButton = jQuery('[data-horizontaltabbutton="' + tabButtonValue + '"] a');
              if (tabButton.length) {
                tabButton.trigger('click');
              }
            }
          }
        }
      );
    }
  } 
})(jQuery);

