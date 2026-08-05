function loadTimeline(ref) {
  if (!ref) return;
  var expandedSafeId = toSafeId(ref);
  var timelineContainer = document.getElementById('timeline-list-' + expandedSafeId);
  if (!timelineContainer) return;

  getUpdates(ref).then(function(updates) {
    timelineContainer.innerHTML = '';
    updates.forEach(function(entry) {
      var isFinal = entry.status === 'Dispatched';
      var d = new Date(entry.created_at);
      var ts = MONTH_NAMES[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
      var entryEl = createHTMLElement(
        '<div class="timeline-entry ' + (isFinal ? 'is-final' : '') + '">' +
          '<div class="timeline-timestamp text-[10px] text-[var(--muted)] font-bold uppercase tracking-wide">' + escapeHtml(ts) + '</div>' +
          '<div class="timeline-status font-semibold text-[13px] mt-0.5 text-[var(--ink)]">' + escapeHtml(entry.status) + '</div>' +
          '<p class="timeline-note text-xs text-[var(--muted)] mt-1 leading-relaxed">' + escapeHtml(entry.notes || '') + '</p>' +
          '<div class="timeline-user text-[10px] text-[var(--muted)] mt-2">' +
            '<i class="fa-solid fa-user text-[9px] mr-1"></i>' +
            escapeHtml(entry.updated_by || 'Unknown') +
          '</div>' +
        '</div>'
      );
      timelineContainer.appendChild(entryEl);
    });
  });
}
