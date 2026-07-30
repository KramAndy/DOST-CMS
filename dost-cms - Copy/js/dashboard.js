
// ----------------------------------------------------------------
//  DOM shortcuts
// ----------------------------------------------------------------
function querySelector(selector) { return document.querySelector(selector); }

function createHTMLElement(htmlString) {
  const template = document.createElement('template');
  template.innerHTML = htmlString.trim();
  return template.content.firstChild;
}

// ----------------------------------------------------------------
//  View-only state (not business data — lives here, not in the service)
// ----------------------------------------------------------------
let activeTypeFilter = 'ALL';
let activeMonthFilter = 'ALL';
let activeYearFilter = new Date().getFullYear().toString();
let activeSearchQuery = '';
let currentlyExpandedRef = null;

// ----------------------------------------------------------------
//  Toast notifications
// ----------------------------------------------------------------
function showToast(message, toastType) {
  toastType = toastType || 'info';
  const backgroundColor = toastType === 'success' ? 'var(--accent)' : toastType === 'warn' ? '#DC2626' : 'var(--ink)';
  const iconClass = toastType === 'success' ? 'fa-circle-check' : toastType === 'warn' ? 'fa-triangle-exclamation' : 'fa-circle-info';
  const toastElement = createHTMLElement(
    '<div class="toast-notification text-white text-xs px-4 py-3 rounded-lg flex items-center gap-2 max-w-xs" style="background:' + backgroundColor + '">' +
    '<i class="fa-solid ' + iconClass + ' text-[11px]"></i>' +
    '<span>' + message + '</span>' +
    '</div>'
  );
  querySelector('#notification-toast-area').appendChild(toastElement);
  setTimeout(function() {
    toastElement.style.opacity = '0';
    toastElement.style.transform = 'translateY(8px)';
    toastElement.style.transition = 'all 0.3s';
  }, 2800);
  setTimeout(function() { toastElement.remove(); }, 3200);
}

// ----------------------------------------------------------------
//  Statistics panel
// ----------------------------------------------------------------
function refreshStatistics() {
  fetch('api/get_statistics.php')
    .then(function(r) { return r.json(); })
    .then(function(stats) {
      querySelector('#stat-total-count').textContent = stats.total;
      querySelector('#stat-received-count').textContent = stats.received;
      querySelector('#stat-processing-count').textContent = stats.processing;
      querySelector('#stat-dispatched-count').textContent = stats.dispatched;
    });
}

// ----------------------------------------------------------------
//  Document type registry
// ----------------------------------------------------------------
var documentTypeRegistry = {
  'IM': { tagClass: 'tag-internal-memo' },
  'EL': { tagClass: 'tag-external-letter' },
  'SO': { tagClass: 'tag-special-order' },
  'OO': { tagClass: 'tag-office-order' },
  'MOA': { tagClass: 'tag-moa-mou' }
};

function toSafeId(ref) {
  return ref.replace(/[^a-zA-Z0-9]/g, '_');
}

function formatDisplayDate(dateStr) {
  var d = new Date(dateStr);
  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
}

function previewReferenceFormat(prefix, sep, padding, includeYear) {
  var yearPart = includeYear ? sep + new Date().getFullYear() + sep : sep;
  return prefix + sep + 'IM' + yearPart + '1'.padStart(parseInt(padding, 10) || 4, '0');
}

// ----------------------------------------------------------------
//  Reference number generation
// ----------------------------------------------------------------
function generateReferenceNumber(docType) {
  var url = 'api/generate_reference.php?type=' + encodeURIComponent(docType);
  querySelector('#reference-number-preview').textContent = '—';
  fetch(url)
    .then(function(response) { return response.json(); })
    .then(function(data) {
      if (data.success) {
        querySelector('#reference-number-preview').textContent = data.reference;
      }
    })
    .catch(function() {
      querySelector('#reference-number-preview').textContent = 'Error';
    });
}

// ----------------------------------------------------------------
//  Reference number preview
// ----------------------------------------------------------------
function refreshReferencePreview() {
  generateReferenceNumber(querySelector('#document-type-selector').value);
}

