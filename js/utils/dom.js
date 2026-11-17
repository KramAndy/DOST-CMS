function querySelector(selector) { return document.querySelector(selector); }

function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function createHTMLElement(htmlString) {
  const template = document.createElement('template');
  template.innerHTML = htmlString.trim();
  return template.content.firstChild;
}

function showElement(element) {
  element.classList.remove('hidden');
  element.classList.add('flex');
}

function hideElement(element) {
  element.classList.add('hidden');
  element.classList.remove('flex');
}
