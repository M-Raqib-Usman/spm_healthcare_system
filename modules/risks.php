<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
$users = $pdo->query("SELECT id, name FROM users")->fetchAll();

if (isset($_POST['add_risk'])) {
    $code = 'RSK-' . str_pad(($pdo->query("SELECT COUNT(*) FROM risks")->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    $lik = (int)$_POST['likelihood'];
    $imp = (int)$_POST['impact'];
    $score = $lik * $imp;
    $priority = $score >= 16 ? 'Critical' : ($score >= 10 ? 'High' : ($score >= 5 ? 'Medium' : 'Low'));
    $stmt = $pdo->prepare("INSERT INTO risks (project_id, risk_code, title, description, category, likelihood, impact, priority, status, owner_id) VALUES (1,?,?,?,?,?,?,?,'Identified',?)");
    $stmt->execute([$code, $_POST['title'], $_POST['description'], $_POST['category'], $lik, $imp, $priority, $_POST['owner_id'] ?: null]);
    $msg = "Risk $code added!";
}

if (isset($_POST['add_response'])) {
    $stmt = $pdo->prepare("INSERT INTO risk_responses (risk_id, response_type, action_description, responsible_id) VALUES (?,?,?,?)");
    $stmt->execute([$_POST['risk_id'], $_POST['response_type'], $_POST['action_description'], $_POST['responsible_id'] ?: null]);
    $msg = "Risk response added!";
}

$risks = $pdo->query("SELECT r.*, u.name as owner FROM risks r LEFT JOIN users u ON r.owner_id=u.id ORDER BY r.score DESC")->fetchAll();
$responses = $pdo->query("SELECT rr.*, r.risk_code, r.title as risk_title, u.name as responsible FROM risk_responses rr JOIN risks r ON rr.risk_id=r.id LEFT JOIN users u ON rr.responsible_id=u.id ORDER BY rr.id")->fetchAll();

include '../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shield-exclamation"></i> Risk Management & PDPC</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRiskModal"><i class="bi bi-plus-lg"></i> Add Risk</button>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <!-- Prioritization Matrix -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Risk Prioritization Matrix (Likelihood × Impact)</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Risk</th><th>Likelihood (1-5)</th><th>Impact (1-5)</th><th>Score</th><th>Priority</th><th>Status</th><th>Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($risks as $r): ?>
                        <tr class="<?= $r['priority']=='Critical'?'table-danger':($r['priority']=='High'?'table-warning':'') ?>">
                            <td class="text-start">
                                <code><?= $r['risk_code'] ?></code> 
                                <strong><?= htmlspecialchars($r['title']) ?></strong>
                            </td>
                            <td><?= $r['likelihood'] ?></td>
                            <td><?= $r['impact'] ?></td>
                            <td class="fw-bold"><?= $r['score'] ?></td>
                            <td><?= statusBadge($r['priority']) ?></td>
                            <td><?= statusBadge($r['status']) ?></td>
                            <td><?= htmlspecialchars($r['owner'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($risks)): ?>
                        <tr><td colspan="7" class="text-muted">No risks recorded yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 small text-muted">
                <strong>Scoring:</strong> Critical ≥16 &nbsp;|&nbsp; High 10-15 &nbsp;|&nbsp; Medium 5-9 &nbsp;|&nbsp; Low 1-4
            </div>
        </div>
    </div>

    <!-- PDPC / Risk Responses -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold d-flex justify-content-between">
            <span>Process Decision Program Chart (PDPC) – Risk Responses</span>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addRespModal">Add Response</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Risk</th><th>Response Type</th><th>Action</th><th>Responsible</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $resp): ?>
                        <tr>
                            <td><code><?= $resp['risk_code'] ?></code> <?= htmlspecialchars($resp['risk_title']) ?></td>
                            <td><span class="badge bg-secondary"><?= $resp['response_type'] ?></span></td>
                            <td><?= htmlspecialchars($resp['action_description']) ?></td>
                            <td><?= htmlspecialchars($resp['responsible'] ?? '-') ?></td>
                            <td><?= statusBadge($resp['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($responses)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No risk responses defined yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Risk Modal -->
<div class="modal fade" id="addRiskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Identify New Risk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="e.g. Technical, Schedule, Resource"></div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Likelihood (1-5)</label>
                            <select name="likelihood" class="form-select"><?php for($i=1;$i<=5;$i++) echo "<option value='$i'>$i</option>"; ?></select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Impact (1-5)</label>
                            <select name="impact" class="form-select"><?php for($i=1;$i<=5;$i++) echo "<option value='$i'>$i</option>"; ?></select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner</label>
                        <select name="owner_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="add_risk" class="btn btn-primary">Add Risk</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Add Response Modal -->
<div class="modal fade" id="addRespModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Add Risk Response (PDPC)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Risk</label>
                        <select name="risk_id" class="form-select" required>
                            <?php foreach ($risks as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= $r['risk_code'] ?> – <?= htmlspecialchars($r['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Response Type</label>
                        <select name="response_type" class="form-select">
                            <option>Preventive</option><option>Corrective</option><option>Contingency</option><option>Transfer</option><option>Accept</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Action Description</label><textarea name="action_description" class="form-control" rows="3" required></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Responsible</label>
                        <select name="responsible_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="add_response" class="btn btn-primary">Add Response</button></div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
