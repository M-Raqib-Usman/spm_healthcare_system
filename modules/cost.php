<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$project = getProject($pdo);
$evm = calculateEVM($pdo);

$activities = $pdo->query("SELECT activity_code, name, planned_cost, actual_cost, percent_complete, status FROM activities ORDER BY id")->fetchAll();
$totalPlanned = array_sum(array_column($activities, 'planned_cost'));
$totalActual = array_sum(array_column($activities, 'actual_cost'));

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="bi bi-currency-dollar"></i> Cost Management & Earned Value Analysis</h2>

    <!-- Warning banners -->
    <?php if ($evm['cpi'] < CPI_THRESHOLD): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-octagon"></i> <strong>Cost Overrun Warning!</strong> 
        CPI = <?= number_format($evm['cpi'], 3) ?> is below the threshold of <?= CPI_THRESHOLD ?>. 
        Project is spending more than planned for the work completed.
    </div>
    <?php endif; ?>
    <?php if ($evm['spi'] < SPI_THRESHOLD): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <strong>Schedule Deviation Warning!</strong> 
        SPI = <?= number_format($evm['spi'], 3) ?> is below the threshold of <?= SPI_THRESHOLD ?>. 
        Project is behind schedule.
    </div>
    <?php endif; ?>

    <!-- EVM Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h5 class="text-muted mb-1">BAC</h5><h4><?= formatPKR($evm['bac']) ?></h4><small>Budget at Completion</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h5 class="text-muted mb-1">PV</h5><h4><?= formatPKR($evm['pv']) ?></h4><small>Planned Value</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h5 class="text-muted mb-1">EV</h5><h4 class="text-primary"><?= formatPKR($evm['ev']) ?></h4><small>Earned Value</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h5 class="text-muted mb-1">AC</h5><h4 class="text-danger"><?= formatPKR($evm['ac']) ?></h4><small>Actual Cost</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h5 class="text-muted mb-1">CPI</h5><h4 class="<?= $evm['cpi']<CPI_THRESHOLD?'text-danger':'text-success' ?>"><?= number_format($evm['cpi'], 3) ?></h4><small>Cost Performance</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center py-3"><h5 class="text-muted mb-1">SPI</h5><h4 class="<?= $evm['spi']<SPI_THRESHOLD?'text-danger':'text-success' ?>"><?= number_format($evm['spi'], 3) ?></h4><small>Schedule Performance</small></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Variances & Forecasts</div>
                <div class="card-body">
                    <table class="table mb-0">
                        <tr><td>Cost Variance (CV = EV − AC)</td><td class="text-end fw-bold <?= $evm['cv']<0?'text-danger':'text-success' ?>"><?= formatPKR($evm['cv']) ?></td></tr>
                        <tr><td>Schedule Variance (SV = EV − PV)</td><td class="text-end fw-bold <?= $evm['sv']<0?'text-danger':'text-success' ?>"><?= formatPKR($evm['sv']) ?></td></tr>
                        <tr class="table-light"><td>Estimate at Completion (EAC)</td><td class="text-end fw-bold"><?= formatPKR($evm['eac']) ?></td></tr>
                        <tr><td>Estimate to Complete (ETC)</td><td class="text-end"><?= formatPKR($evm['etc']) ?></td></tr>
                        <tr><td>Variance at Completion (VAC)</td><td class="text-end fw-bold <?= $evm['vac']<0?'text-danger':'text-success' ?>"><?= formatPKR($evm['vac']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Budget Summary</div>
                <div class="card-body">
                    <table class="table mb-0">
                        <tr><td>Original Approved Budget</td><td class="text-end"><?= formatPKR($project['approved_budget']) ?></td></tr>
                        <tr><td>Current Budget (after CRs)</td><td class="text-end fw-bold"><?= formatPKR($project['current_budget']) ?></td></tr>
                        <tr><td>Sum of Activity Planned Costs</td><td class="text-end"><?= formatPKR($totalPlanned) ?></td></tr>
                        <tr><td>Sum of Actual Costs to Date</td><td class="text-end text-danger"><?= formatPKR($totalActual) ?></td></tr>
                        <tr class="table-light"><td>Remaining Budget</td><td class="text-end fw-bold"><?= formatPKR($project['current_budget'] - $totalActual) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Cost Breakdown -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Activity Cost Breakdown</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th><th>Activity</th><th>Planned Cost</th><th>Actual Cost</th><th>% Complete</th><th>Earned Value</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $a): 
                            $ev_act = $a['planned_cost'] * $a['percent_complete'] / 100;
                        ?>
                        <tr>
                            <td><code><?= $a['activity_code'] ?></code></td>
                            <td><?= htmlspecialchars($a['name']) ?></td>
                            <td><?= formatPKR($a['planned_cost']) ?></td>
                            <td><?= formatPKR($a['actual_cost']) ?></td>
                            <td><?= $a['percent_complete'] ?>%</td>
                            <td><?= formatPKR($ev_act) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2">Total</th>
                            <th><?= formatPKR($totalPlanned) ?></th>
                            <th><?= formatPKR($totalActual) ?></th>
                            <th></th>
                            <th><?= formatPKR($evm['ev']) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
