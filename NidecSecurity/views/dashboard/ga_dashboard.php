
<style>
    /* 1. Force the table to obey set widths and center titles */
    .table-container table.ga-staff-table {
        table-layout: fixed !important;
        width: 100% !important;
        border-collapse: collapse;
    }

    /* 2. Center all Table Headers */
    .ga-staff-table th { 
        text-align: center !important; 
        font-size: 0.875rem !important;
        padding: 12px 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }

    /* 3. Center all Data Cells by default */
    .ga-staff-table td {
        text-align: center !important;
        vertical-align: middle;
        font-size: 0.875rem !important;
        padding: 12px 8px;
    }

    /* 4. Keep ONLY the Subject left-aligned for readability */
    .ga-staff-table td.subject-cell {
        text-align: left !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* 5. Define Column Widths for the GA President table */
    .ga-staff-table th:nth-child(1) { width: 110px; } /* Report ID */
    .ga-staff-table th:nth-child(2) { width: auto;  } /* Subject (Flexible) */
    .ga-staff-table th:nth-child(3) { width: 150px; } /* Department */
    .ga-staff-table th:nth-child(4) { width: 100px; } /* Severity */
    .ga-staff-table th:nth-child(5) { width: 140px; } /* Status */
    .ga-staff-table th:nth-child(6) { width: 120px; } /* Date */

    kpi-card {
    /* 3-Stop Linear Gradient at 135 degrees for maximum depth */
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 45%, #020617 100%) !important;
    
    /* Ultra-subtle Rim Light - 1px border with very low white opacity */
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    
    /* Double-layered shadow: one for elevation, one for 'weight' */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 
                0 10px 20px -5px rgba(0, 0, 0, 0.7) !important;
    
    border-radius: 16px !important;
}
</style>


<main class="main-content">
    <div class="animate-fade-in">
        <div class="mt-3 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1">Dashboard</h1>
                <p class="text-sm text-muted-foreground mb-0">Welcome back, <?php echo htmlspecialchars($user['displayName'] ?? $user['username']); ?>.</p>
            </div>
            <div class="d-flex align-items-center gap-2" style="margin-top: 14px;">
                <span class="text-xs text-muted-foreground">Building</span>
                <select id="building-filter" class="form-select form-select-sm" style="min-width: 160px;">
                    <option value="all" <?php echo $selectedBuilding === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="NCFL" <?php echo $selectedBuilding === 'NCFL' ? 'selected' : ''; ?>>NCFL</option>
                    <option value="NPFL" <?php echo $selectedBuilding === 'NPFL' ? 'selected' : ''; ?>>NPFL</option>
                </select>
            </div>
        </div>

        <?php if (($userRole ?? '') === 'ga_president'): ?>
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-lg-3">
            <button type="button" class="metric-card metric-card-split metric-accent-warning w-100" data-metric="pending" data-title="Pending GA Approval">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Pending GA Approval</p>
                        <p class="text-xs text-muted-foreground">Awaiting president decision</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int)($presidentStats['pending_ga'] ?? 0); ?></div>
                </div>
            </button>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
            <button type="button" class="metric-card metric-card-split metric-accent-destructive w-100" data-metric="critical" data-title="Critical Severity">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Critical Severity</p>
                        <p class="text-xs text-muted-foreground">Highest priority incidents</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int)($presidentStats['critical'] ?? 0); ?></div>
                </div>
            </button>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
            <button type="button" class="metric-card metric-card-split metric-accent-success w-100" data-metric="in_progress" data-title="Reports In Progress">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Reports In Progress</p>
                        <p class="text-xs text-muted-foreground">Active fixes & final checks</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int)($presidentStats['in_progress'] ?? 0); ?></div>
                </div>
            </button>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
            <button type="button" class="metric-card metric-card-split metric-accent-info w-100" data-metric="overdue" data-title="Overdue Tasks">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Overdue Tasks</p>
                        <p class="text-xs text-muted-foreground">Past due department timelines</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int)($presidentStats['overdue'] ?? 0); ?></div>
                </div>
            </button>
            </div>
        </div>

        <div class="table-container table-card" style="--table-accent: var(--primary)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Recent Reports</h3>
                    <p class="text-xs text-muted-foreground">Latest submissions across all departments</p>
                </div>
                <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(app_url('reports.php')); ?>">View all</a>
            </div>
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 ga-staff-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Report ID</th>
                        <th>Subject</th>
                        <th style="width: 18%;">Department</th>
                        <th style="width: 10%;">Severity</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 16%;">Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($presidentRecent)): ?>
                        <tr><td colspan="6" class="text-center text-muted-foreground">No reports found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($presidentRecent as $r): ?>
                            <?php
                                $sevRaw = strtolower((string)($r['severity'] ?? ''));
                                $sevBadge = 'badge--muted';
                                if ($sevRaw === 'critical') $sevBadge = 'badge--destructive';
                                elseif ($sevRaw === 'high') $sevBadge = 'badge--warning';
                                elseif ($sevRaw === 'medium') $sevBadge = 'badge--info';
                                elseif ($sevRaw === 'low') $sevBadge = 'badge--muted';

                                $statusRaw = (string)($r['status'] ?? '');
                                $statusBadge = 'badge--muted';
                                if (in_array($statusRaw, ['submitted_to_ga_president', 'submitted_to_ga_staff'], true)) {
                                    $statusBadge = 'badge--warning';
                                } elseif (in_array($statusRaw, ['under_department_fix', 'sent_to_department', 'ga_staff_reviewed'], true)) {
                                    $statusBadge = 'badge--info';
                                } elseif ($statusRaw === 'for_security_final_check') {
                                    $statusBadge = 'badge--primary';
                                } elseif ($statusRaw === 'returned_to_department') {
                                    $statusBadge = 'badge--destructive';
                                } elseif (in_array($statusRaw, ['approved_by_ga_president', 'resolved'], true)) {
                                    $statusBadge = 'badge--success';
                                }
                            ?>
                            <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                                
                                <td class="subject-cell font-medium text-truncate" style="max-width: 260px;">
                                    <?php echo htmlspecialchars($r['subject']); ?>
                                </td>
                                
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['department_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($sevBadge); ?>">
                                        <?php echo htmlspecialchars(severity_label($r['severity'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($statusBadge); ?>">
                                        <?php echo htmlspecialchars(report_status_label($r['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(date('M d, Y', strtotime($r['submitted_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Metric List Modal -->
        <div id="metric-modal-overlay" class="modal-overlay" aria-hidden="true">
            <div id="metric-modal" class="report-modal" role="dialog" aria-modal="true" aria-labelledby="metric-modal-title">
                <div class="metric-modal-header">
                    <h3 id="metric-modal-title">Metric</h3>
                    <button type="button" class="metric-modal-close" onclick="MetricModal.close()" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="report-modal-body" id="metric-modal-body">
                    <div class="text-sm text-muted-foreground">Loading…</div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="metric-modal-footer-action" onclick="MetricModal.close()">Close</button>
                </div>
            </div>
        </div>

        <script>
        (function () {
            const apiUrl = <?php echo json_encode(app_url('api/president_metric_list.php')); ?>;
            const overlay = document.getElementById('metric-modal-overlay');
            const titleEl = document.getElementById('metric-modal-title');
            const bodyEl = document.getElementById('metric-modal-body');

            function escapeHtml(s) {
                return String(s ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatStatus(status) {
                const s = String(status || '').trim();
                if (!s) return '—';
                return s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }

            function formatSeverity(sev) {
                const s = String(sev || '').trim();
                if (!s) return '—';
                return s.charAt(0).toUpperCase() + s.slice(1);
            }

            function formatDate(dt) {
                if (!dt) return '—';
                const d = new Date(dt);
                if (isNaN(d.getTime())) return String(dt);
                return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
            }

            function render(items, type) {
                if (!Array.isArray(items) || items.length === 0) {
                    bodyEl.innerHTML = '<div class="text-sm text-muted-foreground">No records found.</div>';
                    return;
                }

                const showOverdue = (type === 'overdue');
                let html = '';
                html += '<div class="table-container table-responsive">';
                // Use a specific ID so our CSS targets this table correctly
                html += '<table id="staff-dashboard-table" class="table table-hover align-middle mb-0">'; 
                html += '<thead><tr>';
                html += '<th>Report ID</th>';
                html += '<th>Subject</th>';
                html += '<th>Department</th>';
                html += '<th>Severity</th>';
                html += '<th>Status</th>';
                if (showOverdue) {
                    html += '<th>Due</th>';
                    html += '<th>Days Overdue</th>';
                } else {
                    html += '<th>Submitted</th>';
                }
                html += '</tr></thead>';
                html += '<tbody>';

                for (const r of items) {
                    const reportNo = escapeHtml(r.report_no);
                    html += '<tr class="clickable-row" onclick="ReportModal.open(\'' + reportNo + '\')">';
                    html += '<td class="font-mono text-xs font-medium">' + reportNo + '</td>';
                    
                    // ADDED: subject-cell class here to keep the subject left-aligned
                    html += '<td class="subject-cell font-medium">' + escapeHtml(r.subject) + '</td>';
                    
                    html += '<td class="text-muted-foreground">' + escapeHtml(r.department) + '</td>';
                    html += '<td class="text-muted-foreground">' + escapeHtml(formatSeverity(r.severity)) + '</td>';
                    html += '<td class="text-muted-foreground">' + escapeHtml(formatStatus(r.status)) + '</td>';
                    
                    if (showOverdue) {
                        html += '<td class="text-muted-foreground text-xs">' + escapeHtml(formatDate(r.fix_due_date)) + '</td>';
                        html += '<td class="text-muted-foreground">' + escapeHtml(r.days_overdue ?? 0) + '</td>';
                    } else {
                        html += '<td class="text-muted-foreground text-xs">' + escapeHtml(formatDate(r.submitted_at)) + '</td>';
                    }
                    html += '</tr>';
                }

                html += '</tbody></table></div>';
                bodyEl.innerHTML = html;
            }

            window.MetricModal = {
                open(type, title) {
                    titleEl.textContent = title || 'Metric';
                    bodyEl.innerHTML = '<div class="text-sm text-muted-foreground">Loading…</div>';
                    overlay.classList.add('active');
                    overlay.setAttribute('aria-hidden', 'false');

                    const pageUrl = new URL(window.location.href);
                    const url = new URL(apiUrl, window.location.origin);
                    url.searchParams.set('type', String(type || ''));
                    const building = pageUrl.searchParams.get('building');
                    if (building) url.searchParams.set('building', building);

                    fetch(url.toString(), { credentials: 'same-origin' })
                        .then(r => r.json().then(j => ({ ok: r.ok, status: r.status, json: j })))
                        .then(({ ok, json }) => {
                            if (!ok) throw new Error(json && json.error ? json.error : 'Request failed');
                            render(json.items || [], json.type || type);
                        })
                        .catch(err => {
                            bodyEl.innerHTML = '<div class="alert alert-danger alert-error">' + escapeHtml(err.message || 'Failed to load data') + '</div>';
                        });
                },
                close() {
                    overlay.classList.remove('active');
                    overlay.setAttribute('aria-hidden', 'true');
                }
            };

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) window.MetricModal.close();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('active')) window.MetricModal.close();
            });

            document.querySelectorAll('.metric-card[data-metric]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    window.MetricModal.open(btn.getAttribute('data-metric'), btn.getAttribute('data-title'));
                });
            });
        })();
        </script>
        <?php else: ?>

        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-lg-4">
            <a class="metric-card metric-card-split metric-accent-warning no-underline w-100" href="<?php echo htmlspecialchars(app_url('ga-staff-review.php')); ?>">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Waiting for Review</p>
                        <p class="text-xs text-muted-foreground">Submitted by Security</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int)($gaStaffCounts['waiting'] ?? 0); ?></div>
                </div>
            </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
            <a class="metric-card metric-card-split metric-accent-destructive no-underline w-100" href="<?php echo htmlspecialchars(app_url('returned-reports.php')); ?>">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Returned by President</p>
                        <p class="text-xs text-muted-foreground">Edit and resubmit</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int)($gaStaffCounts['returned'] ?? 0); ?></div>
                </div>
            </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
            <a class="metric-card metric-card-split metric-accent-info no-underline w-100" href="<?php echo htmlspecialchars(app_url('reports.php')); ?>">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Forwarded to President</p>
                        <p class="text-xs text-muted-foreground">Sent for final approval</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int)($gaStaffCounts['forwarded'] ?? 0); ?></div>
                </div>
            </a>
            </div>
        </div>

        <div class="table-container table-card mb-4" style="--table-accent: var(--warning)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Reports Waiting for Review</h3>
                    <p class="text-xs text-muted-foreground">Most urgent items for GA Staff</p>
                </div>
                <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(app_url('ga-staff-review.php')); ?>">Open review queue</a>
            </div>
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Severity</th>
                        <th>Date Submitted</th>
                        <th>Submitted by Security</th>
                        <th style="width: 110px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gaStaffWaiting)): ?>
                        <tr><td colspan="7" class="text-center text-muted-foreground">No reports waiting for review.</td></tr>
                    <?php else: ?>
                        <?php foreach ($gaStaffWaiting as $r): ?>
                            <?php
                                $sevRaw = strtolower((string)($r['severity'] ?? ''));
                                $sevBadge = 'badge--muted';
                                if ($sevRaw === 'critical') $sevBadge = 'badge--destructive';
                                elseif ($sevRaw === 'high') $sevBadge = 'badge--warning';
                                elseif ($sevRaw === 'medium') $sevBadge = 'badge--info';
                                elseif ($sevRaw === 'low') $sevBadge = 'badge--muted';
                            ?>
                            <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                                <td class="font-medium text-truncate" style="max-width: 260px;"><?php echo htmlspecialchars($r['subject']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['department_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($sevBadge); ?>"><?php echo htmlspecialchars(severity_label((string)$r['severity'])); ?></span>
                                </td>
                                <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(date('M d, Y', strtotime($r['submitted_at']))); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['submitted_by_name'] ?? '—'); ?></td>
                                <td onclick="event.stopPropagation();">
                                    <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(app_url('ga-staff-review.php')); ?>">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="table-container table-card" style="--table-accent: var(--destructive)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Returned by President</h3>
                    <p class="text-xs text-muted-foreground">Items that need edits before resubmission</p>
                </div>
                <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(app_url('returned-reports.php')); ?>">Open returned list</a>
            </div>
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Severity</th>
                        <th>Returned At</th>
                        <th>President Feedback</th>
                        <th style="width: 110px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gaStaffReturned)): ?>
                        <tr><td colspan="7" class="text-center text-muted-foreground">No returned reports.</td></tr>
                    <?php else: ?>
                        <?php foreach ($gaStaffReturned as $r): ?>
                            <?php
                                $sevRaw = strtolower((string)($r['severity'] ?? ''));
                                $sevBadge = 'badge--muted';
                                if ($sevRaw === 'critical') $sevBadge = 'badge--destructive';
                                elseif ($sevRaw === 'high') $sevBadge = 'badge--warning';
                                elseif ($sevRaw === 'medium') $sevBadge = 'badge--info';
                                elseif ($sevRaw === 'low') $sevBadge = 'badge--muted';
                            ?>
                            <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                                <td class="font-medium text-truncate" style="max-width: 260px;"><?php echo htmlspecialchars($r['subject']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['department_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($sevBadge); ?>"><?php echo htmlspecialchars(severity_label((string)$r['severity'])); ?></span>
                                </td>
                                <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars($r['returned_at'] ? date('M d, Y', strtotime($r['returned_at'])) : '—'); ?></td>
                                <td class="text-muted-foreground text-truncate" style="max-width: 260px;"><?php echo htmlspecialchars($r['president_notes'] ?? '—'); ?></td>
                                <td onclick="event.stopPropagation();">
                                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(app_url('returned-reports.php')); ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <?php endif; ?>
    </div>
</main>

<script>
    (function () {
        const el = document.getElementById('building-filter');
        if (!el) return;
        el.addEventListener('change', () => {
            const val = el.value;
            const url = new URL(window.location.href);
            if (val === 'all') url.searchParams.delete('building');
            else url.searchParams.set('building', val);
            window.location.href = url.toString();
        });
    })();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
