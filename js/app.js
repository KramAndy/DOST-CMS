var dom = {
  typeSelector: querySelector('#document-type-selector'),
  directionSelector: querySelector('#document-direction-selector'),
  previewEl: querySelector('#reference-number-preview'),
  titleInput: querySelector('#document-title-input'),
  originInput: querySelector('#originating-office-input'),
  registerBtn: querySelector('#register-document-button')
};

function refreshReferencePreview() {
  var docType = dom.typeSelector.value;
  dom.previewEl.textContent = '—';
  generateReferenceNumber(docType).then(function(data) {
    if (data.success) {
      dom.previewEl.textContent = data.reference;
    }
  }).catch(function() {
    dom.previewEl.textContent = 'Error';
  });
}

dom.typeSelector.addEventListener('change', refreshReferencePreview);

dom.registerBtn.addEventListener('click', function() {
  var title = dom.titleInput.value.trim();
  var origin = dom.originInput.value.trim();
  if (!title || !origin) {
    showToast('Please provide a title and originating office', 'warn');
    return;
  }
  registerDocument(dom.typeSelector.value, title, origin, dom.directionSelector.value).then(function(data) {
    if (!data.success) {
      showToast('Registration failed', 'warn');
      return;
    }
    dom.titleInput.value = '';
    dom.originInput.value = '';
    refreshReferencePreview();
    renderDocumentTable();
    refreshStatistics();
    showToast('Document ' + data.reference + ' successfully tracked', 'success');
  }).catch(function() {
    showToast('Failed to register document', 'warn');
  });
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeFormatModal();
});

refreshReferencePreview();
renderDocumentTable();
refreshStatistics();
