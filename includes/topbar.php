<nav class="navbar navbar-expand-lg navbar-dark bg-primary topbar">
    <div class="container-fluid">
        <button class="btn btn-link text-white me-3" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <span class="navbar-brand mb-0 h1"><?php echo $pageTitle ?? 'Dashboard'; ?></span>

        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-link text-white dropdown-toggle text-decoration-none" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/ScrapingToolsAutoSync/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="/ScrapingToolsAutoSync/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/ScrapingToolsAutoSync/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
