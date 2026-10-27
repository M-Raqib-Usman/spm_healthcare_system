<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$plan = $pdo->query("SELECT * FROM project_plans WHERE project_id = 1")->fetch();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE project_plans SET scope_summary=?, schedule_summary=?, cost_summary=?, quality_summary=?, risk_summary=?, communication_plan=? WHERE project_id=1");
    $stmt->execute([
        $_POST['scope_summary'], $_POST['schedule_summary'], $_POST['cost_summary'],
        $_POST['quality_summary'], $_POST['risk_summary'], $_POST['communication_plan']
    ]);
    $msg = 'Project Management Plan updated successfully!';
    $plan = $pdo->query("SELECT * FROM project_plans WHERE project_id = 1")->fetch();
}

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="bi bi-journal-text"></i> Project Management Plan</h2>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Scope Summary</label>
                        <textarea name="scope_summary" class="form-control" rows="4"><?= htmlspecialchars($plan['scope_summary'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Schedule Summary</label>
                        <textarea name="schedule_summary" class="form-control" rows="4"><?= htmlspecialchars($plan['schedule_summary'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Cost Summary</label>
                        <textarea name="cost_summary" class="form-control" rows="4"><?= htmlspecialchars($plan['cost_summary'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Quality Summary</label>
                        <textarea name="quality_summary" class="form-control" rows="4"><?= htmlspecialchars($plan['quality_summary'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Risk Summary</label>
                        <textarea name="risk_summary" class="form-control" rows="4"><?= htmlspecialchars($plan['risk_summary'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Communication Plan</label>
                        <textarea name="communication_plan" class="form-control" rows="4"><?= htmlspecialchars($plan['communication_plan'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Plan</button>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
