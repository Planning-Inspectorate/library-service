(function (Drupal, once) {

  Drupal.behaviors.folderAltTitle = {
    attach: function (context) {

      once('folder-alt-title', context).forEach(() => {

        document.addEventListener('change', function (e) {

          // ✅ Only react to CSHS levels
          if (!e.target.id.includes('edit-field-kl-doc-folder-0-target-id')) {
            return;
          }

          // ✅ Get all levels
          const selects = document.querySelectorAll('[id^="edit-field-kl-doc-folder-0-target-id--level"]');

          let finalValue = null;

          selects.forEach(select => {
            if (select.value && !isNaN(select.value)) {
              finalValue = select.value;
            }
          });

          if (!finalValue) return;

          fetch(`/pins-folder-alt-title/${finalValue}`)
            .then(res => res.json())
            .then(data => {

              const altField = document.querySelector('[name="field_alternative_title[0][value]"]');
              if (!altField) return;

              // ✅ ALWAYS overwrite
              altField.value = data.alternative_title || '';

            })
            .catch(err => console.error('Fetch error:', err));

        });

      });

    }
  };

})(Drupal, once);
