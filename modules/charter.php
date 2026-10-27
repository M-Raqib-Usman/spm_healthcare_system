<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$project = getProject($pdo);
$charter = $pdo->query("SELECT * FROM project_charter WHERE project_id = 1")->fetch();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_charter'])) {
    $stmt = $pdo->prepare("UPDATE project_charter SET objectives=?, stakeholders=?, scope_boundaries=?, assumptions=?, constraints=?, success_criteria=? WHERE project_id=1");
    $stmt->execute([
        $_POST['objectives'], $_POST['stakeholders'], $_POST['scope_boundaries'],
        $_POST['assumptions'], $_POST['constraints'], $_POST['success_criteria']
    ]);
    $msg = 'Project Charter updated successfully!';
    $charter = $pdo->query("SELECT * FROM project_charter WHERE project_id = 1")->fetch();
}

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="bi bi-file-earmark-text"></i> Project Charter</h2>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Project Charter Details</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Project Objectives</label>
                            <textarea name="objectives" class="form-control" rows="4" required><?= htmlspecialchars($charter['objectives'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Stakeholders</label>
                            <textarea name="stakeholders" class="form-control" rows="3" required><?= htmlspecialchars($charter['stakeholders'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Scope Boundaries (In / Out of Scope)</label>
                            <textarea name="scope_boundaries" class="form-control" rows="3" required><?= htmlspecialchars($charter['scope_boundaries'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Assumptions</label>
                                <textarea name="assumptions" class="form-control" rows="3"><?= htmlspecialchars($charter['assumptions'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Constraints</label>
                                <textarea name="constraints" class="form-control" rows="3"><?= htmlspecialchars($charter['constraints'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Success Criteria</label>
                            <textarea name="success_criteria" class="form-control" rows="3"><?= htmlspecialchars($charter['success_criteria'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="update_charter" class="btn btn-primary"><i class="bi bi-save"></i> Update Charter</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-primary text-white">Project Summary</div>
                <div class="card-body">
                    <p><strong>Name:</strong><br><?= htmlspecialchars($project['name']) ?></p>
                    <p><strong>Approved Budget:</strong><br><?= formatPKR($project['approved_budget']) ?></p>
                    <p><strong>Current Budget:</strong><br><?= formatPKR($project['current_budget']) ?></p>
                    <p><strong>Start Date:</strong><br><?= date('d M Y', strtotime($project['start_date'])) ?></p>
                    <p><strong>Planned End:</strong><br><?= date('d M Y', strtotime($project['planned_end_date'])) ?></p>
                    <p><strong>Status:</strong> <?= statusBadge($project['status']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
