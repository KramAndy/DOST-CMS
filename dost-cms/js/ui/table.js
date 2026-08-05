var selectedStatuses = {};
var editingRef = null;

function buildDocumentRow(doc, isExpanded) {
  var statusModifier = doc.status === 'Received' ? 'status-indicator--received'
    : doc.status === 'Processing' ? 'status-indicator--processing'
    : 'status-indicator--dispatched';
  var tagClass = documentTypeRegistry[doc.type] ? documentTypeRegistry[doc.type].tagClass : 'tag-internal-memo';
  var role = window.USER_ROLE || '';
  var canEdit   = role === 'super_admin' || role === 'office_admin';
  var canDelete = role === 'super_admin';

  var actionHtml =
    '<div class="flex items-center justify-end gap-1">' +
      '<button class="row-expand-trigger text-[var(--muted)] hover:text-[var(--accent)] transition-colors p-1.5 rounded" title="' + (isExpanded ? 'Collapse' : 'Expand') + '">' +
        '<i class="fa-solid ' + (isExpanded ? 'fa-chevron-up' : 'fa-chevron-down') + ' text-[11px]"></i>' +
      '</button>' +
      (canEdit
        ? '<button class="doc-edit-trigger text-[var(--muted)] hover:text-[var(--accent)] transition-colors p-1.5 rounded" title="Edit document">' +
            '<i class="fa-solid fa-pen text-[10px]"></i>' +
          '</button>'
        : '') +
      (canDelete
        ? '<button class="doc-delete-trigger text-[var(--muted)] hover:text-red-500 transition-colors p-1.5 rounded" title="Delete document">' +
            '<i class="fa-solid fa-trash text-[10px]"></i>' +
          '</button>'
        : '') +
    '</div>';

  return createHTMLElement(
    '<tr class="document-row animate-fade-up ' + (isExpanded ? 'is-expanded' : '') + '" data-ref="' + escapeHtml(doc.ref) + '">' +
      '<td class="px-5 py-3.5 align-top w-[26%]">' +
        '<div class="ref-number font-mono text-[11px] font-bold text-[var(--ink)]">' + escapeHtml(doc.ref) + '</div>' +
        '<span class="document-type-tag ' + tagClass + ' mt-1 inline-block">' + escapeHtml(doc.type) + '</span>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top">' +
        '<div class="doc-title-text font-semibold text-[13px] text-[var(--ink)] leading-snug">' + escapeHtml(doc.title) + '</div>' +
        '<div class="doc-origin-text text-[11px] text-[var(--muted)] mt-1">' +
          '<div><i class="fa-solid fa-building text-[9px] mr-1 opacity-60"></i>' + escapeHtml(doc.origin) + '</div>' +
          '<div class="mt-1"><i class="fa-solid fa-user text-[9px] mr-1 opacity-60"></i>' + escapeHtml(doc.registeredBy) + '</div>' +
        '</div>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top hidden md:table-cell">' +
        '<div class="doc-date-text text-[11px] text-[var(--muted)] font-medium whitespace-nowrap">' + formatDisplayDate(doc.date) + '</div>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top">' +
        '<span class="status-indicator ' + statusModifier + '">' + escapeHtml(doc.status) + '</span>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top text-right">' +
        actionHtml +
      '</td>' +
    '</tr>'
  );
}

