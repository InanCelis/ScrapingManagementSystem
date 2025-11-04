<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ScraperManager.php';

$auth = new Auth();
$auth->requireAuth();

$scraperManager = new ScraperManager();

// Handle filters
$filters = [];
if (!empty($_GET['type'])) {
    $filters['type'] = $_GET['type'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

$configs = $scraperManager->getAllConfigs($filters);

$pageTitle = 'Scraping Configurations';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-fill">
        <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-cog me-2"></i>Scraping Configurations</h2>
                <a href="/ScrapingToolsAutoSync/configuration-form" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Configuration
                </a>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search"
                                   placeholder="Search by name or folder..."
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="">All Types</option>
                                <option value="website" <?php echo (($_GET['type'] ?? '') === 'website') ? 'selected' : ''; ?>>
                                    Website
                                </option>
                                <option value="xml" <?php echo (($_GET['type'] ?? '') === 'xml') ? 'selected' : ''; ?>>
                                    XML
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                            <a href="/ScrapingToolsAutoSync/configurations" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Configurations Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>All Configurations
                        <span class="badge bg-primary ms-2"><?php echo count($configs); ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Folder/File</th>
                                    <th>Last Run</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($configs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-inbox fa-4x mb-3 d-block"></i>
                                            <h5>No configurations found</h5>
                                            <p>Create your first scraping configuration to get started</p>
                                            <a href="/ScrapingToolsAutoSync/configuration-form" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Add Configuration
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($configs as $config): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($config['name']); ?></strong>
                                                <?php if ($config['testing_mode']): ?>
                                                    <span class="badge bg-warning ms-2">Test Mode</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo strtoupper($config['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'active' => 'success',
                                                    'inactive' => 'secondary',
                                                    'archived' => 'warning'
                                                ];
                                                $color = $statusColors[$config['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo ucfirst($config['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($config['folder_name']); ?> /
                                                    <?php echo htmlspecialchars($config['filename']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo $config['last_run_at'] ? date('M d, Y H:i', strtotime($config['last_run_at'])) : 'Never'; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($config['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/ScrapingToolsAutoSync/configuration-form?id=<?php echo $config['id']; ?>"
                                                       class="btn btn-sm btn-info" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-warning"
                                                            onclick="duplicateConfig(<?php echo $config['id']; ?>)"
                                                            title="Duplicate">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger"
                                                            onclick="deleteConfig(<?php echo $config['id']; ?>)"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
