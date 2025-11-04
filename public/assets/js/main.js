// Main JavaScript for Scraping Management System

// Sidebar Toggle
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const topbar = document.querySelector('.topbar');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            topbar.classList.toggle('expanded');
        });
    }

    // Mobile sidebar toggle
    if (window.innerWidth <= 768) {
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    }

    // Auto-refresh running processes
    if (document.querySelector('.running-tools-page')) {
        setInterval(refreshRunningTools, 5000); // Refresh every 5 seconds
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// ==================== Running Tools Functions ====================

function startScraper(configId) {
    if (!confirm('Are you sure you want to start this scraper?')) {
        return;
    }

    $.ajax({
        url: '/ScrapingToolsAutoSync/api/scraper.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'start',
            config_id: configId
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to start scraper');
        }
    });
}

function stopScraper(processId) {
    if (!confirm('Are you sure you want to stop this scraper?')) {
        return;
    }

    $.ajax({
        url: '/ScrapingToolsAutoSync/api/scraper.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'stop',
            process_id: processId
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to stop scraper');
        }
    });
}

function viewProgress(processId) {
    // Load process details
    $.ajax({
        url: '/ScrapingToolsAutoSync/api/scraper.php',
        method: 'GET',
        data: {
            action: 'get_process',
            process_id: processId
        },
        success: function(response) {
            if (response.success) {
                showProgressModal(response.process);
            } else {
                showAlert('danger', 'Failed to load process details');
            }
        },
        error: function() {
            showAlert('danger', 'Failed to load process details');
        }
    });
}

function showProgressModal(process) {
    const modal = new bootstrap.Modal(document.getElementById('progressModal'));

    // Update modal content
    document.getElementById('modalProcessName').textContent = process.config_name;
    document.getElementById('modalProcessStatus').textContent = process.status;
    document.getElementById('modalProcessStatus').className = 'badge status-badge bg-' + getStatusColor(process.status);

    // Update progress bar
    const percentage = process.total_items > 0 ? Math.round((process.items_scraped / process.total_items) * 100) : 0;
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('progressBar').textContent = percentage + '%';

    // Update stats
    document.getElementById('totalItems').textContent = process.total_items;
    document.getElementById('itemsScraped').textContent = process.items_scraped;
    document.getElementById('itemsRemaining').textContent = process.total_items - process.items_scraped;
    document.getElementById('itemsCreated').textContent = process.items_created;
    document.getElementById('itemsUpdated').textContent = process.items_updated;

    // Load logs
    loadProcessLogs(process.id);

    modal.show();

    // Auto-refresh logs if running
    if (process.status === 'running') {
        const logsInterval = setInterval(function() {
            if (document.getElementById('progressModal').classList.contains('show')) {
                loadProcessLogs(process.id);
                // Refresh process stats
                refreshProcessStats(process.id);
            } else {
                clearInterval(logsInterval);
            }
        }, 2000);
    }
}

function loadProcessLogs(processId) {
    $.ajax({
        url: '/ScrapingToolsAutoSync/api/scraper.php',
        method: 'GET',
        data: {
            action: 'get_logs',
            process_id: processId
        },
        success: function(response) {
            if (response.success) {
                const consoleOutput = document.getElementById('consoleOutput');
                consoleOutput.innerHTML = '';

                response.logs.forEach(function(log) {
                    const logLine = document.createElement('div');
                    logLine.className = 'log-' + log.log_level;
                    logLine.textContent = `[${log.created_at}] [${log.log_level.toUpperCase()}] ${log.message}`;
                    consoleOutput.appendChild(logLine);
                });

                // Auto-scroll to bottom
                consoleOutput.scrollTop = consoleOutput.scrollHeight;
            }
        }
    });
}

function refreshProcessStats(processId) {
    $.ajax({
        url: '/ScrapingToolsAutoSync/api/scraper.php',
        method: 'GET',
        data: {
            action: 'get_process',
            process_id: processId
        },
        success: function(response) {
            if (response.success) {
                const process = response.process;
                const percentage = process.total_items > 0 ? Math.round((process.items_scraped / process.total_items) * 100) : 0;

                document.getElementById('progressBar').style.width = percentage + '%';
                document.getElementById('progressBar').textContent = percentage + '%';
                document.getElementById('itemsScraped').textContent = process.items_scraped;
                document.getElementById('itemsRemaining').textContent = process.total_items - process.items_scraped;
                document.getElementById('itemsCreated').textContent = process.items_created;
                document.getElementById('itemsUpdated').textContent = process.items_updated;
            }
        }
    });
}

function refreshRunningTools() {
    $.ajax({
        url: '/ScrapingToolsAutoSync/api/scraper.php',
        method: 'GET',
        data: {
            action: 'get_running'
        },
        success: function(response) {
            if (response.success) {
                // Update UI with running processes
                updateRunningToolsUI(response.processes);
            }
        }
    });
}

function updateRunningToolsUI(processes) {
    // Update the running tools table or list
    // This would be specific to the running-tools.php page implementation
}

// ==================== Configuration Functions ====================

function editConfig(id) {
    window.location.href = '/ScrapingToolsAutoSync/configuration-form.php?id=' + id;
}

function deleteConfig(id) {
    if (!confirm('Are you sure you want to delete this configuration?')) {
        return;
    }

    $.ajax({
        url: '/ScrapingToolsAutoSync/api/config.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'delete',
            id: id
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to delete configuration');
        }
    });
}

function duplicateConfig(id) {
    $.ajax({
        url: '/ScrapingToolsAutoSync/api/config.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'duplicate',
            id: id
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to duplicate configuration');
        }
    });
}

// ==================== Form Functions ====================

function toggleTypeFields() {
    const type = document.getElementById('type').value;
    const websiteFields = document.getElementById('websiteFields');
    const xmlFields = document.getElementById('xmlFields');

    if (type === 'website') {
        websiteFields.style.display = 'block';
        xmlFields.style.display = 'none';
    } else if (type === 'xml') {
        websiteFields.style.display = 'none';
        xmlFields.style.display = 'block';
    }
}

// ==================== Utility Functions ====================

function getStatusColor(status) {
    const colors = {
        'running': 'primary',
        'completed': 'success',
        'failed': 'danger',
        'stopped': 'warning',
        'pending': 'secondary'
    };
    return colors[status] || 'secondary';
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function formatDuration(seconds) {
    if (!seconds) return '0s';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    let result = '';
    if (hours > 0) result += hours + 'h ';
    if (minutes > 0) result += minutes + 'm ';
    if (secs > 0 || result === '') result += secs + 's';

    return result.trim();
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-US', options);
}
