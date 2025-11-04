<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ScraperManager.php';

$auth = new Auth();
$auth->requireAuth();

$scraperManager = new ScraperManager();

$isEdit = isset($_GET['id']);
$config = null;
$error = '';
$success = '';

if ($isEdit) {
    $config = $scraperManager->getConfig($_GET['id']);
    if (!$config) {
        header('Location: /ScrapingToolsAutoSync/configurations');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'type' => $_POST['type'] ?? '',
        'website_url' => $_POST['website_url'] ?? null,
        'url_pattern' => $_POST['url_pattern'] ?? null,
        'count_of_pages' => !empty($_POST['count_of_pages']) ? (int)$_POST['count_of_pages'] : null,
        'start_page' => !empty($_POST['start_page']) ? (int)$_POST['start_page'] : 1,
        'end_page' => !empty($_POST['end_page']) ? (int)$_POST['end_page'] : null,
        'xml_link' => $_POST['xml_link'] ?? null,
        'count_of_properties' => !empty($_POST['count_of_properties']) ? (int)$_POST['count_of_properties'] : null,
        'enable_upload' => isset($_POST['enable_upload']) ? 1 : 0,
        'testing_mode' => isset($_POST['testing_mode']) ? 1 : 0,
        'folder_name' => $_POST['folder_name'] ?? null,
        'filename' => $_POST['filename'] ?? null,
        'file_path' => $_POST['file_path'] ?? null,
        'status' => $_POST['status'] ?? 'active',
        'owned_by' => $_POST['owned_by'] ?? null,
        'contact_person' => $_POST['contact_person'] ?? null,
        'phone' => $_POST['phone'] ?? null,
        'email' => $_POST['email'] ?? null,
        'listing_id_prefix' => $_POST['listing_id_prefix'] ?? null,
    ];

    if ($isEdit) {
        $result = $scraperManager->updateConfig($_GET['id'], $data);
    } else {
        $result = $scraperManager->createConfig($data);
    }

    if ($result['success']) {
        $success = $result['message'];
        if (!$isEdit) {
            header('Location: /ScrapingToolsAutoSync/configurations');
            exit;
        }
    } else {
        $error = $result['message'];
    }
}

