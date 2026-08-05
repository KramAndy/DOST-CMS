function showToast(message, toastType) {
  toastType = toastType || 'info';
  const backgroundColor = toastType === 'success' ? 'var(--accent)' : toastType === 'warn' ? '#DC2626' : 'var(--ink)';
  const iconClass = toastType === 'success' ? 'fa-circle-check' : toastType === 'warn' ? 'fa-triangle-exclamation' : 'fa-circle-info';
  const toastElement = createHTMLElement(
    '<div class="toast-notification text-white text-xs px-4 py-3 rounded-lg flex items-center gap-2 max-w-xs" style="background:' + backgroundColor + '">' +
    '<i class="fa-solid ' + iconClass + ' text-[11px]"></i>' +
    '<span>' + escapeHtml(message) + '</span>' +
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
