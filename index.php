<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$project = getProject($pdo);
$evm = calculateEVM($pdo);
$progress = getOverallProgress($pdo);
$delayed = getDelayedActivities($pdo);

$pendingCRs = $pdo->query("SELECT COUNT(*) FROM change_requests WHERE status IN ('Submitted','Under Review')")->fetchColumn();
$openCriticalDefects = $pdo->query("SELECT COUNT(*) FROM defects WHERE severity='Critical' AND status IN ('Open','In Progress','Reopened')")->fetchColumn();
$totalRequirements = $pdo->query("SELECT COUNT(*) FROM requirements")->fetchColumn();
$approvedRequirements = $pdo->query("SELECT COUNT(*) FROM requirements WHERE status='Approved'")->fetchColumn();
$totalActivities = $pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
$completedActivities = $pdo->query("SELECT COUNT(*) FROM activities WHERE status='Completed'")->fetchColumn();
$openRisks = $pdo->query("SELECT COUNT(*) FROM risks WHERE status NOT IN ('Closed')")->fetchColumn();

$recentCRs = $pdo->query("SELECT * FROM change_requests ORDER BY created_at DESC LIMIT 5")->fetchAll();
$criticalDefects = $pdo->query("SELECT d.*, u.name as assignee FROM defects d LEFT JOIN users u ON d.assigned_to = u.id WHERE d.severity='Critical' AND d.status IN ('Open','In Progress','Reopened') ORDER BY d.reported_at DESC LIMIT 5")->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Project Dashboard</h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($project['name']) ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary fs-6"><?= $project['status'] ?></span>
            <div class="small text-muted mt-1">
                <?= date('d M Y', strtotime($project['start_date'])) ?> → <?= date('d M Y', strtotime($project['planned_end_date'])) ?>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Overall Progress</h6>
                            <h3 class="mb-0"><?= $progress ?>%</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-graph-up text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height:8px;">
                        <div class="progress-bar" style="width:<?= $progress ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Budget Status</h6>
                            <h3 class="mb-0"><?= formatPKR($project['current_budget']) ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-cash-stack text-success fs-4"></i>
                        </div>
                    </div>
                    <small class="text-muted">Original: <?= formatPKR($project['approved_budget']) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">CPI (Cost Performance)</h6>
                            <h3 class="mb-0 <?= $evm['cpi'] < CPI_THRESHOLD ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($evm['cpi'], 2) ?>
                            </h3>
                        </div>
                        <div class="bg-<?= $evm['cpi'] < CPI_THRESHOLD ? 'danger' : 'success' ?> bg-opacity-10 p-3 rounded">
                            <i class="bi bi-speedometer fs-4 text-<?= $evm['cpi'] < CPI_THRESHOLD ? 'danger' : 'success' ?>"></i>
                        </div>
                    </div>
                    <?php if ($evm['cpi'] < CPI_THRESHOLD): ?>
                        <small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Cost overrun warning</small>
                    <?php else: ?>
                        <small class="text-success">Within threshold (≥ 0.90)</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">SPI (Schedule Performance)</h6>
                            <h3 class="mb-0 <?= $evm['spi'] < SPI_THRESHOLD ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($evm['spi'], 2) ?>
                            </h3>
                        </div>
                        <div class="bg-<?= $evm['spi'] < SPI_THRESHOLD ? 'danger' : 'success' ?> bg-opacity-10 p-3 rounded">
                            <i class="bi bi-calendar-check fs-4 text-<?= $evm['spi'] < SPI_THRESHOLD ? 'danger' : 'success' ?>"></i>
                        </div>
                    </div>
                    <?php if ($evm['spi'] < SPI_THRESHOLD): ?>
                        <small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Schedule delay warning</small>
                    <?php else: ?>
                        <small class="text-success">Within threshold (≥ 0.90)</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h4 class="mb-0 text-primary"><?= $pendingCRs ?></h4><small class="text-muted">Pending CRs</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h4 class="mb-0 text-danger"><?= $openCriticalDefects ?></h4><small class="text-muted">Critical Defects</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h4 class="mb-0"><?= $approvedRequirements ?>/<?= $totalRequirements ?></h4><small class="text-muted">Approved Reqs</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h4 class="mb-0"><?= $completedActivities ?>/<?= $totalActivities ?></h4><small class="text-muted">Activities Done</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h4 class="mb-0 text-warning"><?= count($delayed) ?></h4><small class="text-muted">Delayed Tasks</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h4 class="mb-0"><?= $openRisks ?></h4><small class="text-muted">Open Risks</small></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-bar-chart"></i> Earned Value Management</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>Planned Value (PV)</td><td class="text-end fw-bold"><?= formatPKR($evm['pv']) ?></td></tr>
                        <tr><td>Earned Value (EV)</td><td class="text-end fw-bold"><?= formatPKR($evm['ev']) ?></td></tr>
                        <tr><td>Actual Cost (AC)</td><td class="text-end fw-bold"><?= formatPKR($evm['ac']) ?></td></tr>
                        <tr class="table-light"><td>Cost Variance (CV)</td><td class="text-end fw-bold <?= $evm['cv']<0?'text-danger':'text-success' ?>"><?= formatPKR($evm['cv']) ?></td></tr>
                        <tr class="table-light"><td>Schedule Variance (SV)</td><td class="text-end fw-bold <?= $evm['sv']<0?'text-danger':'text-success' ?>"><?= formatPKR($evm['sv']) ?></td></tr>
                        <tr><td>Estimate at Completion</td><td class="text-end"><?= formatPKR($evm['eac']) ?></td></tr>
                        <tr><td>Variance at Completion</td><td class="text-end <?= $evm['vac']<0?'text-danger':'text-success' ?>"><?= formatPKR($evm['vac']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span><i class="bi bi-exclamation-triangle text-warning"></i> Delayed Activities</span>
                    <a href="<?= BASE_URL ?>modules/schedule.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($delayed)): ?>
                        <p class="text-muted text-center py-4 mb-0">No delayed activities</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach (array_slice($delayed, 0, 5) as $act): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-medium"><?= htmlspecialchars($act['name']) ?></span>
                                        <span class="badge bg-danger">Delayed</span>
                                    </div>
                                    <small class="text-muted">Due: <?= date('d M Y', strtotime($act['planned_end'])) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span><i class="bi bi-bug text-danger"></i> Critical Open Defects</span>
                    <a href="<?= BASE_URL ?>modules/defects.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($criticalDefects)): ?>
                        <p class="text-muted text-center py-4 mb-0">No critical open defects</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($criticalDefects as $def): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-medium"><?= htmlspecialchars($def['title']) ?></span>
                                        <?= statusBadge($def['status']) ?>
                                    </div>
                                    <small class="text-muted">Assigned: <?= htmlspecialchars($def['assignee'] ?? 'Unassigned') ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-bold d-flex justify-content-between">
            <span><i class="bi bi-arrow-left-right"></i> Recent Change Requests</span>
            <a href="<?= BASE_URL ?>modules/change_requests.php" class="btn btn-sm btn-outline-primary">Manage CRs</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th><th>Title</th><th>Cost Impact</th><th>Schedule Impact</th><th>Status</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentCRs as $cr): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($cr['cr_code']) ?></code></td>
                                <td><?= htmlspecialchars($cr['title']) ?></td>
                                <td><?= formatPKR($cr['cost_impact']) ?></td>
                                <td><?= $cr['schedule_impact_days'] ?> days</td>
                                <td><?= statusBadge($cr['status']) ?></td>
                                <td><?= date('d M Y', strtotime($cr['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentCRs)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No change requests yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
