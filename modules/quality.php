<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
$users = $pdo->query("SELECT id, name FROM users")->fetchAll();

if (isset($_POST['add_checklist'])) {
    $stmt = $pdo->prepare("INSERT INTO quality_checklists (project_id, checklist_name, deliverable) VALUES (1,?,?)");
    $stmt->execute([$_POST['checklist_name'], $_POST['deliverable']]);
    $msg = "Checklist created!";
}

if (isset($_POST['add_item'])) {
    $stmt = $pdo->prepare("INSERT INTO quality_checklist_items (checklist_id, item_text) VALUES (?,?)");
    $stmt->execute([$_POST['checklist_id'], $_POST['item_text']]);
    $msg = "Checklist item added!";
}

if (isset($_POST['update_item'])) {
    $status = $_POST['status'];
    $id = (int)$_POST['item_id'];
    $pdo->prepare("UPDATE quality_checklist_items SET status=?, inspected_by=1, inspected_at=NOW(), comments=? WHERE id=?")
        ->execute([$status, $_POST['comments'] ?? '', $id]);
    $msg = "Inspection result recorded!";
}

$checklists = $pdo->query("SELECT * FROM quality_checklists ORDER BY id")->fetchAll();
$items = $pdo->query("SELECT i.*, c.checklist_name FROM quality_checklist_items i JOIN quality_checklists c ON i.checklist_id=c.id ORDER BY i.checklist_id, i.id")->fetchAll();

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clipboard-check"></i> Quality Checklists</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCheckModal"><i class="bi bi-plus-lg"></i> New Checklist</button>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <?php foreach ($checklists as $cl): 
        $clItems = array_filter($items, fn($i) => $i['checklist_id'] == $cl['id']);
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($cl['checklist_name']) ?></strong>
                <br><small class="text-muted">Deliverable: <?= htmlspecialchars($cl['deliverable'] ?? '-') ?></small>
            </div>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItem<?= $cl['id'] ?>">+ Item</button>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Checklist Item</th><th>Status</th><th>Comments</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($clItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_text']) ?></td>
                        <td><?= statusBadge($item['status']) ?></td>
                        <td><small><?= htmlspecialchars($item['comments'] ?? '') ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#insp<?= $item['id'] ?>">Inspect</button>
                        </td>
                    </tr>
                    <!-- Inspect Modal -->
                    <div class="modal fade" id="insp<?= $item['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header"><h5 class="modal-title">Record Inspection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <p><?= htmlspecialchars($item['item_text']) ?></p>
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Result</label>
                                            <select name="status" class="form-select">
                                                <option value="Passed">Passed</option>
                                                <option value="Failed">Failed</option>
                                                <option value="N/A">N/A</option>
                                                <option value="Pending">Pending</option>
                                            </select>
                                        </div>
                                        <div class="mb-3"><label class="form-label">Comments</label><textarea name="comments" class="form-control" rows="2"></textarea></div>
                                    </div>
                                    <div class="modal-footer"><button type="submit" name="update_item" class="btn btn-primary">Save</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($clItems)): ?>
                    <tr><td colspan="4" class="text-muted text-center">No items yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Item Modal for this checklist -->
    <div class="modal fade" id="addItem<?= $cl['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header"><h5 class="modal-title">Add Checklist Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="checklist_id" value="<?= $cl['id'] ?>">
                        <div class="mb-3"><label class="form-label">Item Text</label><input type="text" name="item_text" class="form-control" required></div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="add_item" class="btn btn-primary">Add</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="addCheckModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Create Quality Checklist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Checklist Name</label><input type="text" name="checklist_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Related Deliverable</label><input type="text" name="deliverable" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="submit" name="add_checklist" class="btn btn-primary">Create</button></div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
