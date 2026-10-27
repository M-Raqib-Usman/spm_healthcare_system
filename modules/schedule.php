<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
$users = $pdo->query("SELECT id, name, role FROM users")->fetchAll();

// Add activity
if (isset($_POST['add_activity'])) {
    $code = 'ACT-' . str_pad(($pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO activities (project_id, activity_code, name, description, assigned_to, planned_start, planned_end, duration_days, planned_cost, status) VALUES (1,?,?,?,?,?,?,?,?, 'Not Started')");
    $stmt->execute([
        $code, $_POST['name'], $_POST['description'], $_POST['assigned_to'] ?: null,
        $_POST['planned_start'], $_POST['planned_end'], $_POST['duration_days'], $_POST['planned_cost']
    ]);
    $newId = (int)$pdo->lastInsertId();

    if (!empty($_POST['predecessor_id'])) {
        $depStmt = $pdo->prepare("INSERT INTO activity_dependencies (activity_id, predecessor_id, dependency_type, lag_days) VALUES (?, ?, ?, ?)");
        $depStmt->execute([$newId, (int)$_POST['predecessor_id'], $_POST['dependency_type'] ?? 'FS', (int)($_POST['lag_days'] ?? 0)]);
    }

    $msg = "Activity $code added successfully!";
}

// Update progress
if (isset($_POST['update_progress'])) {
    $id = (int)$_POST['act_id'];
    $pct = (float)$_POST['percent_complete'];
    $status = $_POST['status'];
    $actual_cost = (float)$_POST['actual_cost'];
    $stmt = $pdo->prepare("UPDATE activities SET percent_complete=?, status=?, actual_cost=? WHERE id=?");
    $stmt->execute([$pct, $status, $actual_cost, $id]);
    if ($status === 'Completed') {
        $pdo->prepare("UPDATE activities SET actual_end=CURDATE() WHERE id=?")->execute([$id]);
    }
    $msg = "Activity updated!";
}

$activities = $pdo->query("
    SELECT a.*, u.name as assignee 
    FROM activities a 
    LEFT JOIN users u ON a.assigned_to = u.id 
    ORDER BY a.planned_start, a.id
")->fetchAll();

// Query all dependencies
$rawDeps = $pdo->query("
    SELECT ad.*, p.activity_code as pred_code, p.name as pred_name
    FROM activity_dependencies ad
    JOIN activities p ON ad.predecessor_id = p.id
")->fetchAll();
$predMap = [];
foreach ($rawDeps as $d) {
    $predMap[$d['activity_id']][] = $d['pred_code'] . ' (' . $d['dependency_type'] . ($d['lag_days'] > 0 ? '+' . $d['lag_days'] . 'd' : '') . ')';
}

$delayed = getDelayedActivities($pdo);
$impactReport = calculateDelayImpact($pdo);
$project = getProject($pdo);

// -----------------------------------------------------
// Chart & Gantt Data Preparation
// -----------------------------------------------------
$totalActivities = count($activities);
$statusCounts = [
    'Completed' => 0,
    'In Progress' => 0,
    'Delayed' => 0,
    'Not Started' => 0,
    'On Hold' => 0
];
$totalProgressSum = 0;
foreach ($activities as $a) {
    $st = $a['status'] ?? 'Not Started';
    if (isset($statusCounts[$st])) {
        $statusCounts[$st]++;
    } else {
        $statusCounts['Not Started']++;
    }
    $totalProgressSum += (float)$a['percent_complete'];
}
$overallProgressPct = $totalActivities > 0 ? round($totalProgressSum / $totalActivities, 1) : 0;

// Resource / Assignee Workload Breakdown
$assigneeMap = [];
foreach ($activities as $a) {
    $user = !empty($a['assignee']) ? $a['assignee'] : 'Unassigned';
    if (!isset($assigneeMap[$user])) {
        $assigneeMap[$user] = ['completed' => 0, 'in_progress' => 0, 'delayed' => 0, 'not_started' => 0, 'total' => 0];
    }
    $assigneeMap[$user]['total']++;
    if ($a['status'] === 'Completed') {
        $assigneeMap[$user]['completed']++;
    } elseif ($a['status'] === 'Delayed') {
        $assigneeMap[$user]['delayed']++;
    } elseif ($a['status'] === 'In Progress') {
        $assigneeMap[$user]['in_progress']++;
    } else {
        $assigneeMap[$user]['not_started']++;
    }
}

// Activity ID to Code map for Gantt dependency links
$actIdToCode = [];
foreach ($activities as $a) {
    $actIdToCode[$a['id']] = $a['activity_code'];
}
$actDepsMap = [];
foreach ($rawDeps as $d) {
    if (isset($actIdToCode[$d['predecessor_id']])) {
        $actDepsMap[$d['activity_id']][] = $actIdToCode[$d['predecessor_id']];
    }
}

// Build Gantt Task objects
$ganttTasks = [];
foreach ($activities as $a) {
    $sDate = $a['planned_start'];
    $eDate = $a['planned_end'];
    // Frappe Gantt requires end date > start date
    if (strtotime($eDate) <= strtotime($sDate)) {
        $eDate = date('Y-m-d', strtotime($sDate . ' +1 day'));
    }

    $customClass = 'gantt-bar-default';
    if ($a['status'] === 'Completed') $customClass = 'gantt-bar-completed';
    elseif ($a['status'] === 'Delayed') $customClass = 'gantt-bar-delayed';
    elseif ($a['status'] === 'In Progress') $customClass = 'gantt-bar-progress';
    elseif ($a['status'] === 'On Hold') $customClass = 'gantt-bar-hold';

    $deps = !empty($actDepsMap[$a['id']]) ? implode(', ', $actDepsMap[$a['id']]) : '';

    $ganttTasks[] = [
        'id' => $a['activity_code'],
        'name' => $a['activity_code'] . ': ' . $a['name'],
        'start' => $sDate,
        'end' => $eDate,
        'progress' => (int)$a['percent_complete'],
        'dependencies' => $deps,
        'custom_class' => $customClass,
        'assignee' => $a['assignee'] ?: 'Unassigned',
        'status' => $a['status'],
        'duration' => $a['duration_days']
    ];
}

include '../includes/header.php';
?>

<!-- Frappe Gantt Stylesheet -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">

<style>
/* Gantt Custom Styling */
.gantt-container-wrapper {
    overflow-x: auto;
    background: #ffffff;
    border-radius: 8px;
    padding: 12px 6px;
    min-height: 400px;
}
.gantt .bar {
    rx: 4px;
    ry: 4px;
}
.gantt .gantt-bar-completed .bar { fill: #d1e7dd !important; stroke: #198754; stroke-width: 1px; }
.gantt .gantt-bar-completed .bar-progress { fill: #198754 !important; }

.gantt .gantt-bar-progress .bar { fill: #cfe2ff !important; stroke: #0d6efd; stroke-width: 1px; }
.gantt .gantt-bar-progress .bar-progress { fill: #0d6efd !important; }

.gantt .gantt-bar-delayed .bar { fill: #f8d7da !important; stroke: #dc3545; stroke-width: 1px; }
.gantt .gantt-bar-delayed .bar-progress { fill: #dc3545 !important; }

.gantt .gantt-bar-hold .bar { fill: #fff3cd !important; stroke: #ffc107; stroke-width: 1px; }
.gantt .gantt-bar-hold .bar-progress { fill: #ffc107 !important; }

.gantt .gantt-bar-default .bar { fill: #e9ecef !important; stroke: #6c757d; stroke-width: 1px; }
.gantt .gantt-bar-default .bar-progress { fill: #6c757d !important; }

.gantt .bar-label {
    fill: #212529 !important;
    font-size: 11px;
    font-weight: 600;
}
.gantt-legend-badge {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 5px;
    vertical-align: middle;
}
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-calendar-week text-primary"></i> Schedule & Resource Management</h2>
            <p class="text-muted mb-0">Project timeline, interactive Gantt chart, resource workload, and activity dependency tracking.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> Add Activity
        </button>
    </div>

    <?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- KPI Metric Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3 h-100">
                <small class="text-muted fw-semibold">Total Activities</small>
                <h3 class="mb-0 mt-1 fw-bold"><?= $totalActivities ?></h3>
                <small class="text-secondary"><?= $project['name'] ?? 'Project' ?></small>
            </div>
        </div>
        <div class="col-md-3 col-sm-8">
            <div class="card border-0 shadow-sm py-3 px-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted fw-semibold">Overall Schedule Progress</small>
                    <span class="badge bg-primary"><?= $overallProgressPct ?>%</span>
                </div>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: <?= $overallProgressPct ?>%"></div>
                </div>
                <small class="text-muted mt-2">Avg across <?= $totalActivities ?> tasks</small>
            </div>
        </div>
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3 h-100 border-start border-4 border-success">
                <small class="text-muted fw-semibold">Completed</small>
                <h3 class="mb-0 mt-1 text-success fw-bold"><?= $statusCounts['Completed'] ?></h3>
                <small class="text-muted"><?= $totalActivities ? round(($statusCounts['Completed']/$totalActivities)*100) : 0 ?>% of total</small>
            </div>
        </div>
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3 h-100 border-start border-4 border-primary">
                <small class="text-muted fw-semibold">In Progress</small>
                <h3 class="mb-0 mt-1 text-primary fw-bold"><?= $statusCounts['In Progress'] ?></h3>
                <small class="text-muted">Currently active</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3 h-100 border-start border-4 border-danger">
                <small class="text-muted fw-semibold">Delayed Activities</small>
                <h3 class="mb-0 mt-1 text-danger fw-bold"><?= count($delayed) ?></h3>
                <small class="text-danger"><?= count($delayed) > 0 ? 'Requires attention' : 'On schedule' ?></small>
            </div>
        </div>
    </div>

    <!-- Delay Impact Report (if any) -->
    <?php if (!empty($impactReport)): ?>
    <div class="card border-warning shadow-sm mb-4">
        <div class="card-header bg-warning bg-opacity-25 fw-bold text-dark d-flex justify-content-between align-items-center">
            <span><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Delayed Activities & Calculated Impact on Dependencies & Deadline</span>
            <span class="badge bg-warning text-dark"><?= count($impactReport) ?> Delayed</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Delayed Activity</th>
                            <th>Planned End</th>
                            <th>Current Delay</th>
                            <th>Impacted Dependent Activities</th>
                            <th>Projected Final Deadline Impact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($impactReport as $imp): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($imp['activity']['name']) ?></strong> (<code><?= $imp['activity']['activity_code'] ?></code>)</td>
                            <td><?= date('d M Y', strtotime($imp['activity']['planned_end'])) ?></td>
                            <td><span class="badge bg-danger">+<?= $imp['delay_days'] ?> days delay</span></td>
                            <td>
                                <?php if (!empty($imp['dependents'])): ?>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($imp['dependents'] as $dep): ?>
                                            <li><code><?= $dep['activity_code'] ?></code> <?= htmlspecialchars($dep['name']) ?> (<?= $dep['dependency_type'] ?>)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted">No direct downstream successors</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-danger">+<?= $imp['projected_delay_days'] ?> days</strong> to planned milestone
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Interactive Charts Section: Status Breakdown & Resource Workload -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-pie-chart text-primary me-2"></i> Activity Status Breakdown</span>
                    <span class="badge bg-light text-dark border"><?= $totalActivities ?> Tasks</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div style="position: relative; height: 240px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mt-3 pt-2 border-top">
                        <small class="text-muted"><span class="gantt-legend-badge" style="background:#198754;"></span> Completed: <strong><?= $statusCounts['Completed'] ?></strong></small>
                        <small class="text-muted"><span class="gantt-legend-badge" style="background:#0d6efd;"></span> In Progress: <strong><?= $statusCounts['In Progress'] ?></strong></small>
                        <small class="text-muted"><span class="gantt-legend-badge" style="background:#dc3545;"></span> Delayed: <strong><?= $statusCounts['Delayed'] ?></strong></small>
                        <small class="text-muted"><span class="gantt-legend-badge" style="background:#6c757d;"></span> Not Started: <strong><?= $statusCounts['Not Started'] ?></strong></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-workspace text-primary me-2"></i> Resource Allocation & Workload by Assignee</span>
                    <span class="badge bg-light text-dark border"><?= count($assigneeMap) ?> Team Members</span>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 260px;">
                        <canvas id="workloadChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Gantt Chart & Timeline Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-steps text-primary me-2"></i> Project Timeline & Gantt Chart</h5>
                    <small class="text-muted">Interactive visual schedule with task dependencies and milestone tracking</small>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Gantt View Mode Switcher -->
                    <div class="btn-group btn-group-sm" role="group" id="gantt-view-modes">
                        <button type="button" class="btn btn-outline-secondary" data-mode="Quarter Day" style="display:none;">Q-Day</button>
                        <button type="button" class="btn btn-outline-primary" data-mode="Day">Day</button>
                        <button type="button" class="btn btn-outline-primary active" data-mode="Week">Week</button>
                        <button type="button" class="btn btn-outline-primary" data-mode="Month">Month</button>
                    </div>

                    <!-- View Switcher Tabs -->
                    <ul class="nav nav-pills nav-pills-sm" id="timelineTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-1 px-3" id="gantt-tab" data-bs-toggle="pill" data-bs-target="#tab-gantt" type="button">
                                <i class="bi bi-diagram-3"></i> Gantt Chart
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3" id="table-tab" data-bs-toggle="pill" data-bs-target="#tab-table" type="button">
                                <i class="bi bi-table"></i> Timeline Grid
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Gantt Legend -->
            <div class="d-flex flex-wrap gap-3 mt-3 pt-2 border-top small text-muted align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-info-circle"></i> Legend:</span>
                <span><span class="gantt-legend-badge" style="background:#198754;"></span> Completed</span>
                <span><span class="gantt-legend-badge" style="background:#0d6efd;"></span> In Progress</span>
                <span><span class="gantt-legend-badge" style="background:#dc3545;"></span> Delayed</span>
                <span><span class="gantt-legend-badge" style="background:#6c757d;"></span> Not Started</span>
                <span class="ms-auto text-secondary"><i class="bi bi-arrow-right-short"></i> Arrows indicate Predecessor-Successor dependencies</span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="timelineTabContent">
                <!-- Tab 1: Interactive Frappe Gantt Chart -->
                <div class="tab-pane fade show active" id="tab-gantt" role="tabpanel">
                    <div class="gantt-container-wrapper">
                        <div id="gantt-target"></div>
                    </div>
                </div>

                <!-- Tab 2: Classic Gantt-style Table View -->
                <div class="tab-pane fade p-3" id="tab-table" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="min-width:900px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:220px;">Activity</th>
                                    <th>Assignee</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Duration</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th style="width:300px;">Timeline Bar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $minDate = strtotime($project['start_date']);
                                $maxDate = strtotime($project['planned_end_date']);
                                $totalDays = max(1, ($maxDate - $minDate) / 86400);
                                foreach ($activities as $a): 
                                    $start = strtotime($a['planned_start']);
                                    $end = strtotime($a['planned_end']);
                                    $left = max(0, (($start - $minDate) / 86400) / $totalDays * 100);
                                    $width = max(2, (($end - $start) / 86400) / $totalDays * 100);
                                    $color = $a['status']=='Completed'?'#198754':($a['status']=='Delayed'?'#dc3545':($a['status']=='In Progress'?'#0d6efd':'#6c757d'));
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($a['name']) ?></strong><br><small class="text-muted"><?= $a['activity_code'] ?></small></td>
                                    <td><?= htmlspecialchars($a['assignee'] ?? '-') ?></td>
                                    <td><?= date('d M', strtotime($a['planned_start'])) ?></td>
                                    <td><?= date('d M', strtotime($a['planned_end'])) ?></td>
                                    <td><?= $a['duration_days'] ?>d</td>
                                    <td>
                                        <div class="progress" style="height:18px;">
                                            <div class="progress-bar" style="width:<?= $a['percent_complete'] ?>%"><?= $a['percent_complete'] ?>%</div>
                                        </div>
                                    </td>
                                    <td><?= statusBadge($a['status']) ?></td>
                                    <td>
                                        <div style="position:relative;height:22px;background:#f1f3f5;border-radius:4px;">
                                            <div style="position:absolute;left:<?= $left ?>%;width:<?= $width ?>%;height:100%;background:<?= $color ?>;border-radius:4px;" title="<?= htmlspecialchars($a['name']) ?>"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity List with Edit / Update -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-task text-primary me-2"></i> All Activities & Progress Records</span>
            <span class="badge bg-secondary"><?= count($activities) ?> items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Assignee</th>
                            <th>Dependencies</th>
                            <th>Planned Cost</th>
                            <th>Actual Cost</th>
                            <th style="width: 140px;">Progress</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $a): ?>
                        <tr>
                            <td><code><?= $a['activity_code'] ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($a['name']) ?></strong>
                                <?php if (!empty($a['description'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($a['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($a['assignee'] ?? '—') ?></td>
                            <td><small><?= !empty($predMap[$a['id']]) ? implode('<br>', $predMap[$a['id']]) : '<span class="text-muted">—</span>' ?></small></td>
                            <td><?= formatPKR($a['planned_cost']) ?></td>
                            <td><?= formatPKR($a['actual_cost']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $a['status']=='Completed'?'bg-success':($a['status']=='Delayed'?'bg-danger':'bg-primary') ?>" style="width: <?= $a['percent_complete'] ?>%"></div>
                                    </div>
                                    <small class="fw-bold"><?= $a['percent_complete'] ?>%</small>
                                </div>
                            </td>
                            <td><?= statusBadge($a['status']) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $a['id'] ?>">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="edit<?= $a['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update: <?= htmlspecialchars($a['name']) ?> (<?= $a['activity_code'] ?>)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="act_id" value="<?= $a['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">% Complete</label>
                                                <input type="number" name="percent_complete" class="form-control" min="0" max="100" step="5" value="<?= $a['percent_complete'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <?php foreach (['Not Started','In Progress','Completed','Delayed','On Hold'] as $s): ?>
                                                        <option value="<?= $s ?>" <?= $a['status']==$s?'selected':'' ?>><?= $s ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Actual Cost (PKR)</label>
                                                <input type="number" name="actual_cost" class="form-control" value="<?= $a['actual_cost'] ?>" step="1000">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_progress" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Activity Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Add New Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Activity Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., API Integration">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief activity scope..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Predecessor Dependency</label>
                            <select name="predecessor_id" class="form-select">
                                <option value="">— None (No Predecessor) —</option>
                                <?php foreach ($activities as $actOpt): ?>
                                    <option value="<?= $actOpt['id'] ?>"><?= $actOpt['activity_code'] ?> – <?= htmlspecialchars($actOpt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Dependency Type</label>
                            <select name="dependency_type" class="form-select">
                                <option value="FS" selected>FS (Finish-to-Start)</option>
                                <option value="SS">SS (Start-to-Start)</option>
                                <option value="FF">FF (Finish-to-Finish)</option>
                                <option value="SF">SF (Start-to-Finish)</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Lag (Days)</label>
                            <input type="number" name="lag_days" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Planned Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="planned_start" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Planned End Date <span class="text-danger">*</span></label>
                            <input type="date" name="planned_end" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Duration (Days)</label>
                            <input type="number" name="duration_days" class="form-control" value="5" min="1">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Planned Cost (PKR)</label>
                            <input type="number" name="planned_cost" class="form-control" value="0" step="1000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_activity" class="btn btn-primary">Add Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js & Frappe Gantt CDN Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // -----------------------------------------------------
    // 1. Chart.js: Activity Status Breakdown (Donut Chart)
    // -----------------------------------------------------
    const statusData = <?= json_encode([
        'labels' => ['Completed', 'In Progress', 'Delayed', 'Not Started', 'On Hold'],
        'values' => [
            $statusCounts['Completed'],
            $statusCounts['In Progress'],
            $statusCounts['Delayed'],
            $statusCounts['Not Started'],
            $statusCounts['On Hold']
        ]
    ]) ?>;

    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusData.labels,
                datasets: [{
                    data: statusData.values,
                    backgroundColor: [
                        '#198754', // Completed (green)
                        '#0d6efd', // In Progress (blue)
                        '#dc3545', // Delayed (red)
                        '#6c757d', // Not Started (gray)
                        '#ffc107'  // On Hold (yellow)
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.raw || 0;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '62%'
            }
        });
    }

    // -----------------------------------------------------
    // 2. Chart.js: Resource Allocation & Workload (Stacked Bar)
    // -----------------------------------------------------
    <?php
    $assigneeLabels = array_keys($assigneeMap);
    $compData = [];
    $inProgData = [];
    $delData = [];
    $notStartData = [];
    foreach ($assigneeMap as $usr => $stat) {
        $compData[] = $stat['completed'];
        $inProgData[] = $stat['in_progress'];
        $delData[] = $stat['delayed'];
        $notStartData[] = $stat['not_started'];
    }
    ?>
    const workloadLabels = <?= json_encode($assigneeLabels) ?>;
    const completedTasks = <?= json_encode($compData) ?>;
    const inProgressTasks = <?= json_encode($inProgData) ?>;
    const delayedTasks = <?= json_encode($delData) ?>;
    const notStartedTasks = <?= json_encode($notStartData) ?>;

    const ctxWorkload = document.getElementById('workloadChart');
    if (ctxWorkload) {
        new Chart(ctxWorkload, {
            type: 'bar',
            data: {
                labels: workloadLabels,
                datasets: [
                    {
                        label: 'Completed',
                        data: completedTasks,
                        backgroundColor: '#198754'
                    },
                    {
                        label: 'In Progress',
                        data: inProgressTasks,
                        backgroundColor: '#0d6efd'
                    },
                    {
                        label: 'Delayed',
                        data: delayedTasks,
                        backgroundColor: '#dc3545'
                    },
                    {
                        label: 'Not Started',
                        data: notStartedTasks,
                        backgroundColor: '#6c757d'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        title: {
                            display: true,
                            text: 'Number of Activities',
                            font: { size: 12 }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    // -----------------------------------------------------
    // 3. Frappe Gantt Chart Initialization
    // -----------------------------------------------------
    const ganttTasksRaw = <?= json_encode($ganttTasks) ?>;

    if (ganttTasksRaw && ganttTasksRaw.length > 0 && typeof Gantt !== 'undefined') {
        let gantt = null;
        try {
            gantt = new Gantt("#gantt-target", ganttTasksRaw, {
                header_height: 48,
                column_width: 32,
                step: 24,
                view_modes: ['Quarter Day', 'Half Day', 'Day', 'Week', 'Month'],
                bar_height: 24,
                bar_corner_radius: 4,
                arrow_curve: 5,
                padding: 16,
                view_mode: 'Week',
                date_format: 'YYYY-MM-DD',
                custom_popup_html: function(task) {
                    const matched = ganttTasksRaw.find(t => t.id === task.id) || task;
                    const statusClass = matched.status === 'Completed' ? 'bg-success' : (matched.status === 'Delayed' ? 'bg-danger' : (matched.status === 'In Progress' ? 'bg-primary' : 'bg-secondary'));
                    return `
                        <div class="card shadow-sm border-0" style="min-width: 240px; padding: 10px;">
                            <h6 class="fw-bold mb-1 text-primary">${task.name}</h6>
                            <div class="small text-muted mb-1"><i class="bi bi-person me-1"></i> ${matched.assignee || 'Unassigned'}</div>
                            <div class="small mb-1"><i class="bi bi-calendar-event me-1"></i> ${matched.start} &rarr; ${matched.end} (${matched.duration}d)</div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge ${statusClass}">${matched.status}</span>
                                <span class="small fw-bold">${task.progress}% done</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar ${statusClass}" style="width: ${task.progress}%"></div>
                            </div>
                            ${matched.dependencies ? `<div class="small text-muted mt-2"><i class="bi bi-diagram-2 me-1"></i> Predecessors: <code>${matched.dependencies}</code></div>` : ''}
                        </div>
                    `;
                }
            });

            // View mode switchers (Day, Week, Month)
            const modeBtns = document.querySelectorAll('#gantt-view-modes button');
            modeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    modeBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const mode = this.getAttribute('data-mode');
                    if (gantt && mode) {
                        gantt.change_view_mode(mode);
                    }
                });
            });

            // Tab switch handler: refresh Gantt when tab is shown
            const ganttTabEl = document.getElementById('gantt-tab');
            if (ganttTabEl) {
                ganttTabEl.addEventListener('shown.bs.tab', function () {
                    if (gantt) gantt.refresh(ganttTasksRaw);
                });
            }
        } catch (err) {
            console.error("Gantt Chart render error:", err);
            const container = document.getElementById('gantt-target');
            if (container) {
                container.innerHTML = '<div class="alert alert-info m-3">Interactive Gantt chart initialized with fallback table below.</div>';
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
