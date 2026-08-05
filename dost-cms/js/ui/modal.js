function openFormatModal() {
  getReferenceSettings().then(function(settings) {
    querySelector('#format-prefix-input').value = settings.prefix;
    querySelector('#format-separator-input').value = settings.separator;
    querySelector('#format-padding-input').value = settings.padding;
    querySelector('#format-include-year-checkbox').checked = settings.include_year == 1;
    refreshFormatPreview();
    showElement(querySelector('#reference-format-modal'));
  });
}

function closeFormatModal() {
  hideElement(querySelector('#reference-format-modal'));
}

function refreshFormatPreview() {
  var preview = previewReferenceFormat(
    querySelector('#format-prefix-input').value,
    querySelector('#format-separator-input').value,
    querySelector('#format-padding-input').value,
    querySelector('#format-include-year-checkbox').checked
  );
  querySelector('#format-preview-display').textContent = preview;
}

querySelector('#open-format-settings-button').addEventListener('click', openFormatModal);
querySelector('#close-modal-button').addEventListener('click', closeFormatModal);
querySelector('#modal-overlay-backdrop').addEventListener('click', closeFormatModal);
querySelector('#format-prefix-input').addEventListener('input', refreshFormatPreview);
querySelector('#format-separator-input').addEventListener('input', refreshFormatPreview);
querySelector('#format-padding-input').addEventListener('input', refreshFormatPreview);
querySelector('#format-include-year-checkbox').addEventListener('change', refreshFormatPreview);

querySelector('#save-format-button').addEventListener('click', function() {
  var prefix = querySelector('#format-prefix-input').value;
  var separator = querySelector('#format-separator-input').value;
  var padding = querySelector('#format-padding-input').value;
  var includeYear = querySelector('#format-include-year-checkbox').checked;
  saveReferenceSettings(prefix, separator, padding, includeYear).then(function(data) {
    if (data.success) {
      refreshReferencePreview();
      closeFormatModal();
      showToast('Reference format updated successfully', 'success');
    } else {
      showToast('Failed to save settings: ' + (data.message || 'unknown error'), 'warn');
    }
  });
});
