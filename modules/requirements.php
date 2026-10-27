<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
$users = $pdo->query("SELECT id, name FROM users")->fetchAll();

// Add requirement
if (isset($_POST['add_req'])) {
    $code = 'REQ-' . str_pad(($pdo->query("SELECT COUNT(*) FROM requirements")->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO requirements (project_id, req_code, title, description, priority, status, source, created_by) VALUES (1,?,?,?,?, 'Proposed', ?, 1)");
    $stmt->execute([$code, $_POST['title'], $_POST['description'], $_POST['priority'], $_POST['source']]);
    $msg = "Requirement $code added successfully!";
}

// Update status
if (isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $id = (int)$_POST['req_id'];
    if ($status === 'Approved') {
        $stmt = $pdo->prepare("UPDATE requirements SET status=?, approved_by=1, approved_at=NOW() WHERE id=?");
        $stmt->execute([$status, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE requirements SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
    }
    $msg = "Requirement status updated!";
}

$requirements = $pdo->query("SELECT r.*, u.name as creator FROM requirements r LEFT JOIN users u ON r.created_by=u.id ORDER BY r.id")->fetchAll();

    $approvedCount = count(array_filter($requirements, fn($r) => $r['status'] === 'Approved'));
    $unapprovedCount = count($requirements) - $approvedCount;

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2><i class="bi bi-list-check"></i> Requirements & Scope Management</h2>
            <p class="text-muted mb-0">Define, prioritize, and baseline approved scope items.</p>
        </div>
        <div>
            <span class="badge bg-success fs-6 me-2"><i class="bi bi-check-circle"></i> <?= $approvedCount ?> Approved in Scope</span>
            <span class="badge bg-secondary fs-6 me-2"><i class="bi bi-slash-circle"></i> <?= $unapprovedCount ?> Excluded from Scope</span>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Requirement</button>
        </div>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-shield-lock-fill fs-4 me-3 text-primary"></i>
        <div>
            <strong>Scope Boundary Rule:</strong> Only requirements with <strong>Approved</strong> status are admitted into the official project scope baseline and work packages. Proposed, deferred, or rejected items are strictly excluded from development activities.
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Requirements Traceability & Scope Status</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th><th>Requirement Title</th><th>Priority</th><th>Scope Status</th><th>Source</th><th>Approval Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requirements as $r): 
                            $isApproved = ($r['status'] === 'Approved');
                        ?>
                        <tr class="<?= $isApproved ? 'table-success bg-opacity-25' : ($r['status']=='Rejected'?'table-danger bg-opacity-25':'') ?>">
                            <td><code><?= htmlspecialchars($r['req_code']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($r['title']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars(substr($r['description']??'',0,80)) ?>...</small>
                            </td>
                            <td><?= statusBadge($r['priority']) ?></td>
                            <td>
                                <?= statusBadge($r['status']) ?>
                                <?php if ($isApproved): ?>
                                    <span class="badge bg-success ms-1"><i class="bi bi-check"></i> In Scope</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-1"><i class="bi bi-x"></i> Excluded</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['source'] ?? '-') ?></td>
                            <td>
                                <?php if ($r['status'] !== 'Approved' && $r['status'] !== 'Rejected'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                        <option value="">Change...</option>
                                        <option value="Approved">Approve (Include in Scope)</option>
                                        <option value="Rejected">Reject (Exclude)</option>
                                        <option value="Deferred">Defer</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <?php else: ?>
                                    <span class="text-muted small"><?= $isApproved ? 'Approved by PM' : 'Rejected' ?></span>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Add New Requirement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select"><option>High</option><option selected>Medium</option><option>Low</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Source</label><input type="text" name="source" class="form-control" placeholder="e.g. Client, Hospital Management"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_req" class="btn btn-primary">Add Requirement</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
