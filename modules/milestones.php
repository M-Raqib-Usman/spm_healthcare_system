<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
$project = getProject($pdo);

if (isset($_POST['add_milestone'])) {
    $stmt = $pdo->prepare("INSERT INTO milestones (project_id, name, planned_date, description) VALUES (1,?,?,?)");
    $stmt->execute([$_POST['name'], $_POST['planned_date'], $_POST['description']]);
    $msg = "Milestone added!";
}

if (isset($_POST['update_ms'])) {
    $id = (int)$_POST['ms_id'];
    $status = $_POST['status'];
    $actual = $status === 'Achieved' ? date('Y-m-d') : null;
    $pdo->prepare("UPDATE milestones SET status=?, actual_date=? WHERE id=?")->execute([$status, $actual, $id]);
    $msg = "Milestone updated!";
}

// Update project status
if (isset($_POST['update_project_status'])) {
    $pdo->prepare("UPDATE projects SET status=? WHERE id=1")->execute([$_POST['project_status']]);
    $msg = "Project status updated!";
    $project = getProject($pdo);
}

// Project closure records
if (isset($_POST['save_closure'])) {
    $actEnd = !empty($_POST['actual_end_date']) ? $_POST['actual_end_date'] : date('Y-m-d');
    $pdo->prepare("UPDATE projects SET status='Closed', actual_end_date=? WHERE id=1")->execute([$actEnd]);
    $msg = "Project closure record saved successfully!";
    $project = getProject($pdo);
}

$milestones = $pdo->query("SELECT * FROM milestones ORDER BY planned_date")->fetchAll();

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="bi bi-flag"></i> Milestones & Project Closure Records</h2>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Project Status & Responsibilities</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <select name="project_status" class="form-select">
                                <?php foreach (['Initiated','Planning','Executing','Monitoring','Closing','Closed'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $project['status']==$s?'selected':'' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_project_status" class="btn btn-primary w-100">Update Status</button>
                    </form>
                    <hr>
                    <p class="mb-1"><strong>Start:</strong> <?= date('d M Y', strtotime($project['start_date'])) ?></p>
                    <p class="mb-1"><strong>Planned End:</strong> <?= date('d M Y', strtotime($project['planned_end_date'])) ?></p>
                    <p class="mb-1"><strong>Actual End:</strong> <?= !empty($project['actual_end_date']) ? date('d M Y', strtotime($project['actual_end_date'])) : '<span class="text-muted">In Progress</span>' ?></p>
                    <p class="mb-0"><strong>Budget:</strong> <?= formatPKR($project['current_budget']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span>Project Milestones</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMs">+ Add</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Milestone</th><th>Planned Date</th><th>Actual Date</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($milestones as $m): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($m['name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($m['description']??'') ?></small>
                                </td>
                                <td><?= date('d M Y', strtotime($m['planned_date'])) ?></td>
                                <td><?= $m['actual_date'] ? date('d M Y', strtotime($m['actual_date'])) : '—' ?></td>
                                <td><?= statusBadge($m['status']) ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="ms_id" value="<?= $m['id'] ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="">Change...</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Achieved">Achieved</option>
                                            <option value="Missed">Missed</option>
                                        </select>
                                        <input type="hidden" name="update_ms" value="1">
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($milestones)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No milestones defined</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Closure Records Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold"><i class="bi bi-folder-check"></i> Project Closure Records & Sign-off</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-7">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Actual Completion Date</label>
                                <input type="date" name="actual_end_date" class="form-control" value="<?= $project['actual_end_date'] ?? date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Closure Status</label>
                                <select name="closure_status" class="form-select">
                                    <option value="Completed" <?= $project['status']=='Closed'?'selected':'' ?>>Approved for Closure (Go-Live Accepted)</option>
                                    <option value="Pending">Pending Final Client Sign-off</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lessons Learned & Closure Summary</label>
                            <textarea class="form-control" rows="3" readonly>All 5 hospital modules delivered and deployed. Approved CRs (AI diagnosis + mobile app) incorporated into final release. Core defect DEF-001 resolved before production handover.</textarea>
                        </div>
                        <button type="submit" name="save_closure" class="btn btn-success"><i class="bi bi-check2-circle"></i> Save & Record Project Closure</button>
                    </form>
                </div>
                <div class="col-md-5">
                    <div class="border rounded p-3 bg-light">
                        <h6 class="fw-bold mb-2">Project Handover Checklist</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Deliverables completed and inspected</li>
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Change Requests evaluated and implemented</li>
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Critical defects resolved in testing phase</li>
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Hospital admin & doctor training completed</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Source code & database schemas archived</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMs" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Add Milestone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Planned Date</label><input type="date" name="planned_date" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" name="add_milestone" class="btn btn-primary">Add</button></div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
