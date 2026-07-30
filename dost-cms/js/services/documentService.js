var API_URLS = {
  documents: 'api/get_documents.php',
  updates: 'api/get_updates.php',
  updateStatus: 'api/update_status.php',
  addUpdate: 'api/add_update.php',
  register: 'api/register_document.php',
  statistics: 'api/get_statistics.php',
  referenceSettings: 'api/get_reference_settings.php',
  saveReferenceSettings: 'api/save_reference_settings.php',
  generateReference: 'api/generate_reference.php'
};

function apiGet(url, params) {
  var query = params ? Object.keys(params).map(function(k) {
    return k + '=' + encodeURIComponent(params[k]);
  }).join('&') : '';
  return fetch(url + (query ? '?' + query : '')).then(function(r) { return r.json(); });
}

function apiPost(url, data) {
  var formData = new URLSearchParams();
  Object.keys(data).forEach(function(k) { formData.append(k, data[k]); });
  return fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData.toString()
  }).then(function(r) { return r.json(); });
}

function getDocuments() {
  return apiGet(API_URLS.documents);
}

function getUpdates(reference) {
  return apiGet(API_URLS.updates, { reference: reference });
}

function updateStatus(reference, status) {
  return apiPost(API_URLS.updateStatus, { reference: reference, status: status });
}

function addUpdate(reference, notes) {
  return apiPost(API_URLS.addUpdate, { reference: reference, notes: notes });
}

function registerDocument(type, title, origin, registeredBy) {
  return apiPost(API_URLS.register, { type: type, title: title, origin: origin, registered_by: registeredBy });
}

function getStatistics() {
  return apiGet(API_URLS.statistics);
}

function getReferenceSettings() {
  return apiGet(API_URLS.referenceSettings);
}

function saveReferenceSettings(prefix, separator, padding, includeYear) {
  return apiPost(API_URLS.saveReferenceSettings, { prefix: prefix, separator: separator, padding: padding, include_year: includeYear ? '1' : '0' });
}

function generateReferenceNumber(docType) {
  return apiGet(API_URLS.generateReference, { type: docType });
}
