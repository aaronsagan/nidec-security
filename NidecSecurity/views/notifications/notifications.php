<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1">Notifications</h1>
                <p class="text-sm text-muted-foreground mb-0">Review and manage your notifications</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="text-xs text-muted-foreground" for="notifications-filter">Filter</label>
                <select id="notifications-filter" class="form-select form-select-sm" style="min-width: 160px;">
                    <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Unread</option>
                    <option value="today" <?php echo $filter === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
                <button id="notifications-page-mark-all" type="button" class="btn btn-outline-secondary btn-sm">Mark all read</button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <div class="fw-semibold text-foreground">Your Notifications</div>
                    <div class="small text-muted-foreground">Click an item to mark it read and view the report (if available).</div>
                </div>
                <div id="notifications-page-unread" class="small text-muted-foreground">Unread: —</div>
            </div>

            <div id="notifications-page-list" class="list-group list-group-flush"></div>
        </div>
    </div>
</main>

<script>
(() => {
    const filterEl = document.getElementById('notifications-filter');
    const listEl = document.getElementById('notifications-page-list');
    const unreadEl = document.getElementById('notifications-page-unread');
    const markAllBtn = document.getElementById('notifications-page-mark-all');

    if (!filterEl || !listEl) return;

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function timeAgo(dateString) {
        if (!dateString) return '';
        const d = new Date(String(dateString).replace(' ', 'T'));
        const now = new Date();
        const diffMs = now.getTime() - d.getTime();
        if (!isFinite(diffMs)) return '';

        const sec = Math.floor(diffMs / 1000);
        if (sec < 60) return sec + ' sec ago';
        const min = Math.floor(sec / 60);
        if (min < 60) return min + ' min ago';
        const hr = Math.floor(min / 60);
        if (hr < 24) return hr + ' hour' + (hr === 1 ? '' : 's') + ' ago';
        const day = Math.floor(hr / 24);
        return day + ' day' + (day === 1 ? '' : 's') + ' ago';
    }

    function render(items) {
        const arr = Array.isArray(items) ? items : [];
        if (arr.length === 0) {
            listEl.innerHTML = '<div class="p-4 text-center text-muted-foreground text-sm">No notifications</div>';
            return;
        }

        listEl.innerHTML = arr.map((n) => {
            const unread = Number(n.is_read || 0) === 0;
            const reportNo = n.report_no ? String(n.report_no) : '';
            const msg = n.message ? String(n.message) : '';
            const when = timeAgo(n.created_at);
            const safeId = Number(n.id || 0);

            const dot = unread
                ? '<span class="d-inline-block rounded-circle flex-shrink-0" style="width:10px;height:10px;background:var(--primary);"></span>'
                : '<span class="d-inline-block rounded-circle flex-shrink-0" style="width:10px;height:10px;background:transparent;border:1px solid var(--border);"></span>';

            const titleClass = unread ? 'fw-semibold text-foreground' : 'text-foreground';
            const sub = reportNo ? ('<div class="small text-muted-foreground">' + escapeHtml(reportNo) + '</div>') : '';
            const time = when ? ('<div class="small text-muted-foreground">' + escapeHtml(when) + '</div>') : '';

            if (reportNo) {
                const href = appUrl('print_report_by_no.php?id=' + encodeURIComponent(reportNo));
                return (
                    '<a class="list-group-item list-group-item-action d-flex gap-3 align-items-start notification-item"'
                    + ' href="' + escapeHtml(href) + '"'
                    + ' data-id="' + safeId + '"'
                    + ' data-report="' + escapeHtml(reportNo) + '"'
                    + ' aria-label="Open report ' + escapeHtml(reportNo) + '">'
                    + dot
                    + '<div class="flex-grow-1">'
                    + '<div class="' + titleClass + '">' + escapeHtml(msg) + '</div>'
                    + sub
                    + '</div>'
                    + '<div class="text-end flex-shrink-0" style="min-width:120px;">'
                    + time
                    + '</div>'
                    + '</a>'
                );
            }

            return (
                '<button type="button" class="list-group-item list-group-item-action d-flex gap-3 align-items-start notification-item"'
                + ' data-id="' + safeId + '"'
                + ' data-report=""'
                + ' aria-label="Mark notification read">'
                + dot
                + '<div class="flex-grow-1">'
                + '<div class="' + titleClass + '">' + escapeHtml(msg) + '</div>'
                + '</div>'
                + '<div class="text-end flex-shrink-0" style="min-width:120px;">'
                + time
                + '</div>'
                + '</button>'
            );

        }).join('');

        listEl.querySelectorAll('.notification-item').forEach((el) => {
            el.addEventListener('click', async (e) => {
                const id = Number(el.getAttribute('data-id') || 0);
                const reportNo = el.getAttribute('data-report') || '';
                if (id > 0) {
                    if (reportNo) e.preventDefault();
                    await fetch(appUrl('api/notifications.php'), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken()
                        },
                        body: JSON.stringify({ action: 'mark_read', id }),
                        keepalive: true
                    });
                }

                if (reportNo) {
                    window.location.href = appUrl('print_report_by_no.php?id=' + encodeURIComponent(String(reportNo)));
                    return;
                }

                await load();
            });
        });
    }

    async function load() {
        try {
            const filter = String(filterEl.value || 'all');
            const res = await fetch(appUrl('api/notifications.php?limit=100&filter=' + encodeURIComponent(filter)), {
                method: 'GET',
                credentials: 'same-origin'
            });
            if (!res.ok) return;
            const data = await res.json();
            if (unreadEl) unreadEl.textContent = 'Unread: ' + String(data.unread_count ?? 0);
            render(data.items || []);
        } catch (e) {
            // ignore
        }
    }

    filterEl.addEventListener('change', () => {
        const val = String(filterEl.value || 'unread');
        const url = new URL(window.location.href);
        url.searchParams.set('filter', val);
        window.location.href = url.toString();
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await fetch(appUrl('api/notifications.php'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken()
                    },
                    body: JSON.stringify({ action: 'mark_all_read' })
                });
            } catch (e2) {
                // ignore
            }
            await load();
            if (typeof Notifications !== 'undefined' && Notifications && typeof Notifications.refresh === 'function') {
                Notifications.refresh();
            }
        });
    }

    load();
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
