<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="sidebar bg-dark text-white" id="sidebar">
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <h4 class="mb-0 text-center">
            <img src="https://internationalpropertyalerts.com/wp-content/uploads/2025/07/site-logo-white-wide.png" style="width: 200px;">
            <span class="sidebar-text">Scraper Manager</span>
        </h4>
    </div>

    <ul class="nav flex-column p-2">
        <li class="nav-item">
            <a href="/ScrapingToolsAutoSync/dashboard" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line me-2"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/ScrapingToolsAutoSync/running-tools" class="nav-link <?php echo $currentPage === 'running-tools' ? 'active' : ''; ?>">
                <i class="fas fa-play-circle me-2"></i>
                <span class="sidebar-text">Running Tools</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/ScrapingToolsAutoSync/configurations" class="nav-link <?php echo $currentPage === 'configurations' ? 'active' : ''; ?>">
                <i class="fas fa-cog me-2"></i>
                <span class="sidebar-text">Configurations</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/ScrapingToolsAutoSync/activity-log" class="nav-link <?php echo $currentPage === 'activity-log' ? 'active' : ''; ?>">
                <i class="fas fa-history me-2"></i>
                <span class="sidebar-text">Activity Log</span>
            </a>
        </li>

        <li class="nav-item">
            <hr class="sidebar-divider my-2 bg-secondary">
        </li>

        <li class="nav-item">
            <a href="/ScrapingToolsAutoSync/profile" class="nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user me-2"></i>
                <span class="sidebar-text">My Profile</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/ScrapingToolsAutoSync/settings" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h me-2"></i>
                <span class="sidebar-text">Settings</span>
            </a>
        </li>

        <li class="nav-item mt-auto">
            <a href="/ScrapingToolsAutoSync/logout" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt me-2"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </li>
    </ul>
</div>
