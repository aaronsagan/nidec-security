<style>
    /* 1. TABLE CORE STYLES */
    .approval-table {
        table-layout: fixed !important;
        width: 100% !important;
        border-collapse: collapse;
    }

    /* 2. SPECIFIC COLUMN WIDTHS */
    .approval-table th:nth-child(1) { width: 100px; } /* ID */
    .approval-table th:nth-child(2) { width: auto;  } /* Subject (Flexible) */
    .approval-table th:nth-child(3) { width: 120px; } /* Category */
    .approval-table th:nth-child(4) { width: 140px; } /* Dept */
    .approval-table th:nth-child(5) { width: 100px; } /* Severity */
    .approval-table th:nth-child(6) { width: 110px; } /* Date */
    .approval-table th:nth-child(7) { width: 230px; } /* Actions */

    /* 3. CENTER ALIGN HEADERS - SIZED TO MATCH OTHER PAGES */
    .approval-table th {
        text-align: center !important;
        padding: 12px 8px;
        font-size: 0.875rem !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }

    /* 4. CENTER ALIGN ALL DATA EXCEPT SUBJECT */
    .approval-table td {
        padding: 12px 8px;
        vertical-align: middle;
        text-align: center;
        font-size: 0.875rem !important;
    }

    /* 5. SUBJECT CELL STYLING */
    .approval-table td.subject-cell {
        text-align: left !important;
        font-size: 0.875rem !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Ensure Action buttons don't shrink too much */
    .btn-sm {
        font-size: 0.75rem !important;
        padding: 4px 8px !important;
    }
</style>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-2 d-flex align-items-start justify-content-between gap-10   flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1">GA Pending Reports</h1>
                <p class="text-sm text-muted-foreground mb-0">Review reports pending your final approval</p>
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

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="table-container table-card" style="--table-accent: var(--warning)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Pending Approval</h3>
                    <p class="text-xs text-muted-foreground">Click a row to preview. Use actions to decide.</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int)count($pending); ?></div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 approval-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Department</th>
                            <th>Severity</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr><td colspan="7" class="text-center text-muted-foreground py-12">No pending reports for approval.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending as $r): ?>
                                <?php
                                    $sevRaw = strtolower((string)($r['severity'] ?? ''));
                                    $sevBadge = 'badge--muted';
                                    if ($sevRaw === 'critical') $sevBadge = 'badge--destructive';
                                    elseif ($sevRaw === 'high') $sevBadge = 'badge--warning';
                                    elseif ($sevRaw === 'medium') $sevBadge = 'badge--info';
                                ?>
                                <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
                                    <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                                    <td class="font-medium text-foreground subject-cell" title="<?php echo htmlspecialchars($r['subject']); ?>">
                                        <?php echo htmlspecialchars($r['subject']); ?>
                                    </td>
                                    <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars($r['category']); ?></td>
                                    <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars($r['department_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo htmlspecialchars($sevBadge); ?>">
                                            <?php echo htmlspecialchars(severity_label($r['severity'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(date('M d, Y', strtotime($r['submitted_at']))); ?></td>
                                    <td onclick="event.stopPropagation();">
                                        <div class="action-group">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                                <input type="hidden" name="report_no" value="<?php echo htmlspecialchars($r['report_no']); ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-outline btn-sm"
                                                    data-return-report-no="<?php echo htmlspecialchars($r['report_no']); ?>"
                                                    data-return-subject="<?php echo htmlspecialchars($r['subject']); ?>">Return</button>
                                            <button type="button" class="btn btn-destructive btn-sm"
                                                    data-reject-report-no="<?php echo htmlspecialchars($r['report_no']); ?>"
                                                    data-reject-subject="<?php echo htmlspecialchars($r['subject']); ?>">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div id="ga-action-overlay" class="modal-overlay hidden" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ga-reject-title">
        <div class="modal-header">
            <h2 id="ga-reject-title" class="text-lg font-semibold text-foreground">Action</h2>
            <p id="ga-action-subtitle" class="text-sm text-muted-foreground mt-1">Please provide a reason.</p>
        </div>
        <div class="modal-section" style="margin-bottom: 0.75rem;">
            <div class="text-xs text-muted-foreground" id="ga-reject-meta"></div>
        </div>
        <label class="text-sm font-medium text-foreground" for="ga-reject-reason">Reason</label>
        <textarea id="ga-reject-reason" class="form-control mt-2" placeholder="Type the reason..." maxlength="500" style="resize: vertical; min-height: 100px;" required></textarea>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-sm" id="ga-reject-cancel">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="ga-action-submit">Submit</button>
        </div>
    </div>
</div>

<form id="ga-action-form" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="report_no" value="" />
    <input type="hidden" name="notes" value="" />
</form>

<script>
(() => {
    const overlay = document.getElementById('ga-action-overlay');
    const meta = document.getElementById('ga-reject-meta');
    const reason = document.getElementById('ga-reject-reason');
    const cancelBtn = document.getElementById('ga-reject-cancel');
    const submitBtn = document.getElementById('ga-action-submit');
    const subtitle = document.getElementById('ga-action-subtitle');
    const title = document.getElementById('ga-reject-title');
    const form = document.getElementById('ga-action-form');

    if (!overlay || !reason || !cancelBtn || !submitBtn || !form) return;

    let currentReportNo = '';
    let currentSubject = '';
    let currentAction = '';

    function openModal(action, reportNo, subject) {
        currentAction = action;
        currentReportNo = reportNo;
        currentSubject = subject;

        if (action === 'return') {
            title.textContent = 'Return Report';
            subtitle.textContent = 'Explain why this needs revision by GA Staff.';
            submitBtn.textContent = 'Confirm Return';
            submitBtn.className = 'btn btn-primary btn-sm';
        } else {
            title.textContent = 'Reject Report';
            subtitle.textContent = 'Explain why this report is being rejected.';
            submitBtn.textContent = 'Confirm Reject';
            submitBtn.className = 'btn btn-destructive btn-sm';
        }

        meta.textContent = `Report: ${reportNo} - ${subject}`;
        reason.value = '';
        overlay.classList.remove('hidden');
        overlay.classList.add('active');
        setTimeout(() => reason.focus(), 50);
    }

    function closeModal() {
        overlay.classList.remove('active');
        overlay.classList.add('hidden');
    }

    document.querySelectorAll('[data-reject-report-no]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('reject', btn.getAttribute('data-reject-report-no'), btn.getAttribute('data-reject-subject'));
        });
    });

    document.querySelectorAll('[data-return-report-no]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('return', btn.getAttribute('data-return-report-no'), btn.getAttribute('data-return-subject'));
        });
    });

    cancelBtn.addEventListener('click', closeModal);

    submitBtn.addEventListener('click', () => {
        const val = reason.value.trim();
        if (!val) return alert('Reason is required');
        form.querySelector('[name="action"]').value = currentAction;
        form.querySelector('[name="report_no"]').value = currentReportNo;
        form.querySelector('[name="notes"]').value = val;
        form.submit();
    });
})();
</script>

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
