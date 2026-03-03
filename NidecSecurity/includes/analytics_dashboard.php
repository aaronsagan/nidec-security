<?php
if (!function_exists('app_url')) {
    http_response_code(500);
    die('Missing config.');
}

$user = getUser();
$role = (string)($user['role'] ?? '');
$deptId = (int)($user['department_id'] ?? 0);
$userBuilding = normalize_building($user['building'] ?? null);

$canSeeAll = in_array($role, ['ga_president', 'ga_staff', 'security'], true);
$canChooseBuilding = in_array($role, ['ga_president', 'ga_staff', 'department'], true);
$departmentsDb = fetch_departments();

$apiUrl = app_url('api/analytics.php');
?>

<style>
    /* 1. FILTER COLLAPSIBLE LOGIC */
    #filter-collapsible-content {
        max-height: 500px;
        transition: all 0.3s ease-in-out;
        overflow: hidden;
    }
    #filter-collapsible-content.collapsed {
        max-height: 0 !important;
        margin-top: 0 !important;
        opacity: 0;
        pointer-events: none;
    }
    #filter-chevron.rotated {
        transform: rotate(-180deg);
    }

    /* 2. TAB BAR TRACK (The rounded grey background) */
    .tabs-bar {
        display: flex !important;
        width: 100% !important;
        gap: 4px !important;
        background-color: #f1f5f9 !important; 
        padding: 6px !important;
        border-radius: 12px !important;
        margin-bottom: 24px !important;
        overflow: hidden !important; 
    }

    /* 3. TAB BUTTONS - BALANCED FOR SPACE & READABILITY */
    #analytics-tabs button.tab-btn {
        flex: 1 !important;
        background-color: transparent !important;
        color: #64748b !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 10px 4px !important; 
        font-size: 0.75rem !important; 
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.02em !important;
        border: none !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        white-space: nowrap !important;
        outline: none !important;
    }

    /* 4. ACTIVE TAB STATE - VIVID GREEN WITH PURE WHITE TEXT */
    #analytics-tabs button.tab-btn.active {
        background-color: #22c55e !important; 
        color: #ffffff !important;           
        font-weight: 800 !important;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3) !important;
    }

    /* 5. TAB HOVER STATE */
    #analytics-tabs button.tab-btn:hover:not(.active) {
        background-color: rgba(0, 0, 0, 0.05) !important;
        color: #1e293b !important;
    }

    /* 6. KPI CARDS - READABLE TEXT FOR 2-ROW / 3-CARD LAYOUT */
    .kpi-card i {
        font-size: 1.3rem;
        display: flex;
        align-items: center;
    }

    .kpi-value {
        font-size: 2rem !important; 
        font-weight: 800 !important;
        color: #1e293b;
        margin: 5px 0 !important;
        text-align: left !important; 
    }

    .kpi-label {
      font-size: 0.95rem !important; 
        font-weight: 700 !important;
      color: #111;
      text-transform: none;
      letter-spacing: 0.01em;
    }

    .kpi-sub {
        font-size: 0.8rem !important; 
      color: #6c757d;
        line-height: 1.4;
    }

    /* 7. HEADER RANGE TEXT - REMOVED CAPSLOCK */
    #analytics-range {
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: none !important; /* Forces normal casing */
    }

    /* 8. RESPONSIVE FIX */
    @media (max-width: 1100px) {
        .tabs-bar { 
            overflow-x: auto !important; 
            flex-wrap: nowrap !important; 
            scrollbar-width: none; 
        }
        .tabs-bar::-webkit-scrollbar { display: none; }
        #analytics-tabs button.tab-btn { min-width: 140px !important; }
    }
</style>

