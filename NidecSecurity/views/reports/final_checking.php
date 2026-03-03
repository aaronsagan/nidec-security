<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1">Security Final Checking</h1>
            <p class="text-sm text-muted-foreground mb-0">
                Perform re-inspection and confirm resolution of reports
            </p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="table-container table-card" style="--table-accent: var(--destructive)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Reports Awaiting Final Checking</h3>
                    <p class="text-xs text-muted-foreground">Click a row to review and decide</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int)count($reports); ?></div>
            </div>
            <div class="table-responsive">
                <table id="final-check-table" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Severity</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="5" class="text-center text-muted-foreground">No reports awaiting final checking.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr class="clickable-row" onclick="SecurityFinalModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                                <td class="text-truncate fw-medium" style="max-width: 320px;"><?php echo htmlspecialchars($r['subject']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['department_name']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(severity_label($r['severity'])); ?></td>
                                <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(date('M d, Y', strtotime($r['submitted_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Final Checking Action Modal -->
<div id="security-final-modal-overlay" class="modal-overlay">
    <div id="security-final-modal" class="report-modal">
        <div class="report-modal-header">
            <h3 id="security-final-subject">Final Checking</h3>
            <button class="modal-close-btn" type="button" onclick="SecurityFinalModal.close()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="report-modal-body" id="security-final-content">
            <div class="text-sm text-muted-foreground">Loading report details...</div>
        </div>
        <div class="report-modal-footer" style="justify-content: flex-end; gap: 8px;">
            <form method="POST" id="final-return-form" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                <input type="hidden" name="action" value="not_resolved" />
                <input type="hidden" name="report_no" id="final-report-no-2" value="" />
                <input type="hidden" name="final_remarks" id="final-remarks-2" value="" />
                <button type="submit" class="btn btn-destructive" onclick="return SecurityFinalModal.submitNotResolved();">Not Resolved</button>
            </form>
            <form method="POST" id="final-confirm-form" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                <input type="hidden" name="action" value="confirm_resolved" />
                <input type="hidden" name="report_no" id="final-report-no-1" value="" />
                <input type="hidden" name="final_remarks" id="final-remarks-1" value="" />
                <button type="submit" class="btn btn-primary" onclick="return SecurityFinalModal.submitConfirm();">Confirm Resolved</button>
            </form>
        </div>
    </div>
</div>

<script>
const SecurityFinalModal = {
    overlay: null,
    subjectEl: null,
    contentEl: null,
    reportNo: null,
    report: null,

    init() {
        this.overlay = document.getElementById('security-final-modal-overlay');
        this.subjectEl = document.getElementById('security-final-subject');
        this.contentEl = document.getElementById('security-final-content');
        if (!this.overlay) return;

        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) this.close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) this.close();
        });
    },

    isOpen() {
        return this.overlay && this.overlay.classList.contains('active');
    },

    async open(reportNo) {
        this.reportNo = reportNo;
        this.report = null;

        document.getElementById('final-report-no-1').value = reportNo;
        document.getElementById('final-report-no-2').value = reportNo;
        document.getElementById('final-remarks-1').value = '';
        document.getElementById('final-remarks-2').value = '';

        if (this.subjectEl) this.subjectEl.textContent = 'Loading...';
        if (this.contentEl) this.contentEl.innerHTML = '<div class="text-sm text-muted-foreground">Loading report details...</div>';

        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        try {
            const res = await fetch('api/report.php?id=' + encodeURIComponent(reportNo), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Failed to load report (' + res.status + ')');
            const report = await res.json();
            if (report && report.error) throw new Error(report.error);
            this.report = report;
            if (this.subjectEl) this.subjectEl.textContent = report.subject || 'Final Checking';
            if (this.contentEl) this.contentEl.innerHTML = this.render(report);
        } catch (e) {
            if (this.subjectEl) this.subjectEl.textContent = 'Final Checking';
            if (this.contentEl) this.contentEl.innerHTML = '<div class="alert alert-danger">' + (e && e.message ? e.message : 'Unable to load report.') + '</div>';
        }
    },

    close() {
        if (this.overlay) {
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    render(report) {
        const base = (window.ReportModal && typeof ReportModal.renderReportContent === 'function')
            ? ReportModal.renderReportContent(report)
            : `
                <div class="modal-section">
                    <div class="modal-section-title">Report Information</div>
                    <div class="modal-info-grid">
                        <div class="modal-info-item"><div class="modal-info-label">Report ID</div><div class="modal-info-value font-mono">${report.id || ''}</div></div>
                        <div class="modal-info-item"><div class="modal-info-label">Department</div><div class="modal-info-value">${report.department || 'N/A'}</div></div>
                        <div class="modal-info-item"><div class="modal-info-label">Severity</div><div class="modal-info-value">${report.severity || 'N/A'}</div></div>
                        <div class="modal-info-item"><div class="modal-info-label">Status</div><div class="modal-info-value">${report.status || 'N/A'}</div></div>
                    </div>
                    <div class="modal-info-item mt-3">
                        <div class="modal-info-label">Full Details</div>
                        <div class="modal-description">${report.details || ''}</div>
                    </div>
                </div>
            `;

        return base + `
            <div class="modal-section">
                <div class="modal-section-title">Final Checking Remarks</div>
                <div class="modal-info-item">
                    <div class="modal-info-label">Remarks <span class="text-muted-foreground">*</span></div>
                    <textarea id="security-final-remarks" class="form-control form-control-sm" rows="3" placeholder="Enter remarks (required)"></textarea>
                    <p class="text-xs text-muted-foreground mt-2">Remarks are required for both actions and will be recorded in the report history.</p>
                </div>
            </div>
        `;
    },

    submitConfirm() {
        const el = document.getElementById('security-final-remarks');
        const remarks = el ? String(el.value || '').trim() : '';
        if (!remarks) { alert('Remarks are required.'); return false; }
        document.getElementById('final-remarks-1').value = remarks;
        return true;
    },

    submitNotResolved() {
        const el = document.getElementById('security-final-remarks');
        const remarks = el ? String(el.value || '').trim() : '';
        if (!remarks) { alert('Remarks are required.'); return false; }
        document.getElementById('final-remarks-2').value = remarks;
        return true;
    }
};

document.addEventListener('DOMContentLoaded', () => SecurityFinalModal.init());
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
