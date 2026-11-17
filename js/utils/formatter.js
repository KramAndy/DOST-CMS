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

function previewReferenceFormat(prefix, sep, padding, includeYear) {
  var yearPart = includeYear ? sep + new Date().getFullYear() + sep : sep;
  return prefix + sep + 'IM' + yearPart + '1'.padStart(parseInt(padding, 10) || 4, '0');
}
