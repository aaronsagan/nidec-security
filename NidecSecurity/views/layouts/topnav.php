<?php
// Top Navigation component
// Expects: $user from config.php
?>
<header class="topnav navbar navbar-expand bg-body border-bottom">
    <div class="container-fluid gap-2">
        <div class="d-flex align-items-center gap-2">
            <!-- Mobile sidebar toggle (Bootstrap offcanvas) -->
            <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Toggle sidebar">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <span class="navbar-text text-muted">
                Security Reporting System
            </span>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <!-- Notifications (keeps existing JS hooks/IDs) -->
            <div class="position-relative">
                <button id="notifications-bell" type="button" onclick="UI.toggleNotifications()" class="btn btn-outline-secondary position-relative">
                    <i class="bi bi-bell-fill" aria-hidden="true"></i> 
                    <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
                </button>

                <div id="notifications-dropdown" class="notifications-dropdown dropdown-menu dropdown-menu-end p-0 hidden" style="min-width: 320px;">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <h6 class="m-0">Notifications</h6>
                        <button id="notifications-mark-all" type="button" class="btn btn-link btn-sm text-decoration-none">Mark all read</button>
                    </div>
                    <div class="px-3 py-2 border-bottom">
                        <a href="<?php echo htmlspecialchars(app_url('notifications.php')); ?>" class="small text-muted text-decoration-none">View all notifications</a>
                    </div>
                    <div id="notifications-list" class="notifications-list"></div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-person text-primary-foreground" aria-hidden="true"></i>
                </div>
                <div class="d-none d-md-block">
                    <div id="topnav-username" class="fw-semibold lh-1"><?php echo htmlspecialchars($user['username'] ?? 'Admin'); ?></div>
                    <div id="topnav-role" class="small text-muted"><?php echo htmlspecialchars($user['displayName'] ?? 'GA President'); ?></div>
                </div>
            </div>

            <!-- Logout -->
            <button type="button" onclick="Auth.logout()" class="btn btn-logout-custom" title="Sign out">
                <i class="bi bi-box-arrow-right" aria-hidden="true" style="-webkit-text-stroke: 1px;"></i>
            </button>
        </div>
    </div>
</header>