// ----------------------------------------------------------------
//  Format configuration modal
// ----------------------------------------------------------------
function openFormatModal() {
  fetch('api/get_reference_settings.php')
    .then(function(r) { return r.json(); })
    .then(function(settings) {
      querySelector('#format-prefix-input').value = settings.prefix;
      querySelector('#format-separator-input').value = settings.separator;
      querySelector('#format-padding-input').value = settings.padding;
      querySelector('#format-include-year-checkbox').checked = settings.include_year == 1;
      refreshFormatPreview();
      querySelector('#reference-format-modal').classList.remove('hidden');
      querySelector('#reference-format-modal').classList.add('flex');
    });
}

function closeFormatModal() {
  querySelector('#reference-format-modal').classList.add('hidden');
  querySelector('#reference-format-modal').classList.remove('flex');
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
  var formData = new URLSearchParams();
  formData.append('prefix', querySelector('#format-prefix-input').value);
  formData.append('separator', querySelector('#format-separator-input').value);
  formData.append('padding', querySelector('#format-padding-input').value);
  formData.append('include_year', querySelector('#format-include-year-checkbox').checked ? '1' : '0');
  fetch('api/save_reference_settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData.toString()
  }).then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        refreshReferencePreview();
        closeFormatModal();
        showToast('Reference format updated successfully', 'success');
      }
    });
});

// ----------------------------------------------------------------
//  Document table rendering
// ----------------------------------------------------------------
function getFilteredDocuments(filters) {
  return fetch('api/get_documents.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
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
      var statusModifier = doc.status === 'Received' ? 'status-indicator--received'
        : doc.status === 'Processing' ? 'status-indicator--processing'
        : 'status-indicator--dispatched';
      var isExpanded = currentlyExpandedRef === doc.ref;
      var safeId = toSafeId(doc.ref);
      var tagClass = documentTypeRegistry[doc.type] ? documentTypeRegistry[doc.type].tagClass : 'tag-internal-memo';

      var rowElement = createHTMLElement(
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
            '<button class="row-expand-trigger text-[var(--muted)] hover:text-[var(--accent)] transition-colors p-1" onclick="event.stopPropagation();">' +
              '<i class="fa-solid ' + (isExpanded ? 'fa-chevron-up' : 'fa-chevron-down') + ' text-xs"></i>' +
            '</button>' +
          '</td>' +
        '</tr>'
      );
      tableBody.appendChild(rowElement);

      if (isExpanded) {
        var detailRow = createHTMLElement(
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
        tableBody.appendChild(detailRow);
      }
    });
  }

  // Row click → expand / collapse
  document.querySelectorAll('.document-row').forEach(function(row) {
    row.addEventListener('click', function(event) {
      if (event.target.closest('.row-expand-trigger') ||
          event.target.closest('.status-preset-button') ||
          event.target.closest('.submit-update-button') ||
          event.target.closest('textarea')) {
        return;
      }
      currentlyExpandedRef = currentlyExpandedRef === row.dataset.ref ? null : row.dataset.ref;
      renderDocumentTable();
    });
  });

  // Status preset buttons
  document.querySelectorAll('.status-preset-button').forEach(function(button) {
    button.addEventListener('click', function(event) {
      event.stopPropagation();
      var ref = button.dataset.ref;
      var status = button.dataset.status;
      var formData = new URLSearchParams();
      formData.append('reference', ref);
      formData.append('status', status);
      fetch('api/update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      }).then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success) {
            renderDocumentTable();
            refreshStatistics();
            showToast('Status updated to ' + status, 'success');
          }
        });
    });
  });

  // Submit update buttons
  document.querySelectorAll('.submit-update-button').forEach(function(button) {
    button.addEventListener('click', function(event) {
      event.stopPropagation();
      var ref = button.dataset.ref;
      var notesField = document.getElementById('update-notes-' + toSafeId(ref));
      var notes = notesField ? notesField.value.trim() : '';
      if (!notes) {
        showToast('Please enter update notes', 'warn');
        return;
      }
      var formData = new URLSearchParams();
      formData.append('reference', ref);
      formData.append('notes', notes);
      fetch('api/add_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      }).then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success) {
            notesField.value = '';
            renderDocumentTable();
            showToast('Update added to timeline', 'success');
          }
        });
    });
  });

  // Fetch timeline for expanded row
  if (currentlyExpandedRef) {
    var expandedSafeId = toSafeId(currentlyExpandedRef);
    var timelineContainer = document.getElementById('timeline-list-' + expandedSafeId);
    if (timelineContainer) {
      fetch('api/get_updates.php?reference=' + encodeURIComponent(currentlyExpandedRef))
        .then(function(r) { return r.json(); })
        .then(function(updates) {
          timelineContainer.innerHTML = '';
          updates.forEach(function(entry) {
            var isFinal = entry.status === 'Dispatched';
            var d = new Date(entry.created_at);
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var ts = months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
            var entryEl = createHTMLElement(
			  '<div class="timeline-entry ' + (isFinal ? 'is-final' : '') + '">' +
				'<div class="timeline-timestamp text-[10px] text-[var(--muted)] font-bold uppercase tracking-wide">' + ts + '</div>' +
				'<div class="timeline-status font-semibold text-[13px] mt-0.5 text-[var(--ink)]">' + entry.status + '</div>' +
				'<p class="timeline-note text-xs text-[var(--muted)] mt-1 leading-relaxed">' + (entry.notes || '') + '</p>' +
				'<div class="timeline-user text-[10px] text-[var(--muted)] mt-2">' +
				  '<i class="fa-solid fa-user text-[9px] mr-1"></i>' +
				  (entry.updated_by || 'Unknown') +
				'</div>' +
			  '</div>'
			);
            timelineContainer.appendChild(entryEl);
          });
        });
    }
  }
  });
}

