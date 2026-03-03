<?php
function dept_timeline_status_label(?string $actionType, ?string $timelineDue): string {
    if ($actionType === 'done') return 'DONE';
    if ($actionType === 'timeline') {
        if (!$timelineDue) return 'TIMELINE';
        $due = strtotime($timelineDue);
        if ($due !== false && $due <= time()) return 'DUE';
        return 'ON TRACK';
    }
    return 'NOT SET';
}
?>

<main class="main-content">
  <div class="animate-fade-in">
    <div class="mt-3 d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div>
        <h1 class="h4 fw-bold text-foreground mb-1">Department Dashboard</h1>
        <p class="text-sm text-muted-foreground mb-0">Assigned Department: <?php echo htmlspecialchars($currentUser['department_name'] ?? ''); ?></p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-xs text-muted-foreground">Building</span>
        <select id="building-filter" class="form-select form-select-sm" style="min-width: 160px;">
          <option value="all" <?php echo $selectedBuilding === 'all' ? 'selected' : ''; ?>>All</option>
          <option value="NCFL" <?php echo $selectedBuilding === 'NCFL' ? 'selected' : ''; ?>>NCFL</option>
          <option value="NPFL" <?php echo $selectedBuilding === 'NPFL' ? 'selected' : ''; ?>>NPFL</option>
        </select>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card stat-card--accent stat-accent-warning h-100">
        <div class="stat-card-icon">
          <i class="bi bi-clock-history" aria-hidden="true" style="font-size: 18px;"></i>
        </div>
        <p class="fs-2 fw-bold text-foreground"><?php echo (int)$stats['pending_assigned']; ?></p>
        <p class="text-xs text-muted-foreground mt-1">Pending Reports Assigned</p>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card stat-card--accent stat-accent-info h-100">
        <div class="stat-card-icon">
          <i class="bi bi-plus" aria-hidden="true" style="font-size: 18px;"></i>
        </div>
        <p class="fs-2 fw-bold text-foreground"><?php echo (int)$stats['under_timeline']; ?></p>
        <p class="text-xs text-muted-foreground mt-1">Reports Under Fix Timeframe</p>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card stat-card--accent stat-accent-success h-100">
        <div class="stat-card-icon">
          <i class="bi bi-check-lg" aria-hidden="true" style="font-size: 18px;"></i>
        </div>
        <p class="fs-2 fw-bold text-foreground"><?php echo (int)$stats['marked_done']; ?></p>
        <p class="text-xs text-muted-foreground mt-1">Reports Marked as Done</p>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card stat-card--accent stat-accent-info h-100">
        <div class="stat-card-icon">
          <i class="bi bi-shield-check" aria-hidden="true" style="font-size: 18px;"></i>
        </div>
        <p class="fs-2 fw-bold text-foreground"><?php echo (int)$stats['waiting_final_check']; ?></p>
        <p class="text-xs text-muted-foreground mt-1">Waiting for Security Final Check</p>
        </div>
      </div>
    </div>

    <div class="table-container table-card" style="--table-accent: var(--warning);">
      <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
          <h3 class="font-semibold text-foreground">Recent Assigned Reports</h3>
          <p class="text-xs text-muted-foreground">Latest 10 reports assigned to your department</p>
        </div>
        <div class="text-xs text-muted-foreground">Total: <?php echo (int)count($recent); ?></div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Report ID</th>
            <th>Subject</th>
            <th>Severity</th>
            <th>Date Assigned</th>
            <th>Timeline Status</th>
            <th>Current Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent)): ?>
            <tr><td colspan="6" class="text-center text-muted-foreground">No assigned reports found.</td></tr>
          <?php else: ?>
            <?php foreach ($recent as $r): ?>
              <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                <td class="font-medium text-truncate" style="max-width: 260px;"><?php echo htmlspecialchars($r['subject']); ?></td>
                <td class="text-muted-foreground"><?php echo htmlspecialchars(severity_label($r['severity'])); ?></td>
                <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(date('M d, Y', strtotime($r['assigned_at']))); ?></td>
                <td class="text-muted-foreground"><?php echo htmlspecialchars(dept_timeline_status_label($r['action_type'] ?? null, ($r['timeline_due'] ?? $r['fix_due_date']) ?? null)); ?></td>
                <td class="text-muted-foreground"><?php echo htmlspecialchars(report_status_label($r['status'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
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