function buildDetailRow(doc, safeId) {
  if (!(doc.ref in selectedStatuses)) {
    selectedStatuses[doc.ref] = doc.status;
  }

  var isEditing = editingRef === doc.ref;

  var typeOptions = '';
  var typeKeys = Object.keys(documentTypeRegistry);
  for (var t = 0; t < typeKeys.length; t++) {
    var tk = typeKeys[t];
    typeOptions += '<option value="' + tk + '"' + (tk === doc.type ? ' selected' : '') + '>' + tk + '</option>';
  }

  var updateFormHtml =
    '<div class="update-form-column">' +
      '<h4 class="update-form-heading font-bold text-sm mb-4 flex items-center gap-2">' +
        '<i class="fa-solid fa-pen-to-square text-[var(--accent)] text-xs"></i> Provide Update' +
      '</h4>' +
      '<div class="preset-buttons-group mb-4">' +
        '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-2">Quick Status Presets</label>' +
        '<div class="preset-buttons-row flex flex-wrap gap-2">' +
          '<button class="button-ghost text-[11px] font-medium px-3 py-1.5 status-preset-button ' + (doc.status === 'Received' ? 'is-active-preset' : '') + '" data-ref="' + escapeHtml(doc.ref) + '" data-status="Received">Received</button>' +
          '<button class="button-ghost text-[11px] font-medium px-3 py-1.5 status-preset-button ' + (doc.status === 'Processing' ? 'is-active-preset' : '') + '" data-ref="' + escapeHtml(doc.ref) + '" data-status="Processing">Processing</button>' +
          '<button class="button-ghost text-[11px] font-medium px-3 py-1.5 status-preset-button ' + (doc.status === 'Dispatched' ? 'is-active-preset' : '') + '" data-ref="' + escapeHtml(doc.ref) + '" data-status="Dispatched">Dispatched</button>' +
        '</div>' +
      '</div>' +
      '<div class="update-notes-group">' +
        '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Update Notes</label>' +
        '<textarea id="update-notes-' + safeId + '" rows="3" placeholder="Enter details about this update..." class="flat-field w-full px-3 py-2.5 text-sm resize-none"></textarea>' +
      '</div>' +
      '<button class="submit-update-button button-primary w-full text-[11px] font-medium px-4 py-2.5 flex items-center justify-center gap-2 mt-3" data-ref="' + escapeHtml(doc.ref) + '">' +
        '<i class="fa-solid fa-paper-plane text-[10px]"></i> Submit Update' +
      '</button>' +
    '</div>';

  var editFormHtml =
    '<div class="update-form-column">' +
      '<h4 class="update-form-heading font-bold text-sm mb-4 flex items-center gap-2">' +
        '<i class="fa-solid fa-pen text-[var(--accent)] text-xs"></i> Edit Document' +
      '</h4>' +
      '<div class="space-y-3">' +
        '<div>' +
          '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1">Title</label>' +
          '<input type="text" id="edit-title-' + safeId + '" value="' + escapeHtml(doc.title) + '" class="flat-field w-full px-3 py-2 text-sm" maxlength="255">' +
        '</div>' +
        '<div>' +
          '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1">Originating Office</label>' +
          '<input type="text" id="edit-origin-' + safeId + '" value="' + escapeHtml(doc.origin) + '" class="flat-field w-full px-3 py-2 text-sm" maxlength="255">' +
        '</div>' +
        '<div class="grid grid-cols-2 gap-3">' +
          '<div>' +
            '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1">Type</label>' +
            '<select id="edit-type-' + safeId + '" class="flat-field w-full px-3 py-2 text-sm">' + typeOptions + '</select>' +
          '</div>' +
          '<div>' +
            '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1">Direction</label>' +
            '<select id="edit-direction-' + safeId + '" class="flat-field w-full px-3 py-2 text-sm">' +
              '<option value="Incoming"' + (doc.direction === 'Incoming' ? ' selected' : '') + '>Incoming</option>' +
              '<option value="Outgoing"' + (doc.direction === 'Outgoing' ? ' selected' : '') + '>Outgoing</option>' +
            '</select>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="flex gap-2 mt-4">' +
        '<button class="edit-save-button button-primary flex-1 text-[11px] font-medium px-4 py-2.5 flex items-center justify-center gap-2" data-ref="' + escapeHtml(doc.ref) + '">' +
          '<i class="fa-solid fa-check text-[10px]"></i> Save Changes' +
        '</button>' +
        '<button class="edit-cancel-button button-ghost flex-1 text-[11px] font-medium px-4 py-2.5 flex items-center justify-center gap-2" data-ref="' + escapeHtml(doc.ref) + '">' +
          'Cancel' +
        '</button>' +
      '</div>' +
    '</div>';

  return createHTMLElement(
    '<tr class="row-detail-panel">' +
      '<td colspan="5" class="px-5 py-6">' +
        '<div class="detail-grid grid md:grid-cols-2 gap-8">' +
          '<div class="timeline-column">' +
            '<h4 class="timeline-heading font-bold text-sm mb-4 flex items-center gap-2">' +
              '<i class="fa-solid fa-clock-rotate-left text-[var(--accent)] text-xs"></i> Chronological Updates' +
            '</h4>' +
            '<div class="timeline-list" id="timeline-list-' + safeId + '">' +
              '<div class="timeline-entry">' +
                '<div class="timeline-timestamp text-[10px] text-[var(--muted)] font-bold uppercase tracking-wide">Loading...</div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          (isEditing ? editFormHtml : updateFormHtml) +
        '</div>' +
      '</td>' +
    '</tr>'
  );
}

