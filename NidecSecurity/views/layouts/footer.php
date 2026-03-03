<?php
// Layout footer
?>
<!-- Report Details Modal -->
<div id="report-modal-overlay" class="modal-overlay">
    <div id="report-modal" class="report-modal">
        <div class="report-modal-header">
            <h3 id="modal-report-subject">Report Details</h3>
            <button class="modal-close-btn" type="button" aria-label="Close" onclick="ReportModal.close()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="report-modal-body" id="modal-report-content">
            <!-- Content populated by JavaScript -->
        </div>
        <div class="report-modal-footer">
            <button id="modal-download-pdf" class="btn btn-primary" type="button" disabled>Download PDF</button>
            <button class="btn btn-outline-secondary" type="button" onclick="ReportModal.close()">Close</button>
        </div>
    </div>
</div>
<script src="<?php echo htmlspecialchars(app_url('assets/js/app.js')); ?>?v=<?php echo time(); ?>"></script>
</body>
</html>
