var activeTypeFilter = 'ALL';
var activeMonthFilter = 'ALL';
var activeYearFilter = new Date().getFullYear().toString();
var activeSearchQuery = '';
var currentlyExpandedRef = null;

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
