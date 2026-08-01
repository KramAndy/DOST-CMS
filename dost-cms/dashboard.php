<?php
session_start();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include('inc/auth.php');
include('config/db.php');
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Communication Management System · DOST Region V</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/document.css">
</head>
<body>

  <!-- Top Accent Stripe -->
  <div class="h-[3px] bg-[var(--accent)]"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

    <!-- ======================== PAGE HEADER ======================== -->
<?php include('inc/header.php'); ?>
    <!-- ======================== STATISTICS PANEL ======================== -->
    <div class="document-statistics-panel grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
      <div class="stat-card">
        <div class="stat-card-icon bg-[var(--accent-soft)] text-[var(--accent)]"><i class="fa-solid fa-file-lines"></i></div>
        <div>
          <div class="stat-card-value" id="stat-total-count">0</div>
          <div class="stat-card-label">Total Documents</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon bg-[var(--accent-soft)] text-[var(--accent)]"><i class="fa-solid fa-inbox"></i></div>
        <div>
          <div class="stat-card-value" id="stat-received-count">0</div>
          <div class="stat-card-label">Received</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon bg-[var(--amber-soft)] text-[var(--amber)]"><i class="fa-solid fa-spinner"></i></div>
        <div>
          <div class="stat-card-value" id="stat-processing-count">0</div>
          <div class="stat-card-label">Processing</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon bg-[var(--green-soft)] text-[var(--green)]"><i class="fa-solid fa-paper-plane"></i></div>
        <div>
          <div class="stat-card-value" id="stat-dispatched-count">0</div>
          <div class="stat-card-label">Dispatched</div>
        </div>
      </div>
    </div>

    <!-- ======================== MAIN CONTENT GRID ======================== -->
    <div class="main-content-grid grid lg:grid-cols-3 gap-6 mb-8">

      <!-- ─── Left Column: Reference Generator ─── -->
      <div class="reference-generator-column lg:col-span-1">
        <div class="flat-card p-5 lg:sticky lg:top-6">
          <div class="generator-header flex items-center justify-between mb-5">
            <div class="generator-title-group flex items-center gap-2.5">
              <div class="generator-icon w-8 h-8 rounded-lg bg-[var(--accent)] text-white flex items-center justify-center text-[13px]">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
              </div>
              <h2 class="generator-heading text-base font-bold">Reference Generator</h2>
            </div>
            <button id="open-format-settings-button" class="settings-trigger text-[var(--muted)] hover:text-[var(--accent)] transition-colors p-1.5 rounded-md hover:bg-[var(--bg)]" title="Configure Format">
              <i class="fa-solid fa-gear text-sm"></i>
            </button>
          </div>

          <div class="generator-form space-y-4">
            <div class="form-group">
              <label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Document Type</label>
              <select id="document-type-selector" class="flat-field mt-0 w-full px-3 py-2.5 text-sm cursor-pointer appearance-none">
                <option value="IM">Internal Memo (IM)</option>
                <option value="EL">External Letters (EL)</option>
                <option value="SO">Special Order (SO)</option>
                <option value="OO">Office Order (OO)</option>
                <option value="MOA">MOA / MOU (MOA)</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Document Title / Subject</label>
              <input id="document-title-input" type="text" placeholder="e.g. Request for IT Equipment" class="flat-field w-full px-3 py-2.5 text-sm">
            </div>

            <div class="form-group">
              <label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Originating Office</label>
              <input id="originating-office-input" type="text" placeholder="e.g. Office of the Regional Director" class="flat-field w-full px-3 py-2.5 text-sm">
            </div>

            <div class="reference-preview-box p-4 rounded-lg bg-[var(--bg)] border border-dashed border-[var(--line)]">
              <div class="preview-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold mb-1">Generated Reference No.</div>
              <div id="reference-number-preview" class="preview-value font-mono text-base font-bold text-[var(--accent)] break-all leading-snug">—</div>
            </div>
				<input type="hidden" id="registered-by" value="<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>">
            <button id="register-document-button" class="button-primary w-full text-sm px-5 py-2.5 flex items-center justify-center gap-2">
              <i class="fa-solid fa-plus text-[11px]"></i> Register & Track Document
            </button>
          </div>
        </div>
      </div>

      <!-- ─── Right Column: Document Tracker ─── -->
      <div class="document-tracker-column lg:col-span-2">
        <div class="flat-card overflow-hidden">

          <div class="tracker-toolbar p-5 border-b border-[var(--line)]">
            <div class="toolbar-top-row flex items-center justify-between mb-4 flex-wrap gap-3">
              <div class="tracker-title-group flex items-center gap-2.5">
                <div class="tracker-icon w-8 h-8 rounded-lg bg-[var(--accent-soft)] text-[var(--accent)] flex items-center justify-center text-[13px]">
                  <i class="fa-solid fa-satellite-dish"></i>
                </div>
                <h2 class="tracker-heading text-base font-bold">Document Tracker</h2>
              </div>
              <div class="search-wrapper relative w-full sm:w-60">
                <input type="text" id="document-search-input" placeholder="Search ref no or title..." class="flat-field w-full pl-8 pr-3 py-2 text-sm">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)] text-[11px]"></i>
              </div>
            </div>

            <!-- Type Filter Chips -->
            <div class="type-filter-row flex items-center gap-2 overflow-x-auto pb-2">
              <button class="filter-chip is-selected px-3.5 py-1.5 text-[11px] whitespace-nowrap" data-filter="ALL">All</button>
              <button class="filter-chip px-3.5 py-1.5 text-[11px] whitespace-nowrap" data-filter="IM">Internal Memo</button>
              <button class="filter-chip px-3.5 py-1.5 text-[11px] whitespace-nowrap" data-filter="EL">External Letters</button>
              <button class="filter-chip px-3.5 py-1.5 text-[11px] whitespace-nowrap" data-filter="SO">Special Order</button>
              <button class="filter-chip px-3.5 py-1.5 text-[11px] whitespace-nowrap" data-filter="OO">Office Order</button>
              <button class="filter-chip px-3.5 py-1.5 text-[11px] whitespace-nowrap" data-filter="MOA">MOA/MOU</button>
            </div>

            <!-- Date Filters -->
            <div class="date-filter-row flex items-center gap-3 mt-3">
              <span class="date-filter-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold flex-shrink-0">Period:</span>
              <select id="month-filter-selector" class="flat-field px-2.5 py-1.5 text-[11px] cursor-pointer w-full sm:w-auto">
                <option value="ALL">All Months</option>
                <option value="0">January</option><option value="1">February</option><option value="2">March</option>
                <option value="3">April</option><option value="4">May</option><option value="5">June</option>
                <option value="6">July</option><option value="7">August</option><option value="8">September</option>
                <option value="9">October</option><option value="10">November</option><option value="11">December</option>
              </select>
              <select id="year-filter-selector" class="flat-field px-2.5 py-1.5 text-[11px] cursor-pointer w-full sm:w-auto">
				<option value="ALL">All Years</option>

				<?php
				$currentYear = date('Y');

				for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
					$selected = ($year == $currentYear) ? 'selected' : '';
					echo "<option value=\"$year\" $selected>$year</option>";
				}
				?>
			</select>
            </div>
          </div>

          <!-- Document Table -->
          <div class="document-table-wrapper overflow-x-auto">
            <table class="document-table w-full text-sm">
              <thead>
                <tr class="table-header-row text-left text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold bg-[var(--bg)]">
                  <th class="px-5 py-3">Reference No.</th>
                  <th class="px-5 py-3">Title / Subject</th>
                  <th class="px-5 py-3 hidden md:table-cell">Date</th>
                  <th class="px-5 py-3">Status</th>
                  <th class="px-5 py-3 text-right w-12"></th>
                </tr>
              </thead>
              <tbody id="document-table-body"></tbody>
            </table>
            <div id="no-documents-message" class="text-center py-14 px-6">
              <div class="empty-state-icon w-14 h-14 rounded-full bg-[var(--bg)] border border-[var(--line)] flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-folder-open text-xl text-[var(--line)]"></i>
              </div>
              <p class="empty-state-text text-sm font-medium text-[var(--muted)]">No documents yet. Register your first document using the generator.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================== PAGE FOOTER ======================== -->
    <footer class="page-footer mt-4 flex items-center justify-between text-[11px] text-[var(--muted)] flex-wrap gap-2 pb-6">
      <div class="footer-attribution">Department of Science and Technology · Region V · Bicol</div>
      <div class="footer-version flex items-center gap-2">
        <span class="version-dot w-1.5 h-1.5 rounded-full bg-[var(--accent)]"></span>
        <span>Communication Management System v1.1</span>
      </div>
    </footer>
  </div>

  <!-- ======================== FORMAT CONFIGURATION MODAL ======================== -->
  <div id="reference-format-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="modal-overlay absolute inset-0" id="modal-overlay-backdrop"></div>
    <div class="modal-panel flat-card relative bg-white p-6 w-full max-w-lg">
      <div class="modal-header-row flex items-center justify-between mb-6">
        <div class="modal-title-group flex items-center gap-2.5">
          <div class="modal-icon w-8 h-8 rounded-lg bg-[var(--accent-soft)] text-[var(--accent)] flex items-center justify-center text-[13px]">
            <i class="fa-solid fa-sliders"></i>
          </div>
          <h2 class="modal-heading text-base font-bold">Configure Reference Format</h2>
        </div>
        <button id="close-modal-button" class="modal-close-trigger text-[var(--muted)] hover:text-[var(--ink)] p-1.5 rounded-md hover:bg-[var(--bg)] transition-colors">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="modal-form-body space-y-4">
        <div class="modal-form-group">
          <label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Prefix (Region/Office)</label>
          <input id="format-prefix-input" type="text" value="R05-CMS" class="flat-field w-full px-3 py-2.5 text-sm font-mono">
        </div>
        <div class="modal-form-row grid grid-cols-2 gap-3">
          <div class="modal-form-group">
            <label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Separator</label>
            <input id="format-separator-input" type="text" value="-" class="flat-field w-full px-3 py-2.5 text-sm font-mono">
          </div>
          <div class="modal-form-group">
            <label class="form-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold block mb-1.5">Sequence Padding</label>
            <input id="format-padding-input" type="number" min="1" max="6" value="4" class="flat-field w-full px-3 py-2.5 text-sm">
          </div>
        </div>
        <div class="modal-checkbox-row flex items-center gap-3 pt-1">
          <input type="checkbox" id="format-include-year-checkbox" class="w-4 h-4 accent-[var(--accent)] rounded" checked>
          <label for="format-include-year-checkbox" class="text-sm font-medium cursor-pointer">Include Current Year in Format</label>
        </div>

        <div class="format-preview-box p-4 rounded-lg bg-[var(--bg)] border border-dashed border-[var(--line)]">
          <div class="preview-label text-[10px] uppercase tracking-wider text-[var(--muted)] font-bold mb-1">Live Preview</div>
          <div id="format-preview-display" class="preview-value font-mono text-base font-bold text-[var(--accent)] break-all leading-snug">—</div>
        </div>
        <button id="save-format-button" class="button-primary w-full text-sm px-5 py-2.5 flex items-center justify-center gap-2">
          <i class="fa-solid fa-check text-[11px]"></i> Save Configuration
        </button>
      </div>
    </div>
  </div>

  <!-- Toast Notification Area -->
  <div id="notification-toast-area" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

  <script src="js/utils/dom.js"></script>
  <script src="js/utils/dates.js"></script>
  <script src="js/utils/formatter.js"></script>
  <script src="js/ui/toast.js"></script>
  <script src="js/services/documentService.js"></script>
  <script src="js/ui/filters.js"></script>
  <script src="js/ui/modal.js"></script>
  <script src="js/ui/table.js"></script>
  <script src="js/ui/timeline.js"></script>
  <script src="js/app.js"></script>
</body>
</html>