function getFilteredDocuments(filters) {
  return getDocuments().then(function(data) {
    return data.filter(function(doc) {
      if (filters.type !== 'ALL' && doc.document_type !== filters.type) return false;
      if (filters.year !== 'ALL') {
        var docYear = new Date(doc.created_at).getFullYear().toString();
        if (docYear !== filters.year) return false;
      }
      if (filters.month !== 'ALL') {
        var docMonth = new Date(doc.created_at).getMonth().toString();
        if (docMonth !== filters.month) return false;
      }
      if (filters.query) {
        var q = filters.query.toLowerCase();
        if (doc.reference_no.toLowerCase().indexOf(q) === -1 &&
            doc.title.toLowerCase().indexOf(q) === -1) return false;
      }
      return true;
    }).map(function(doc) {
      return {
        ref: doc.reference_no,
        type: doc.document_type,
        title: doc.title,
        origin: doc.origin_office,
        registeredBy: doc.registered_by,
        status: doc.status,
        date: doc.created_at,
        history: []
      };
    });
  });
}

function renderDocumentTable() {
  var tableBody = querySelector('#document-table-body');
  var emptyMessage = querySelector('#no-documents-message');
  tableBody.innerHTML = '';

  var pendingRender = getFilteredDocuments({
    type: activeTypeFilter,
    year: activeYearFilter,
    month: activeMonthFilter,
    query: activeSearchQuery
  }).then(function(filteredDocuments) {
    if (filteredDocuments.length === 0) {
      emptyMessage.classList.remove('hidden');
    } else {
      emptyMessage.classList.add('hidden');
      filteredDocuments.forEach(function(doc) {
        var isExpanded = currentlyExpandedRef === doc.ref;
        tableBody.appendChild(buildDocumentRow(doc, isExpanded));
        if (isExpanded) {
          var safeId = toSafeId(doc.ref);
          tableBody.appendChild(buildDetailRow(doc, safeId));
        }
      });
    }
    loadTimeline(currentlyExpandedRef);
  });
  return pendingRender;
}

function refreshStatistics() {
  getStatistics().then(function(stats) {
    querySelector('#stat-total-count').textContent = stats.total;
    querySelector('#stat-received-count').textContent = stats.received;
    querySelector('#stat-processing-count').textContent = stats.processing;
    querySelector('#stat-dispatched-count').textContent = stats.dispatched;
  });
}

