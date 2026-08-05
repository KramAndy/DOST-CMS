var notificationTypeConfig = {
  incoming:   { color: '#2563EB', soft: '#EFF6FF', icon: 'fa-inbox' },
  outgoing:   { color: '#7C3AED', soft: '#F3EEFF', icon: 'fa-paper-plane' },
  processing: { color: '#EA580C', soft: '#FFF7ED', icon: 'fa-rotate' },
  dispatched: { color: '#16A34A', soft: '#F0FDF4', icon: 'fa-circle-check' },
  overdue:    { color: '#DC2626', soft: '#FEF2F2', icon: 'fa-triangle-exclamation' }
};
var defaultNotificationConfig = { color: '#525252', soft: '#F5F5F5', icon: 'fa-bell' };

function formatRelativeTime(dateStr) {
  var date = new Date(dateStr.replace(' ', 'T'));
  var seconds = Math.floor((Date.now() - date.getTime()) / 1000);
  if (isNaN(seconds) || seconds < 0) seconds = 0;
  if (seconds < 60) return 'Just now';
  var minutes = Math.floor(seconds / 60);
  if (minutes < 60) return minutes + (minutes === 1 ? ' minute ago' : ' minutes ago');
  var hours = Math.floor(minutes / 60);
  if (hours < 24) return hours + (hours === 1 ? ' hour ago' : ' hours ago');
  var days = Math.floor(hours / 24);
  if (days === 1) return 'Yesterday';
  if (days < 7) return days + ' days ago';
  if (days < 30) return Math.floor(days / 7) + (Math.floor(days / 7) === 1 ? ' week ago' : ' weeks ago');
  return MONTH_NAMES[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
}

function updateNotificationBadge(count) {
  var badge = querySelector('#notification-badge');
  if (!badge) return;
  count = parseInt(count, 10) || 0;
  if (count > 0) {
    badge.textContent = count > 99 ? '99+' : count;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

function buildNotificationItem(notification) {
  var config = notificationTypeConfig[notification.notification_type] || defaultNotificationConfig;
  var message = escapeHtml(notification.message || '');
  var ref = escapeHtml(notification.reference_number || '');
  if (ref) {
    message = message.split(ref).join('<strong>' + ref + '</strong>');
  }
  var isUnread = notification.is_read != 1;
  return createHTMLElement(
    '<div class="notification-item ' + (isUnread ? 'is-unread' : '') + '" data-notification-id="' + escapeHtml(notification.notification_id) + '" data-reference="' + ref + '">' +
      '<div class="notification-icon" style="background:' + config.soft + ';color:' + config.color + '">' +
        '<i class="fa-solid ' + config.icon + '"></i>' +
      '</div>' +
      '<div class="notification-content">' +
        '<div class="notification-title">' + escapeHtml(notification.title) + '</div>' +
        '<div class="notification-message">' + message + '</div>' +
        '<div class="notification-time">' +
          '<i class="fa-regular fa-clock text-[9px] mr-1"></i>' + formatRelativeTime(notification.created_at) +
        '</div>' +
      '</div>' +
      (isUnread ? '<div class="notification-unread-dot" style="background:' + config.color + '"></div>' : '') +
    '</div>'
  );
}

function renderNotifications(data) {
  var list = querySelector('#notification-list');
  if (!list) return;
  list.innerHTML = '';
  var items = data && data.notifications ? data.notifications : [];
  if (items.length === 0) {
    list.appendChild(createHTMLElement(
      '<div class="notification-empty py-10 px-6 text-center">' +
        '<div class="notification-empty-icon w-12 h-12 rounded-full bg-[var(--bg)] border border-[var(--line)] flex items-center justify-center mx-auto mb-3">' +
          '<i class="fa-solid fa-bell-slash text-lg text-[var(--line)]"></i>' +
        '</div>' +
        '<p class="text-sm font-medium text-[var(--muted)]">No notifications.</p>' +
      '</div>'
    ));
  } else {
    items.forEach(function(notification) {
      list.appendChild(buildNotificationItem(notification));
    });
  }
  updateNotificationBadge(data ? data.unread_count : 0);
}

function refreshNotifications() {
  getNotifications().then(function(data) {
    renderNotifications(data);
  }).catch(function() {});
}

function openNotifications() {
  var dropdown = querySelector('#notification-dropdown');
  if (!dropdown) return;
  dropdown.classList.remove('hidden');
  refreshNotifications();
}

function closeNotifications() {
  var dropdown = querySelector('#notification-dropdown');
  if (dropdown) dropdown.classList.add('hidden');
}

function toggleNotifications() {
  var dropdown = querySelector('#notification-dropdown');
  if (!dropdown) return;
  if (dropdown.classList.contains('hidden')) {
    openNotifications();
  } else {
    closeNotifications();
  }
}

function highlightDocumentRow(ref) {
  var row = document.querySelector('tr[data-ref="' + ref + '"]');
  if (!row) return;
  row.scrollIntoView({ behavior: 'smooth', block: 'center' });
  row.classList.add('notification-highlight');
  setTimeout(function() {
    row.classList.remove('notification-highlight');
  }, 3000);
}

function locateAndHighlightDocument(ref) {
  activeSearchQuery = ref;
  activeTypeFilter = 'ALL';
  activeMonthFilter = 'ALL';
  activeYearFilter = 'ALL';

  var searchInput = querySelector('#document-search-input');
  if (searchInput) searchInput.value = ref;
  document.querySelectorAll('.filter-chip').forEach(function(chip) {
    chip.classList.toggle('is-selected', chip.dataset.filter === 'ALL');
  });
  var monthSel = querySelector('#month-filter-selector');
  var yearSel = querySelector('#year-filter-selector');
  if (monthSel) monthSel.value = 'ALL';
  if (yearSel) yearSel.value = 'ALL';

  renderDocumentTable().then(function() {
    highlightDocumentRow(ref);
  });
}

function focusDocumentFromNotification(ref, notificationId) {
  if (window.location.pathname.indexOf('dashboard.php') === -1) {
    window.location.href = 'dashboard.php?notification_ref=' + encodeURIComponent(ref) +
      '&notification_id=' + encodeURIComponent(notificationId);
    return;
  }
  locateAndHighlightDocument(ref);
}

function openNotification(notificationId, reference) {
  closeNotifications();
  markNotificationRead(notificationId).then(function() {
    refreshNotifications();
  }).catch(function() {});
  if (reference) {
    focusDocumentFromNotification(reference, notificationId);
  }
}

var notificationContainer = querySelector('#notification-container');
var notificationBellButton = querySelector('#notification-bell-button');
var markAllReadButton = querySelector('#mark-all-notifications-read');

if (notificationBellButton) {
  notificationBellButton.addEventListener('click', function(e) {
    e.stopPropagation();
    toggleNotifications();
  });
}

if (notificationContainer) {
  document.addEventListener('click', function(e) {
    if (!notificationContainer.contains(e.target)) {
      closeNotifications();
    }
  });
}

if (markAllReadButton) {
  markAllReadButton.addEventListener('click', function(e) {
    e.stopPropagation();
    markAllNotificationsRead().then(function() {
      refreshNotifications();
    });
  });
}

document.addEventListener('click', function(e) {
  var item = e.target.closest('.notification-item');
  if (!item) return;
  e.stopPropagation();
  openNotification(item.dataset.notificationId, item.dataset.reference);
});

document.addEventListener('DOMContentLoaded', function() {
  refreshNotifications();
  var params = new URLSearchParams(window.location.search);
  var ref = params.get('notification_ref');
  var notificationId = params.get('notification_id');
  if (notificationId) {
    markNotificationRead(notificationId).then(function() {
      refreshNotifications();
    }).catch(function() {});
  }
  if (ref) {
    setTimeout(function() {
      locateAndHighlightDocument(ref);
    }, 350);
  }
});
