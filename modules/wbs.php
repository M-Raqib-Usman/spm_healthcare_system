<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';

if (isset($_POST['add_wbs'])) {
    $stmt = $pdo->prepare("INSERT INTO wbs (project_id, parent_id, wbs_code, name, description, level, type) VALUES (1,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['parent_id'] ?: null,
        $_POST['wbs_code'],
        $_POST['name'],
        $_POST['description'],
        (int)$_POST['level'],
        $_POST['type']
    ]);
    $msg = "WBS item added!";
}

$wbsItems = $pdo->query("SELECT * FROM wbs ORDER BY wbs_code")->fetchAll();

// Build hierarchy helper
function renderWBS($items, $parentId = null, $level = 0) {
    $html = '';
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
            $badge = $item['type']=='Deliverable' ? 'primary' : ($item['type']=='Work Package' ? 'info' : 'secondary');
            $html .= '<tr>';
            $html .= '<td>' . $indent . '<code>' . htmlspecialchars($item['wbs_code']) . '</code></td>';
            $html .= '<td>' . $indent . htmlspecialchars($item['name']) . '</td>';
            $html .= '<td><span class="badge bg-' . $badge . '">' . $item['type'] . '</span></td>';
            $html .= '<td>' . htmlspecialchars($item['description'] ?? '') . '</td>';
            $html .= '</tr>';
            $html .= renderWBS($items, $item['id'], $level + 1);
        }
    }
    return $html;
}

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-diagram-3"></i> Work Breakdown Structure (WBS)</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add WBS Item</button>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>WBS Code</th><th>Name</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <?= renderWBS($wbsItems) ?>
                        <?php if (empty($wbsItems)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No WBS items yet. Add deliverables and work packages.</td></tr>
                        <?php endif; ?>
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
                <div class="modal-header"><h5 class="modal-title">Add WBS Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">WBS Code</label><input type="text" name="wbs_code" class="form-control" required placeholder="e.g. 1.2.1"></div>
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Parent Item</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— Top Level —</option>
                            <?php foreach ($wbsItems as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= $w['wbs_code'] ?> – <?= htmlspecialchars($w['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Level</label>
                            <select name="level" class="form-select"><option>1</option><option>2</option><option>3</option><option>4</option></select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option>Deliverable</option><option selected>Work Package</option><option>Activity</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="add_wbs" class="btn btn-primary">Add</button></div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