// ----------------------------------------------------------------
//  Document registration (reads the form, calls the service, re-renders)
// ----------------------------------------------------------------
querySelector('#document-type-selector').addEventListener('change', refreshReferencePreview);

function registerDocument(type, title, origin, registeredBy) {
  var formData = new URLSearchParams();
  formData.append('type', type);
  formData.append('title', title);
  formData.append('origin', origin);
  formData.append('registered_by', registeredBy);
  return fetch('api/register_document.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData.toString()
  }).then(function(response) { return response.json(); });
}

querySelector('#register-document-button').addEventListener('click', function() {
  var type = querySelector('#document-type-selector').value;
  var title = querySelector('#document-title-input').value.trim();
  var origin = querySelector('#originating-office-input').value.trim();
  var registeredBy = querySelector('#registered-by').value;

  if (!title || !origin) {
    showToast('Please provide a title and originating office', 'warn');
    return;
  }

  registerDocument(type, title, origin, registeredBy).then(function(data) {
    if (!data.success) {
      showToast('Registration failed', 'warn');
      return;
    }

    querySelector('#document-title-input').value = '';
    querySelector('#originating-office-input').value = '';
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

// ----------------------------------------------------------------
//  Filter & search bindings
// ----------------------------------------------------------------
document.querySelectorAll('.filter-chip').forEach(function(chip) {
  chip.addEventListener('click', function() {
    document.querySelectorAll('.filter-chip').forEach(function(c) { c.classList.remove('is-selected'); });
    chip.classList.add('is-selected');
    activeTypeFilter = chip.dataset.filter;
    renderDocumentTable();
  });
});

querySelector('#month-filter-selector').addEventListener('change', function(e) {
  activeMonthFilter = e.target.value;
  renderDocumentTable();
});

querySelector('#year-filter-selector').addEventListener('change', function(e) {
  activeYearFilter = e.target.value;
  renderDocumentTable();
});

querySelector('#document-search-input').addEventListener('input', function(e) {
  activeSearchQuery = e.target.value;
  renderDocumentTable();
});

// Keyboard: Escape closes modal
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeFormatModal();
});

// ----------------------------------------------------------------
//  Initialize
// ----------------------------------------------------------------
refreshReferencePreview();
renderDocumentTable();
refreshStatistics();