var dom = {
  typeSelector: querySelector('#document-type-selector'),
  previewEl: querySelector('#reference-number-preview'),
  titleInput: querySelector('#document-title-input'),
  originInput: querySelector('#originating-office-input'),
  registeredBy: querySelector('#registered-by'),
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
  registerDocument(dom.typeSelector.value, title, origin, dom.registeredBy.value).then(function(data) {
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
    setTimeout(function() {
      var newRow = document.querySelector('tr[data-ref="' + data.reference + '"]');
      if (newRow) {
        newRow.classList.add('pulse-glow');
        currentlyExpandedRef = data.reference;
        renderDocumentTable();
      }
    }, 100);
  });
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeFormatModal();
});

refreshReferencePreview();
renderDocumentTable();
refreshStatistics();