<main class="main-content">
  <div class="animate-fade-in" id="analytics-dashboard" data-api-url="<?php echo htmlspecialchars($apiUrl); ?>">

    <div class="mb-4">
      <h1 class="h4 fw-bold text-foreground mb-1">Executive Analytics Dashboard</h1>
      <p class="text-sm text-muted-foreground mb-0">System performance, risk profile, and trend tracking</p>
    </div>




    <div class="section-card section-accent-primary mb-4">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap cursor-pointer" id="toggle-filters-btn" style="user-select: none;">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="3">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            <h3 class="font-semibold text-foreground uppercase mt-1" style="font-size: 0.9rem; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 0; margin-left: 10px;">Filter Analytics</h3>
        </div>
        <div class="d-flex align-items-center gap-2 text-muted-foreground transition-colors">
            <span class="text-xs font-bold uppercase" id="filter-status-text">Hide Filters</span>
            <svg id="filter-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="transition-transform duration-200">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </div>
    </div>

    <div id="filter-collapsible-content" class="mt-4 transition-all duration-300 overflow-hidden">
        <form id="analytics-filters" class="row g-2 align-items-end" onsubmit="return false;">
            
            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Start Date</label>
                <input type="date" class="form-control form-control-sm border-0 shadow-sm" name="start_date" id="af-start" 
                       style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; height: 38px;" />
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">End Date</label>
                <input type="date" class="form-control form-control-sm border-0 shadow-sm" name="end_date" id="af-end" 
                       style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; height: 38px;" />
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Building</label>
                <?php if ($role === 'security'): ?>
                    <div class="analytics-readonly px-2 d-flex align-items-center" style="background-color: #e9ecef; border-radius: 6px; height: 38px;">
                        <span class="text-sm fw-bold text-muted-foreground"><?php echo htmlspecialchars($userBuilding ?: '—'); ?></span>
                    </div>
                    <input type="hidden" name="building" id="af-building" value="<?php echo htmlspecialchars($userBuilding ?: ''); ?>" />
                <?php elseif ($canChooseBuilding): ?>
                    <select name="building" id="af-building" class="form-select form-select-sm border-0 shadow-sm" 
                            style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                        <option value="">All Buildings</option>
                        <option value="NCFL">NCFL</option>
                        <option value="NPFL">NPFL</option>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="building" id="af-building" value="" />
                <?php endif; ?>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Department</label>
                <?php if ($canSeeAll): ?>
                    <select name="department_id" id="af-dept" class="form-select form-select-sm border-0 shadow-sm" 
                            style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                        <option value="0">All Departments</option>
                        <?php foreach ($departmentsDb as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="analytics-readonly px-2 d-flex align-items-center" style="background-color: #e9ecef; border-radius: 6px; height: 38px;">
                        <span class="text-sm fw-bold text-muted-foreground">Your Dept Only</span>
                    </div>
                    <input type="hidden" name="department_id" id="af-dept" value="<?php echo (int)$deptId; ?>" />
                <?php endif; ?>
            </div>

            <div class="col-12 col-md-3">
                <button type="button" id="apply-filters-btn" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2" 
                        style="background-color: #28a745 !important; color: white !important; border: none; border-radius: 6px; height: 38px; transition: 0.2s;">
                    <i class="bi bi-arrow-clockwise" style="-webkit-text-stroke: 1px;"></i>
                    REFRESH ANALYTICS
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- Tabs -->
    <div class="tabs-bar" id="analytics-tabs" role="tablist" aria-label="Analytics Sections">
    <button type="button" class="tab-btn active" role="tab" aria-selected="true" data-tab="metrics">Key Metrics</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="trend">Report Trend</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="severity">Severity Distribution</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="department">Reports by Department</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="timeline">Timeline Performance</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="overdue">Overdue Alerts</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="download">Download</button>
    </div>

    <!-- SECTION 1 — KPI PERFORMANCE CARDS -->
    <section class="analytics-panel" data-tab-panel="metrics">
  <div class="d-flex align-items-end justify-content-between pb-2 mb-3 border-bottom">
    <div>
      <h2 class="text-lg font-bold text-foreground mb-0">Executive Key Performance Indicators</h2>
    </div>
    <div class="text-end">
      <p class="text-xs fw-bold text-muted-foreground mb-0 tracking-tight" id="analytics-range" style="text-transform: none !important;">
        Range: 2026-02-01 to 2026-03-02 • Department: All Departments
      </p>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-accent-primary h-100 p-3">
        <i class="bi bi-file-earmark-text text-primary mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Total Reports</div>
          <div class="kpi-value" id="kpi-total">0</div>
        </div>
        <div class="kpi-sub mt-1">Within selected filters</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-accent-warning h-100 p-3">
        <i class="bi bi-envelope-open text-warning mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Open Reports</div>
          <div class="kpi-value" id="kpi-open">0</div>
        </div>
        <div class="kpi-sub mt-1">Not yet fully resolved</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-accent-success h-100 p-3">
        <i class="bi bi-check-circle text-success mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Resolved Reports</div>
          <div class="kpi-value" id="kpi-resolved">0</div>
        </div>
        <div class="kpi-sub mt-1">Closed within the range</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-accent-destructive h-100 p-3">
        <i class="bi bi-exclamation-octagon text-danger mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Overdue Reports</div>
          <div class="kpi-value" id="kpi-overdue">0</div>
        </div>
        <div class="kpi-sub mt-1">Past due while under fix</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-accent-info h-100 p-3">
        <i class="bi bi-clock-history text-info mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Avg Resolution Time</div>
          <div class="kpi-value" id="kpi-avg-days">N/A</div>
        </div>
        <div class="kpi-sub mt-1">Resolved reports only</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-accent-destructive h-100 p-3">
        <i class="bi bi-shield-shaded text-danger mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">High Severity</div>
          <div class="kpi-value" id="kpi-high-sev">0</div>
        </div>
        <div class="kpi-sub mt-1">High + Critical severity</div>
      </div>
    </div>
  </div>
</section>
    <!-- SECTION 2 — REPORT TREND GRAPH (LINE CHART) -->
    <section class="analytics-panel hidden" data-tab-panel="trend">
      <div class="section-card section-accent-primary chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Report Trend</h2>
            <p class="chart-subtitle" id="subtitle-trend">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-8">
            <div class="chart-wrap h-100">
              <canvas id="chart-trend" height="320"></canvas>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">View</div>
                <select id="trend-mode" class="form-select form-select-sm w-100">
                  <option value="daily">Daily (Last 7 Days)</option>
                  <option value="weekly">Weekly (Last 4 Weeks)</option>
                  <option value="monthly">Monthly (Last 12 Months)</option>
                </select>
                <div class="text-sm text-muted-foreground mt-2">Switch time scale to compare patterns.</div>
              </div>

              <div class="insight-card hidden" id="insight-trend" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-trend"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="severity">
      <div class="section-card section-accent-warning chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Severity Distribution</h2>
            <p class="chart-subtitle" id="subtitle-severity">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-7">
            <div class="chart-wrap h-100">
              <canvas id="chart-severity" height="320"></canvas>
            </div>
          </div>
          <div class="col-12 col-lg-5">
            <div class="analytics-side-stack">
              <div>
                <div class="analytics-side-title">Legend</div>
                <div class="chart-legend" id="severity-legend"></div>
              </div>
              <div class="insight-card hidden" id="insight-severity" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-severity"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="department">
      <div class="section-card section-accent-info chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Reports by Department</h2>
            <p class="chart-subtitle" id="subtitle-department">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-8">
            <div class="chart-wrap h-100">
              <canvas id="chart-department" height="320"></canvas>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">Reading</div>
                <div class="text-sm text-muted-foreground">Highlights the departments contributing most reports within the selected filters.</div>
              </div>

              <div class="insight-card hidden" id="insight-department" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-department"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 5 — TIMELINE PERFORMANCE -->
    <section class="analytics-panel hidden" data-tab-panel="timeline">
      <div class="section-card section-accent-success chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Timeline Performance</h2>
            <p class="chart-subtitle" id="subtitle-timeline">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-8">
            <div class="chart-wrap h-100">
              <canvas id="chart-timeline" height="260"></canvas>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">Compliance</div>
                <div class="analytics-side-metric">
                  <div class="label">Rate</div>
                  <div class="value" id="timeline-rate">N/A</div>
                </div>
                <div class="text-sm text-muted-foreground mt-2">Measures on-time completion vs. overdue work.</div>
              </div>

              <div class="insight-card hidden" id="insight-timeline" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-timeline"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 6 — OVERDUE ALERT TABLE -->
    <section class="analytics-panel hidden" data-tab-panel="overdue">
    <div class="section-card section-accent-destructive chart-card">
      <div class="analytics-chart-header">
        <div>
          <h2 class="text-lg font-bold text-foreground">Overdue Alerts</h2>
          <p class="chart-subtitle">Items past due while still under fix</p>
        </div>
      </div>

      <div class="row g-3 align-items-stretch">
        <div class="col-12 col-xl-8">
          <div class="table-container table-card h-100" style="--table-accent: var(--destructive);">
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Report ID</th>
                  <th>Department</th>
                  <th>Due Date</th>
                  <th>Days Overdue</th>
                </tr>
              </thead>
              <tbody id="overdue-body">
                <tr><td colspan="4" class="text-center text-muted-foreground">Loading…</td></tr>
              </tbody>
            </table>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="analytics-side-stack">
            <div class="analytics-side-card">
              <div class="analytics-side-title">Action</div>
              <div class="text-sm text-muted-foreground">Use this list to prioritize follow-ups with departments and verify evidence uploads once fixes are completed.</div>
            </div>
            <div class="analytics-side-card">
              <div class="analytics-side-title">Tip</div>
              <div class="text-sm text-muted-foreground">Narrow results using filters (date range, building, department) to focus on a specific area.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </section>

    <!-- DOWNLOAD SECTION (MOVE BELOW DASHBOARD) -->
    <section class="analytics-panel hidden" data-tab-panel="download">
      <div class="section-card section-accent-primary">
        <h2 class="text-lg font-bold text-foreground mb-2">Download</h2>
        <p class="text-sm text-muted-foreground mb-4">Export based on the selected filters</p>

        <div class="row g-3 align-items-start">
          <div class="col-12 col-lg-7">
            <div class="analytics-side-card">
              <div class="analytics-side-title">What’s included</div>
              <div class="text-sm text-muted-foreground">Exports reflect the current filters and tab selections. Use CSV for analysis and PDF for reporting.</div>
              <div class="text-xs text-muted-foreground mt-2" id="download-hint"></div>
            </div>
          </div>
          <div class="col-12 col-lg-5">
            <div class="analytics-side-stack">
              <a class="btn btn-outline w-100" id="download-csv" href="#">Download CSV</a>
              <a class="btn btn-outline w-100" id="download-pdf" href="#">Download PDF</a>
            </div>
          </div>
        </div>
      </div>

    </section>

  </div>
</main>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-filters-btn');
    const content = document.getElementById('filter-collapsible-content');
    const chevron = document.getElementById('filter-chevron');
    const statusText = document.getElementById('filter-status-text');

    if (toggleBtn && content) {
        // Function to toggle the filter state
        function toggleFilters(isManualAction = true) {
            const isCollapsed = content.classList.toggle('collapsed');
            chevron.classList.toggle('rotated', isCollapsed);
            statusText.textContent = isCollapsed ? 'Show Filters' : 'Hide Filters';
            
            if (isManualAction) {
                localStorage.setItem('analytics_filters_collapsed', isCollapsed);
            }
        }

        // Handle click event
        toggleBtn.addEventListener('click', () => toggleFilters(true));

        // Check localStorage to remember user's last preference
        const savedState = localStorage.getItem('analytics_filters_collapsed');
        if (savedState === 'true') {
            content.classList.add('collapsed');
            chevron.classList.add('rotated');
            statusText.textContent = 'Show Filters';
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
