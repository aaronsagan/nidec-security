<style>
/* 1. Base Card - COMPLETELY TRANSPARENT, NO WHITE BOX */
.main-content .stat-card.custom-stat-card {
    background: transparent !important; /* Force transparency */
    border: none !important;            /* Force remove all borders */
    box-shadow: none !important;        /* Remove any shadow */
    padding: 1rem 0.5rem !important;
    display: flex;
    flex-direction: column;
    min-height: auto !important;
}

/* 2. The Icon Container - Pill Shape with Bottom/Side Accent Only */
.stat-card-icon-wrapper {
    width: 100%;
    max-width: 200px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    padding: 0 8px;
    margin-bottom: 1.25rem;
    background: transparent !important;
    /* This creates the thin "line" look from your images */
    border: 1.5px solid transparent !important; 
}

/* 3. Layout: Title (Left) and Value (Right) */
.stat-card-middle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    margin-bottom: 2px;
}

.stat-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #334155 !important;
    margin: 0 !important;
}

.stat-card-value {
    font-size: 2.5rem; /* Large and bold as seen in photos */
    font-weight: 800;
    line-height: 1;
    margin: 0 !important;
}

/* 4. Description */
.stat-card-description {
    font-size: 0.85rem;
    color: #64748b !important;
    margin: 0 !important;
}

/* 5. Precise Colors & Icon Borders (Matched to image_b903e1.png) */
.stat-accent-info .stat-card-icon-wrapper { border-color: #3b82f6 !important; }
.stat-accent-info i, .stat-accent-info .stat-card-value { color: #3b82f6 !important; }

.stat-accent-warning .stat-card-icon-wrapper { border-color: #f59e0b !important; }
.stat-accent-warning i, .stat-accent-warning .stat-card-value { color: #f59e0b !important; }

.stat-accent-destructive .stat-card-icon-wrapper { border-color: #f43f5e !important; }
.stat-accent-destructive i, .stat-accent-destructive .stat-card-value { color: #f43f5e !important; }

.stat-accent-success .stat-card-icon-wrapper { border-color: #10b981 !important; }
.stat-accent-success i, .stat-accent-success .stat-card-value { color: #10b981 !important; }
</style>


<main class="main-content">
    <div class="animate-fade-in">
        <div class="mt-3">
            <h1 class="h4 fw-bold text-foreground mb-1">Security Dashboard</h1>
            <p class="text-sm text-muted-foreground mb-0">Welcome back, <?php echo htmlspecialchars($user['displayName'] ?? $user['username']); ?>.</p>
        </div>




    
        <div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card custom-stat-card stat-accent-info">
            <div class="stat-card-icon-wrapper">
                <i class="bi bi-graph-up"></i>
            </div>
            <div class="stat-card-middle-row">
                <p class="stat-card-title">Today's Reports</p>
                <p class="stat-card-value"><?php echo (int)$stats['submitted_today']; ?></p>
            </div>
            <p class="stat-card-description">Reports submitted in the last 24h</p>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card custom-stat-card stat-accent-warning">
            <div class="stat-card-icon-wrapper">
                <i class="bi bi-clock"></i>
            </div>
            <div class="stat-card-middle-row">
                <p class="stat-card-title">Pending GA</p>
                <p class="stat-card-value"><?php echo (int)$stats['waiting_ga_review']; ?></p>
            </div>
            <p class="stat-card-description">Awaiting Staff verification</p>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card custom-stat-card stat-accent-destructive">
            <div class="stat-card-icon-wrapper">
                <i class="bi bi-shield"></i>
            </div>
            <div class="stat-card-middle-row">
                <p class="stat-card-title">Final Check</p>
                <p class="stat-card-value"><?php echo (int)$stats['waiting_final_check']; ?></p>
            </div>
            <p class="stat-card-description">Requires security clearance</p>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card custom-stat-card stat-accent-success">
            <div class="stat-card-icon-wrapper">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="stat-card-middle-row">
                <p class="stat-card-title">Resolved</p>
                <p class="stat-card-value"><?php echo (int)$stats['resolved']; ?></p>
            </div>
            <p class="stat-card-description">Successfully closed reports</p>
        </div>
    </div>
</div>





        <div class="table-container table-card" style="--table-accent: var(--info)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Recent Reports</h3>
                    <p class="text-xs text-muted-foreground">Latest 10 reports you submitted</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int)count($recent); ?></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Severity</th>
                        <th>Current Status</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="6" class="text-center text-muted-foreground">No reports found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent as $r): ?>
                            <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                                <td class="font-medium text-truncate" style="max-width: 260px;"><?php echo htmlspecialchars($r['subject']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['department_name']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(severity_label($r['severity'])); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(report_status_label($r['status'])); ?></td>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
