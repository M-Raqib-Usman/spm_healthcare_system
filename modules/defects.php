<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
$users = $pdo->query("SELECT id, name FROM users")->fetchAll();

if (isset($_POST['add_defect'])) {
    $code = 'DEF-' . str_pad(($pdo->query("SELECT COUNT(*) FROM defects")->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO defects (project_id, defect_code, title, description, severity, status, assigned_to, reported_by) VALUES (1,?,?,?,?,'Open',?,1)");
    $stmt->execute([$code, $_POST['title'], $_POST['description'], $_POST['severity'], $_POST['assigned_to'] ?: null]);
    $msg = "Defect $code recorded!";
}

if (isset($_POST['update_defect'])) {
    $id = (int)$_POST['def_id'];
    $status = $_POST['status'];
    $notes = $_POST['resolution_notes'] ?? '';
    if (in_array($status, ['Resolved','Closed'])) {
        $pdo->prepare("UPDATE defects SET status=?, resolution_notes=?, resolved_at=NOW() WHERE id=?")->execute([$status, $notes, $id]);
    } else {
        $pdo->prepare("UPDATE defects SET status=?, resolution_notes=? WHERE id=?")->execute([$status, $notes, $id]);
    }
    $msg = "Defect updated!";
}

$defects = $pdo->query("SELECT d.*, u.name as assignee FROM defects d LEFT JOIN users u ON d.assigned_to=u.id ORDER BY FIELD(d.severity,'Critical','High','Medium','Low'), d.id DESC")->fetchAll();

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-bug"></i> Defect Tracking</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Report Defect</button>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th><th>Title</th><th>Severity</th><th>Status</th><th>Assigned To</th><th>Reported</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($defects as $d): ?>
                        <tr class="<?= $d['severity']=='Critical' && in_array($d['status'],['Open','In Progress','Reopened']) ? 'table-danger' : '' ?>">
                            <td><code><?= $d['defect_code'] ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($d['title']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars(substr($d['description']??'',0,70)) ?></small>
                            </td>
                            <td><?= statusBadge($d['severity']) ?></td>
                            <td><?= statusBadge($d['status']) ?></td>
                            <td><?= htmlspecialchars($d['assignee'] ?? 'Unassigned') ?></td>
                            <td><?= date('d M Y', strtotime($d['reported_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $d['id'] ?>">Update</button>
                            </td>
                        </tr>

                        <div class="modal fade" id="edit<?= $d['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header"><h5 class="modal-title">Update Defect</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <input type="hidden" name="def_id" value="<?= $d['id'] ?>">
                                            <p><strong><?= htmlspecialchars($d['title']) ?></strong></p>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <?php foreach (['Open','In Progress','Resolved','Closed','Reopened'] as $s): ?>
                                                        <option value="<?= $s ?>" <?= $d['status']==$s?'selected':'' ?>><?= $s ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Resolution Notes</label>
                                                <textarea name="resolution_notes" class="form-control" rows="3"><?= htmlspecialchars($d['resolution_notes']??'') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer"><button type="submit" name="update_defect" class="btn btn-primary">Save</button></div>
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

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Report New Defect</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required placeholder="e.g. Appointment scheduling conflict"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option>Critical</option><option>High</option><option selected>Medium</option><option>Low</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="add_defect" class="btn btn-primary">Report Defect</button></div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
