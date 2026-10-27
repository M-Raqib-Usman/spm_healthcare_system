<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';

// Add CR
if (isset($_POST['add_cr'])) {
    $code = 'CR-' . str_pad(($pdo->query("SELECT COUNT(*) FROM change_requests")->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO change_requests (project_id, cr_code, title, description, justification, cost_impact, schedule_impact_days, status, requested_by) VALUES (1,?,?,?,?,?,?,'Submitted',1)");
    $stmt->execute([$code, $_POST['title'], $_POST['description'], $_POST['justification'], $_POST['cost_impact'], $_POST['schedule_impact']]);
    $msg = "Change Request $code submitted!";
}

// Approve / Reject / Implement
if (isset($_POST['action_cr'])) {
    $id = (int)$_POST['cr_id'];
    $action = $_POST['action_cr'];
    if ($action === 'approve') {
        $pdo->prepare("UPDATE change_requests SET status='Approved', reviewed_by=1, reviewed_at=NOW() WHERE id=?")->execute([$id]);
        $msg = "Change Request approved. You can now implement it to update budget & schedule.";
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE change_requests SET status='Rejected', reviewed_by=1, reviewed_at=NOW() WHERE id=?")->execute([$id]);
        $msg = "Change Request rejected.";
    } elseif ($action === 'implement') {
        if (applyChangeRequestImpact($pdo, $id)) {
            $msg = "Change Request implemented! Project budget and schedule have been updated.";
        } else {
            $msg = "Error: CR must be Approved first.";
        }
    }
}

$crs = $pdo->query("SELECT * FROM change_requests ORDER BY id DESC")->fetchAll();
$project = getProject($pdo);

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-arrow-left-right"></i> Change Request Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> New Change Request</button>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="alert alert-info">
        <strong>Current Project Budget:</strong> <?= formatPKR($project['current_budget']) ?> &nbsp;|&nbsp;
        <strong>Planned End Date:</strong> <?= date('d M Y', strtotime($project['planned_end_date'])) ?>
        <br><small>When you <strong>Implement</strong> an approved CR, the cost & schedule impact is automatically applied to the project.</small>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th><th>Title</th><th>Cost Impact</th><th>Schedule Impact</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($crs as $cr): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($cr['cr_code']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($cr['title']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars(substr($cr['description'],0,60)) ?>...</small>
                            </td>
                            <td class="<?= $cr['cost_impact']>0?'text-danger':'' ?>"><?= formatPKR($cr['cost_impact']) ?></td>
                            <td><?= $cr['schedule_impact_days'] ?> days</td>
                            <td><?= statusBadge($cr['status']) ?></td>
                            <td>
                                <?php if ($cr['status'] === 'Submitted' || $cr['status'] === 'Under Review'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="cr_id" value="<?= $cr['id'] ?>">
                                        <button name="action_cr" value="approve" class="btn btn-sm btn-success">Approve</button>
                                        <button name="action_cr" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                <?php elseif ($cr['status'] === 'Approved'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="cr_id" value="<?= $cr['id'] ?>">
                                        <button name="action_cr" value="implement" class="btn btn-sm btn-warning" onclick="return confirm('This will update project budget and end date. Continue?')">
                                            <i class="bi bi-check2-all"></i> Implement
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Submit Change Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required placeholder="e.g. AI-based Preliminary Diagnosis Module"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
                    <div class="mb-3"><label class="form-label">Justification</label><textarea name="justification" class="form-control" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cost Impact (PKR)</label>
                            <input type="number" name="cost_impact" class="form-control" value="0" step="1000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Schedule Impact (Days)</label>
                            <input type="number" name="schedule_impact" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_cr" class="btn btn-primary">Submit CR</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
