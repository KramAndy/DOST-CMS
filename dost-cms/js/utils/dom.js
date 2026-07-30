function querySelector(selector) { return document.querySelector(selector); }

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
