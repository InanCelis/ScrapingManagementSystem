<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ScraperManager.php';

$auth = new Auth();
$auth->requireAuth();

$scraperManager = new ScraperManager();
$stats = $scraperManager->getDashboardStats();
$recentActivity = $scraperManager->getRecentActivity(10);
$weeklyActivity = $scraperManager->getWeeklyActivity();
$statusDistribution = $scraperManager->getStatusDistribution();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-fill">
        <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card border-left-primary">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="stat-label">Total Configurations</div>
                                    <h2 class="stat-value text-primary"><?php echo $stats['total_configs']; ?></h2>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cog stat-icon text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card border-left-success">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="stat-label">Running Tools</div>
                                    <h2 class="stat-value text-success"><?php echo $stats['running_processes']; ?></h2>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-play-circle stat-icon text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card border-left-info">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="stat-label">Properties Today</div>
                                    <h2 class="stat-value text-info"><?php echo number_format($stats['properties_today']); ?></h2>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-home stat-icon text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card border-left-warning">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="stat-label">Success Rate</div>
                                    <h2 class="stat-value text-warning"><?php echo $stats['success_rate']; ?>%</h2>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line stat-icon text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Scraping Activity (Last 7 Days)</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Process Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                            <a href="/ScrapingToolsAutoSync/activity-log.php" class="btn btn-sm btn-primary">
                                View All
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Configuration</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Items Scraped</th>
                                            <th>Started At</th>
                                            <th>Duration</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentActivity)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    No recent activity
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentActivity as $activity): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($activity['config_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo strtoupper($activity['type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusColors = [
                                                            'running' => 'primary',
                                                            'completed' => 'success',
                                                            'failed' => 'danger',
                                                            'stopped' => 'warning',
                                                            'pending' => 'secondary'
                                                        ];
                                                        $color = $statusColors[$activity['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge status-badge bg-<?php echo $color; ?>">
                                                            <?php echo ucfirst($activity['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo $activity['items_scraped']; ?> / <?php echo $activity['total_items']; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $activity['started_at'] ? date('M d, Y H:i', strtotime($activity['started_at'])) : 'N/A'; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($activity['duration']) {
                                                            $hours = floor($activity['duration'] / 3600);
                                                            $minutes = floor(($activity['duration'] % 3600) / 60);
                                                            $seconds = $activity['duration'] % 60;
                                                            echo ($hours > 0 ? $hours . 'h ' : '') .
                                                                 ($minutes > 0 ? $minutes . 'm ' : '') .
                                                                 $seconds . 's';
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewProgress(<?php echo $activity['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<?php require_once __DIR__ . '/../includes/progress-modal.php'; ?>

<script>
// Activity Chart - Using real data from database
const activityCtx = document.getElementById('activityChart').getContext('2d');
const activityChart = new Chart(activityCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($weeklyActivity)); ?>,
        datasets: [{
            label: 'Properties Scraped',
            data: <?php echo json_encode(array_values($weeklyActivity)); ?>,
            backgroundColor: 'rgba(78, 115, 223, 0.5)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Chart - Using real data from database
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Running', 'Failed', 'Stopped'],
        datasets: [{
            data: <?php echo json_encode(array_values($statusDistribution)); ?>,
            backgroundColor: [
                'rgba(28, 200, 138, 0.8)',
                'rgba(78, 115, 223, 0.8)',
                'rgba(231, 74, 59, 0.8)',
                'rgba(246, 194, 62, 0.8)'
            ],
            borderColor: [
                'rgba(28, 200, 138, 1)',
                'rgba(78, 115, 223, 1)',
                'rgba(231, 74, 59, 1)',
                'rgba(246, 194, 62, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
