<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-6">
            <h1 class="h4 fw-bold text-foreground mb-1">Submit Security Report</h1>
            <p class="text-sm text-muted-foreground mb-0">Create a new security incident report</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <?php if ($successReportNo): ?>
        <div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
            <div class="text-center">
                <div class="rounded-circle bg-primary-10 mx-auto d-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px;">
                    <i class="bi bi-send text-primary" aria-hidden="true" style="font-size: 28px;"></i>
                </div>
                <h2 class="h5 fw-bold text-foreground mb-2">Report Submitted</h2>
                <p class="text-sm text-muted-foreground mb-0">
                    Your report has been sent to General Affairs Staff for review.
                </p>
                <p class="text-xs text-muted-foreground mt-2 mb-0">Report ID: <span class="font-mono"><?php echo htmlspecialchars($successReportNo); ?></span></p>
            </div>
        </div>
        <?php else: ?>

        <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
        <form id="submit-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />

            <div class="section-card section-accent-info mb-4">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
                    <div>
                        <h2 class="h6 fw-bold text-foreground mb-1">Incident Details</h2>
                        <p class="text-sm text-muted-foreground mb-0">Fill in the key incident information to route the report correctly.</p>
                    </div>
                    <div class="badge badge--info">Security Report</div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="subject" class="form-label">Subject *</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            required
                            placeholder="Brief description of the incident"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="category" class="form-label">Category *</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <?php foreach ($reportCategories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo (($_POST['category'] ?? '') === $category) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="location" class="form-label">Location *</label>
                        <input
                            type="text"
                            id="location"
                            name="location"
                            required
                            placeholder="e.g. Building A - 2nd Floor"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="severity" class="form-label">Severity Level *</label>
                        <select id="severity" name="severity" class="form-select" required>
                            <?php foreach ($severityLevels as $level): ?>
                            <option value="<?php echo $level; ?>" <?php echo (($_POST['severity'] ?? 'medium') === $level) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($level); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="department" class="form-label">Department *</label>
                        <select id="department" name="department_id" class="form-select" required>
                            <option value="">Select department</option>
                            <?php foreach (($departmentsDb ?? []) as $dept): ?>
                            <option value="<?php echo (int)$dept['id']; ?>" <?php echo ((int)($_POST['department_id'] ?? 0) === (int)$dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="section-card section-accent-primary mb-4">
                <h2 class="h6 fw-bold text-foreground mb-1">Narrative</h2>
                <p class="text-sm text-muted-foreground mb-4">Describe what happened and what has already been done.</p>

                <div class="d-grid gap-3">
                    <div>
                        <label for="details" class="form-label">Full Details *</label>
                        <textarea
                            id="details"
                            name="details"
                            required
                            rows="4"
                            class="form-control"
                            placeholder="Provide a detailed description of the incident..."
                        ><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="actions-taken" class="form-label">Actions Taken</label>
                        <textarea
                            id="actions-taken"
                            name="actions_taken"
                            rows="3"
                            class="form-control"
                            placeholder="Describe actions already taken..."
                        ><?php echo htmlspecialchars($_POST['actions_taken'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="2"
                            class="form-control"
                            placeholder="Any additional remarks..."
                        ><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="section-card section-accent-success">
                <h2 class="h6 fw-bold text-foreground mb-1">Attachments</h2>
                <p class="text-sm text-muted-foreground mb-4">Optional evidence images (PNG/JPG, up to 10MB each).</p>

                <div>
                    <label class="form-label">Image Attachment</label>
                    <label id="evidence-dropzone" for="evidence" class="rounded p-4 text-center d-block" style="border: 2px dashed hsl(var(--border)); cursor: pointer;">
                        <i class="bi bi-upload text-muted-foreground mx-auto mb-2" aria-hidden="true" style="font-size: 32px; display: block;"></i>
                        <p class="text-sm text-muted-foreground">Click to upload or drag and drop</p>
                        <p class="text-xs text-muted-foreground mt-1">PNG, JPG up to 10MB</p>
                        <input id="evidence" name="evidence[]" type="file" accept="image/png,image/jpeg" multiple class="hidden" />
                    </label>
                    <div id="evidence-selected" class="text-xs text-muted-foreground mt-2">No file selected.</div>
                </div>

                <div class="d-flex justify-content-end pt-3">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        Submit Report
                    </button>
                </div>
            </div>
        </form>
        </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('evidence');
    const zone = document.getElementById('evidence-dropzone');
    const selected = document.getElementById('evidence-selected');
    if (!input || !zone || !selected) return;

    const updateSelected = () => {
        const files = input.files ? Array.from(input.files) : [];
        if (!files.length) {
            selected.textContent = 'No file selected.';
            return;
        }
        if (files.length === 1) {
            selected.textContent = 'Selected: ' + (files[0].name || '1 file');
            return;
        }
        selected.textContent = 'Selected: ' + files.length + ' files';
    };

    input.addEventListener('change', updateSelected);
    updateSelected();

    const prevent = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((evt) => {
        zone.addEventListener(evt, prevent);
    });

    zone.addEventListener('drop', (e) => {
        const dtFiles = e.dataTransfer && e.dataTransfer.files ? Array.from(e.dataTransfer.files) : [];
        if (!dtFiles.length) return;

        // Only accept images (browser may still provide other types on drop)
        const imgs = dtFiles.filter(f => (f.type === 'image/png' || f.type === 'image/jpeg'));
        if (!imgs.length) return;

        try {
            const dt = new DataTransfer();
            imgs.forEach(f => dt.items.add(f));
            input.files = dt.files;
            updateSelected();
        } catch (err) {
            // If DataTransfer is unavailable, fall back to normal click-to-upload.
        }
    });
});
</script>
