<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ScraperManager.php';

$auth = new Auth();
$auth->requireAuth();

$scraperManager = new ScraperManager();
$allConfigs = $scraperManager->getAllConfigs(['status' => 'active']);
$runningProcesses = $scraperManager->getRunningProcesses();

// Create a map of running processes by config_id
$runningMap = [];
foreach ($runningProcesses as $process) {
    $runningMap[$process['config_id']] = $process;
}

$pageTitle = 'Running Tools';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-fill running-tools-page">
        <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-play-circle me-2"></i>Running Tools</h2>
                <div>
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Running Processes Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card border-left-success">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="stat-label">Currently Running</div>
                                    <h2 class="stat-value text-success"><?php echo count($runningProcesses); ?></h2>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-running stat-icon text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-left-info">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="stat-label">Total Configurations</div>
                                    <h2 class="stat-value text-info"><?php echo count($allConfigs); ?></h2>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cog stat-icon text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-left-warning">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="stat-label">Idle Tools</div>
                                    <h2 class="stat-value text-warning"><?php echo count($allConfigs) - count($runningProcesses); ?></h2>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-pause-circle stat-icon text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tools List -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Scraping Tools</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tool Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Last Run</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allConfigs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No configurations found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allConfigs as $config): ?>
                                        <?php
                                        $isRunning = isset($runningMap[$config['id']]);
                                        $process = $runningMap[$config['id']] ?? null;
                                        $progress = 0;
                                        if ($process && $process['total_items'] > 0) {
                                            $progress = round(($process['items_scraped'] / $process['total_items']) * 100);
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($config['name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($config['folder_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo strtoupper($config['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isRunning): ?>
                                                    <span class="badge status-badge bg-success">
                                                        <i class="fas fa-circle fa-beat"></i> Running
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge status-badge bg-secondary">
                                                        <i class="fas fa-stop-circle"></i> Stopped
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $config['last_run_at'] ? date('M d, H:i', strtotime($config['last_run_at'])) : 'Never'; ?>
                                            </td>
                                            <td>
                                                <?php if ($isRunning): ?>
                                                    <div class="progress" style="height: 25px; width: 150px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                                             role="progressbar" style="width: <?php echo $progress; ?>%">
                                                            <?php echo $progress; ?>%
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($isRunning): ?>
                                                        <button class="btn btn-sm btn-danger"
                                                                onclick="stopScraper(<?php echo $process['id']; ?>)"
                                                                title="Stop">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-info"
                                                                onclick="viewProgress(<?php echo $process['id']; ?>)"
                                                                title="View Progress">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success"
                                                                onclick="startScraper(<?php echo $config['id']; ?>)"
                                                                title="Start">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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

<!-- Progress Modal -->
<?php require_once __DIR__ . '/../includes/progress-modal.php'; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