document.addEventListener('click', function(e) {
  var row = e.target.closest('.document-row');
  if (row &&
      !e.target.closest('.row-expand-trigger') &&
      !e.target.closest('.status-preset-button') &&
      !e.target.closest('.submit-update-button') &&
      !e.target.closest('.doc-edit-trigger') &&
      !e.target.closest('.doc-delete-trigger') &&
      !e.target.closest('.edit-save-button') &&
      !e.target.closest('.edit-cancel-button') &&
      !e.target.closest('textarea')) {
    currentlyExpandedRef = currentlyExpandedRef === row.dataset.ref ? null : row.dataset.ref;
    editingRef = null;
    renderDocumentTable();
    return;
  }

  var expandBtn = e.target.closest('.row-expand-trigger');
  if (expandBtn) {
    e.stopPropagation();
    var ref = expandBtn.closest('.document-row').dataset.ref;
    currentlyExpandedRef = currentlyExpandedRef === ref ? null : ref;
    editingRef = null;
    renderDocumentTable();
    return;
  }

  var presetBtn = e.target.closest('.status-preset-button');
  if (presetBtn) {
    e.stopPropagation();
    var ref = presetBtn.dataset.ref;
    var status = presetBtn.dataset.status;
    selectedStatuses[ref] = status;
    var siblings = presetBtn.parentElement.querySelectorAll('.status-preset-button');
    for (var i = 0; i < siblings.length; i++) {
      siblings[i].classList.remove('is-active-preset');
    }
    presetBtn.classList.add('is-active-preset');
    return;
  }

  var editBtn = e.target.closest('.doc-edit-trigger');
  if (editBtn) {
    e.stopPropagation();
    var ref = editBtn.closest('.document-row').dataset.ref;
    currentlyExpandedRef = ref;
    editingRef = ref;
    renderDocumentTable();
    return;
  }

  var deleteBtn = e.target.closest('.doc-delete-trigger');
  if (deleteBtn) {
    e.stopPropagation();
    var rowEl = deleteBtn.closest('.document-row');
    var ref = rowEl.dataset.ref;
    var titleEl = rowEl.querySelector('.doc-title-text');
    var title = titleEl ? titleEl.textContent : ref;
    if (!confirm('Delete document "' + ref + '"\n\n' + title + '\n\nThis action cannot be undone.')) {
      return;
    }
    deleteDocument(ref).then(function(data) {
      if (data.success) {
        if (currentlyExpandedRef === ref) {
          currentlyExpandedRef = null;
          editingRef = null;
        }
        renderDocumentTable();
        refreshStatistics();
        showToast('Document deleted', 'success');
      } else {
        showToast('Failed to delete: ' + (data.message || 'unknown error'), 'warn');
      }
    });
    return;
  }

  var saveBtn = e.target.closest('.edit-save-button');
  if (saveBtn) {
    e.stopPropagation();
    var ref = saveBtn.dataset.ref;
    var safeId = toSafeId(ref);
    var title     = (document.getElementById('edit-title-' + safeId)      || {}).value || '';
    var origin    = (document.getElementById('edit-origin-' + safeId)     || {}).value || '';
    var type      = (document.getElementById('edit-type-' + safeId)       || {}).value || '';
    var direction = (document.getElementById('edit-direction-' + safeId)  || {}).value || '';
    title = title.trim();
    origin = origin.trim();
    if (!title || !origin) {
      showToast('Title and originating office are required', 'warn');
      return;
    }
    editDocument(ref, title, origin, type, direction).then(function(data) {
      if (data.success) {
        editingRef = null;
        renderDocumentTable();
        showToast('Document updated', 'success');
      } else {
        showToast('Failed to update: ' + (data.message || 'unknown error'), 'warn');
      }
    });
    return;
  }

  var cancelBtn = e.target.closest('.edit-cancel-button');
  if (cancelBtn) {
    e.stopPropagation();
    editingRef = null;
    renderDocumentTable();
    return;
  }

  var submitBtn = e.target.closest('.submit-update-button');
  if (submitBtn) {
    e.stopPropagation();
    var ref = submitBtn.dataset.ref;
    var notesField = document.getElementById('update-notes-' + toSafeId(ref));
    var notes = notesField ? notesField.value.trim() : '';
    if (!notes) {
      showToast('Please enter update notes', 'warn');
      return;
    }
    var status = selectedStatuses[ref];
    if (!status) {
      showToast('Please select a status', 'warn');
      return;
    }
    submitUpdate(ref, status, notes).then(function(data) {
      if (data.success) {
        notesField.value = '';
        delete selectedStatuses[ref];
        renderDocumentTable();
        refreshStatistics();
        showToast('Update submitted successfully', 'success');
      } else {
        showToast('Failed to submit update: ' + (data.message || 'unknown error'), 'warn');
      }
    });
  }
});