$pageTitle = $isEdit ? 'Edit Configuration' : 'Add Configuration';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-fill">
        <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus'; ?> me-2"></i>
                    <?php echo $pageTitle; ?>
                </h2>
                <a href="/ScrapingToolsAutoSync/configurations" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Configuration Form -->
            <form method="POST" action="">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name of Tool <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?php echo htmlspecialchars($config['name'] ?? ''); ?>" required>
                                <small class="text-muted">Example: Holiday Homes Spain, Nilsott</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type of Tool <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required onchange="toggleTypeFields()">
                                    <option value="">Select Type</option>
                                    <option value="website" <?php echo (($config['type'] ?? '') === 'website') ? 'selected' : ''; ?>>
                                        Website
                                    </option>
                                    <option value="xml" <?php echo (($config['type'] ?? '') === 'xml') ? 'selected' : ''; ?>>
                                        XML
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo (($config['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>
                                        Active
                                    </option>
                                    <option value="inactive" <?php echo (($config['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>
                                        Inactive
                                    </option>
                                    <option value="archived" <?php echo (($config['status'] ?? '') === 'archived') ? 'selected' : ''; ?>>
                                        Archived
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Website Fields -->
                <div class="card mb-4" id="websiteFields" style="display: <?php echo (($config['type'] ?? '') === 'website') ? 'block' : 'none'; ?>;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Website Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="website_url" class="form-label">Website URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="website_url" name="website_url"
                                       value="<?php echo htmlspecialchars($config['website_url'] ?? ''); ?>">
                                <small class="text-muted">Example: https://holiday-homes-spain.com</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="url_pattern" class="form-label">URL Pattern <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="url_pattern" name="url_pattern"
                                       value="<?php echo htmlspecialchars($config['url_pattern'] ?? ''); ?>">
                                <small class="text-muted">Use {$page} for page variable</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="count_of_pages" class="form-label">Count of Pages</label>
                                <input type="number" class="form-control" id="count_of_pages" name="count_of_pages"
                                       value="<?php echo $config['count_of_pages'] ?? ''; ?>" min="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="start_page" class="form-label">Start Page</label>
                                <input type="number" class="form-control" id="start_page" name="start_page"
                                       value="<?php echo $config['start_page'] ?? 1; ?>" min="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_page" class="form-label">End Page</label>
                                <input type="number" class="form-control" id="end_page" name="end_page"
                                       value="<?php echo $config['end_page'] ?? ''; ?>" min="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- XML Fields -->
                <div class="card mb-4" id="xmlFields" style="display: <?php echo (($config['type'] ?? '') === 'xml') ? 'block' : 'none'; ?>;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-file-code me-2"></i>XML Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="xml_link" class="form-label">XML Link <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="xml_link" name="xml_link"
                                       value="<?php echo htmlspecialchars($config['xml_link'] ?? ''); ?>">
                                <small class="text-muted">Example: https://web3930:9a42ded9cb@www.nilsott.com/xml/kyero.xml</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="count_of_properties" class="form-label">Count of Properties</label>
                                <input type="number" class="form-control" id="count_of_properties" name="count_of_properties"
                                       value="<?php echo $config['count_of_properties'] ?? ''; ?>" min="0">
                                <small class="text-muted">0 = all properties</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Common Fields -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Common Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="folder_name" class="form-label">Folder Name</label>
                                <input type="text" class="form-control" id="folder_name" name="folder_name"
                                       value="<?php echo htmlspecialchars($config['folder_name'] ?? ''); ?>">
                                <small class="text-muted">Auto-generated from tool name if empty</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filename" class="form-label">Filename</label>
                                <input type="text" class="form-control" id="filename" name="filename"
                                       value="<?php echo htmlspecialchars($config['filename'] ?? ''); ?>">
                                <small class="text-muted">Example: Properties.json</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="file_path" class="form-label">File Path</label>
                                <input type="text" class="form-control" id="file_path" name="file_path"
                                       value="<?php echo htmlspecialchars($config['file_path'] ?? ''); ?>">
                                <small class="text-muted">Example: Executable/HolidayHomesSpain.php</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="enable_upload" name="enable_upload"
                                           <?php echo ($config['enable_upload'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_upload">Enable Upload</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="testing_mode" name="testing_mode"
                                           <?php echo ($config['testing_mode'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="testing_mode">Testing Mode</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Owner Details -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Owner Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="owned_by" class="form-label">Owned By</label>
                                <input type="text" class="form-control" id="owned_by" name="owned_by"
                                       value="<?php echo htmlspecialchars($config['owned_by'] ?? ''); ?>">
                                <small class="text-muted">Company or organization name</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person"
                                       value="<?php echo htmlspecialchars($config['contact_person'] ?? ''); ?>">
                                <small class="text-muted">Primary contact name</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($config['phone'] ?? ''); ?>">
                                <small class="text-muted">Example: +34 722 43 32 94</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($config['email'] ?? ''); ?>">
                                <small class="text-muted">Contact email address</small>
                            </div>
                        </div>

                        <!-- Listing ID Prefix (only for XML) -->
                        <div class="row" id="listingIdPrefixRow" style="display: <?php echo (($config['type'] ?? '') === 'xml') ? 'block' : 'none'; ?>;">
                            <div class="col-md-6 mb-3">
                                <label for="listing_id_prefix" class="form-label">Listing ID Prefix</label>
                                <input type="text" class="form-control" id="listing_id_prefix" name="listing_id_prefix"
                                       value="<?php echo htmlspecialchars($config['listing_id_prefix'] ?? ''); ?>">
                                <small class="text-muted">Example: ACF-, BH-, NOG-</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-between">
                    <a href="/ScrapingToolsAutoSync/configurations" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo $isEdit ? 'Update' : 'Create'; ?> Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle type-specific fields
    function toggleTypeFields() {
        const type = document.getElementById('type').value;
        const websiteFields = document.getElementById('websiteFields');
        const xmlFields = document.getElementById('xmlFields');
        const listingIdPrefixRow = document.getElementById('listingIdPrefixRow');

        if (websiteFields) {
            websiteFields.style.display = type === 'website' ? 'block' : 'none';
        }
        if (xmlFields) {
            xmlFields.style.display = type === 'xml' ? 'block' : 'none';
        }
        if (listingIdPrefixRow) {
            listingIdPrefixRow.style.display = type === 'xml' ? 'block' : 'none';
        }
    }

    // Add event listener to type select
    const typeSelect = document.getElementById('type');
    if (typeSelect) {
        typeSelect.addEventListener('change', toggleTypeFields);
    }

    // Initialize on page load
    toggleTypeFields();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
