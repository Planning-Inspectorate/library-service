(function ($, Drupal) {
  Drupal.behaviors.pins = {
    attach: function (context, settings) {

      function hardcopy_autocomplete_onchange() {

        if(jQuery('.node-issued-hard-copy-edit-form').is(':visible')){
          jQuery('#edit-field-content-reference-0-value').attr('disabled', true);
          jQuery('#edit-title-0-value').attr('disabled', true);
          jQuery('#edit-field-hard-copy-0-target-id').attr('disabled', true);
        }

        var pdf_edit = jQuery('.node-pdf-edit-form');
        var hardcopy_edit = jQuery('.node-hard-copy-edit-form');
        var article_edit = jQuery('.node-article-edit-form');

        var pdf_fm = jQuery('.node-pdf-form');
        var hardcopy_fm = jQuery('.node-hard-copy-form');
        var article_fm = jQuery('.node-article-form');

        if(pdf_edit.is(':visible') || hardcopy_edit.is(':visible') || article_edit.is(':visible')){
          $reference_id = jQuery('#edit-field-content-reference-0-value').val();
          alert($reference_id);
          if($reference_id.length > 0){
            jQuery('#edit-field-content-reference-0-value').attr('disabled', true);
          }
        }

        if(pdf_fm.is(':visible') || hardcopy_fm.is(':visible') || article_fm.is(':visible')){
          jQuery('#edit-field-content-reference-0-value').attr('disabled', true);
        }
         

        if (jQuery('.node-issued-hard-copy-form').is(':visible') ) {
          jQuery('.field--name-field-content-reference').hide();
          jQuery('.field--name-title').hide();
  
          
          jQuery('#edit-field-hard-copy-0-target-id').trigger("change");

          jQuery('input#edit-field-hard-copy-0-target-id').bind({
            mouseup: function () {
              save_autocomplete_data();
            },
            change: function () {
              save_autocomplete_data();
            },
            keypress: function (e) {
              if (e.which == 13) {
                $(this).trigger('blur');
                save_autocomplete_data();
              }
            },

            blur: function () {
              $reference_data = $.trim(jQuery('#edit-field-hard-copy-0-target-id').attr('value'));
              save_autocomplete_data();
            }
          });
        }
      }

      function save_autocomplete_data() {
        $reference_data = $.trim(jQuery('#edit-field-hard-copy-0-target-id').val());
        $issued_hard_copy_value_text = $.trim(jQuery('#edit-field-hard-copy-0-target-id').text());

        if ($reference_data.length > 0) {
          var data = $reference_data.split('|');
          jQuery('#edit-title-0-value').attr('value', data[0] +' - '+data[1]);
          jQuery('#edit-field-content-reference-0-value').attr('value', data[1]);
        }
      }

      hardcopy_autocomplete_onchange();


      // if ($('.node-issued_hard_copy-edit-form').is(':visible')) {
      //   // jQuery('.node-issued_hard_copy-edit-form .field--name-title').hide(); 
        jQuery('.node-issued_hard_copy-edit-form #edit-field-content-reference-0-value').attr('disabled', true);
        jQuery('.node-issued_hard_copy-edit-form #edit-title-0-value').attr('disabled', true);    
      // }

      // if (jQuery('.node-issued-hard-copy-form').is(':visible')) {

      //   //  jQuery('.node-issued-hard-copy-form #edit-field-content-reference-0-value').attr('disabled', true);
      //   //  jQuery('.node-issued-hard-copy-form #edit-title-0-value').attr('disabled', true);
      //   //prepopulate issued_hard_copy title with te issued_hard_copy
      //   // jQuery('.node-issued-hard-copy-form .field--name-title').hide();
      //   var hc = jQuery('.node-issued-hard-copy-form #edit-field-hard-copy-0-target-id');
      //   var hc_value = hc.attr('value');

      //   hc.autocomplete({
      //     source: models, select: function (event, ui) {
      //       alert('flippy');
      //     }
      //   });

      //   hc.change(function () {
      //     var issued_hard_copy_value = $(this).attr('value');
      //     var issued_hard_copy_value_text = $(this).text();
      //     alert(issued_hard_copy_value_text);

      //     if (issued_hard_copy_value == '_none' || issued_hard_copy_value == '' || issued_hard_copy_value == 'undefined') {

      //     }
      //     else {
      //       var issued_hard_copy_ref = issued_hard_copy_value.split("|");
      //       var issued_hard_copy_values = issued_hard_copy_value_text.split(" | ");

      //       hard_copy_title = issued_hard_copy_values[0] + ' - ' + issued_hard_copy_values[1];
      //       hard_copy_id = issued_hard_copy_values[1];

      //       jQuery('.node-issued-hard-copy-form .field--name-title #edit-title-0-value').attr('value', hard_copy_title);
      //       jQuery('.node-issued-hard-copy-form  #edit-field-content-reference-0-value').attr('value', hard_copy_id);

      //     }
      //   });
      // }



    }
  }
})(jQuery, Drupal);