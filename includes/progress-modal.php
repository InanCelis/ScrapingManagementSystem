<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">
                    <i class="fas fa-chart-line me-2"></i>
                    <span id="modalProcessName">Process Name</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Status Badge -->
                <div class="mb-3">
                    <span class="badge" id="modalProcessStatus">Running</span>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Progress</label>
                    <div class="progress">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width: 0%">0%</div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">Total Items</small>
                                        <h4 class="mb-0" id="totalItems">0</h4>
                                    </div>
                                    <i class="fas fa-list fa-2x text-primary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">Items Scraped</small>
                                        <h4 class="mb-0" id="itemsScraped">0</h4>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-info opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">Items Remaining</small>
                                        <h4 class="mb-0" id="itemsRemaining">0</h4>
                                    </div>
                                    <i class="fas fa-hourglass-half fa-2x text-warning opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">Created / Updated</small>
                                        <h4 class="mb-0">
                                            <span id="itemsCreated">0</span> / <span id="itemsUpdated">0</span>
                                        </h4>
                                    </div>
                                    <i class="fas fa-sync fa-2x text-success opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Console -->
                <div>
                    <label class="form-label fw-bold">
                        <i class="fas fa-terminal me-2"></i>Live Console
                    </label>
                    <div class="console-terminal" id="consoleOutput">
                        Waiting for logs...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
