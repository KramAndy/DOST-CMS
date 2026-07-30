function buildDocumentRow(doc, isExpanded) {
  var statusModifier = doc.status === 'Received' ? 'status-indicator--received'
    : doc.status === 'Processing' ? 'status-indicator--processing'
    : 'status-indicator--dispatched';
  var tagClass = documentTypeRegistry[doc.type] ? documentTypeRegistry[doc.type].tagClass : 'tag-internal-memo';

  return createHTMLElement(
    '<tr class="document-row animate-fade-up ' + (isExpanded ? 'is-expanded' : '') + '" data-ref="' + doc.ref + '">' +
      '<td class="px-5 py-3.5 align-top w-[26%]">' +
        '<div class="ref-number font-mono text-[11px] font-bold text-[var(--ink)]">' + doc.ref + '</div>' +
        '<span class="document-type-tag ' + tagClass + ' mt-1 inline-block">' + doc.type + '</span>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top">' +
        '<div class="doc-title-text font-semibold text-[13px] text-[var(--ink)] leading-snug">' + doc.title + '</div>' +
        '<div class="doc-origin-text text-[11px] text-[var(--muted)] mt-1">' +
          '<div><i class="fa-solid fa-building text-[9px] mr-1 opacity-60"></i>' + doc.origin + '</div>' +
          '<div class="mt-1"><i class="fa-solid fa-user text-[9px] mr-1 opacity-60"></i>' + doc.registeredBy + '</div>' +
        '</div>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top hidden md:table-cell">' +
        '<div class="doc-date-text text-[11px] text-[var(--muted)] font-medium whitespace-nowrap">' + formatDisplayDate(doc.date) + '</div>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top">' +
        '<span class="status-indicator ' + statusModifier + '">' + doc.status + '</span>' +
      '</td>' +
      '<td class="px-5 py-3.5 align-top text-right">' +
        '<button class="row-expand-trigger text-[var(--muted)] hover:text-[var(--accent)] transition-colors p-1">' +
          '<i class="fa-solid ' + (isExpanded ? 'fa-chevron-up' : 'fa-chevron-down') + ' text-xs"></i>' +
        '</button>' +
      '</td>' +
    '</tr>'
  );
}

function buildDetailRow(doc, safeId) {
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
          '<div class="update-form-column">' +
            '<h4 class="update-form-heading font-bold text-sm mb-4 flex items-center gap-2">' +
              '<i class="fa-solid fa-pen-to-square text-[var(--accent)] text-xs"></i> Provide Update' +
            '</h4>' +
            '<div class="preset-buttons-group mb-4">' +
              '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-2">Quick Status Presets</label>' +
              '<div class="preset-buttons-row flex flex-wrap gap-2">' +
                '<button class="button-ghost text-[11px] font-medium px-3 py-1.5 status-preset-button ' + (doc.status === 'Received' ? 'is-active-preset' : '') + '" data-ref="' + doc.ref + '" data-status="Received">Received</button>' +
                '<button class="button-ghost text-[11px] font-medium px-3 py-1.5 status-preset-button ' + (doc.status === 'Processing' ? 'is-active-preset' : '') + '" data-ref="' + doc.ref + '" data-status="Processing">Processing</button>' +
                '<button class="button-ghost text-[11px] font-medium px-3 py-1.5 status-preset-button ' + (doc.status === 'Dispatched' ? 'is-active-preset' : '') + '" data-ref="' + doc.ref + '" data-status="Dispatched">Dispatched</button>' +
              '</div>' +
            '</div>' +
            '<div class="update-notes-group">' +
              '<label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Update Notes</label>' +
              '<textarea id="update-notes-' + safeId + '" rows="3" placeholder="Enter details about this update..." class="flat-field w-full px-3 py-2.5 text-sm resize-none"></textarea>' +
            '</div>' +
            '<button class="submit-update-button button-primary w-full text-[11px] font-medium px-4 py-2.5 flex items-center justify-center gap-2 mt-3" data-ref="' + doc.ref + '">' +
              '<i class="fa-solid fa-paper-plane text-[10px]"></i> Submit Update' +
            '</button>' +
          '</div>' +
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

  getFilteredDocuments({
    type: activeTypeFilter,
    year: activeYearFilter,
    month: activeMonthFilter,
    query: activeSearchQuery
  }).then(function(filteredDocuments) {
    if (filteredDocuments.length === 0) {
      emptyMessage.classList.remove('hidden');
    } else {
      emptyMessage.classList.add('hidden');
      filteredDocuments.slice().reverse().forEach(function(doc) {
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
      !e.target.closest('textarea')) {
    currentlyExpandedRef = currentlyExpandedRef === row.dataset.ref ? null : row.dataset.ref;
    renderDocumentTable();
    return;
  }

  var presetBtn = e.target.closest('.status-preset-button');
  if (presetBtn) {
    e.stopPropagation();
    updateStatus(presetBtn.dataset.ref, presetBtn.dataset.status).then(function(data) {
      if (data.success) {
        renderDocumentTable();
        refreshStatistics();
        showToast('Status updated to ' + presetBtn.dataset.status, 'success');
      }
    });
    return;
  }

  var submitBtn = e.target.closest('.submit-update-button');
  if (submitBtn) {
    e.stopPropagation();
    var notesField = document.getElementById('update-notes-' + toSafeId(submitBtn.dataset.ref));
    var notes = notesField ? notesField.value.trim() : '';
    if (!notes) {
      showToast('Please enter update notes', 'warn');
      return;
    }
    addUpdate(submitBtn.dataset.ref, notes).then(function(data) {
      if (data.success) {
        notesField.value = '';
        renderDocumentTable();
        showToast('Update added to timeline', 'success');
      }
    });
  }
});
