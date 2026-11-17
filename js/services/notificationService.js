function getNotifications() {
  return apiGet(API_URLS.notifications);
}

function markNotificationRead(notificationId) {
  return apiPost(API_URLS.markNotificationRead, { notification_id: notificationId });
}

function markAllNotificationsRead() {
  return apiPost(API_URLS.markNotificationRead, { all: 1 });
}
