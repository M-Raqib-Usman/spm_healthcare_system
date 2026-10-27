<?php
require_once 'db.php';

/**
 * Get the single project (we only have one main project in this system)
 */
function getProject($pdo, $projectId = 1) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    return $stmt->fetch();
}

/**
 * Calculate Earned Value Management metrics
 */
function calculateEVM($pdo, $projectId = 1) {
    // Planned Value (PV) = sum of planned_cost of all activities that should have started by today
    // For simplicity & accuracy we use: Budget at Completion * % of planned duration elapsed
    // Better practical approach used in this system:

    $project = getProject($pdo, $projectId);
    $today = getEvaluationDate($project);

    // Total Budget at Completion (BAC)
    $bac = (float)$project['current_budget'];

    // Planned Value: sum of planned costs of activities whose planned_end <= today
    // + partial for in-progress according to planned duration
    $stmt = $pdo->prepare("
        SELECT 
            SUM(
                CASE 
                    WHEN planned_end <= ? THEN planned_cost
                    WHEN planned_start > ? THEN 0
                    ELSE planned_cost * (
                        DATEDIFF(?, planned_start) / NULLIF(DATEDIFF(planned_end, planned_start), 0)
                    )
                END
            ) AS pv
        FROM activities 
        WHERE project_id = ?
    ");
    $stmt->execute([$today, $today, $today, $projectId]);
    $pv = (float)($stmt->fetch()['pv'] ?? 0);

    // Earned Value (EV) = sum of (planned_cost * percent_complete / 100)
    $stmt = $pdo->prepare("
        SELECT SUM(planned_cost * percent_complete / 100) AS ev 
        FROM activities 
        WHERE project_id = ?
    ");
    $stmt->execute([$projectId]);
    $ev = (float)($stmt->fetch()['ev'] ?? 0);

    // Actual Cost (AC)
    $stmt = $pdo->prepare("SELECT SUM(actual_cost) AS ac FROM activities WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $ev_actual = $stmt->fetch()['ac'] ?? 0;
    $ac = (float)$ev_actual;

    // Performance Indices
    $cpi = ($ac > 0) ? $ev / $ac : 1;
    $spi = ($pv > 0) ? $ev / $pv : 1;

    // Variances
    $cv = $ev - $ac;   // Cost Variance
    $sv = $ev - $pv;   // Schedule Variance

    // Estimate at Completion (EAC) - simple CPI method
    $eac = ($cpi > 0) ? $bac / $cpi : $bac;

    // Estimate to Complete
    $etc = $eac - $ac;

    // Variance at Completion
    $vac = $bac - $eac;

    return [
        'bac' => round($bac, 2),
        'pv'  => round($pv, 2),
        'ev'  => round($ev, 2),
        'ac'  => round($ac, 2),
        'cpi' => round($cpi, 4),
        'spi' => round($spi, 4),
        'cv'  => round($cv, 2),
        'sv'  => round($sv, 2),
        'eac' => round($eac, 2),
        'etc' => round($etc, 2),
        'vac' => round($vac, 2),
        'evaluation_date' => $today,
        'cpi_status' => ($cpi < CPI_THRESHOLD) ? 'warning' : 'good',
        'spi_status' => ($spi < SPI_THRESHOLD) ? 'warning' : 'good'
    ];
}

/**
 * Get active evaluation date (uses system date, or execution date during demo if run before project start)
 */
function getEvaluationDate($project) {
    $today = date('Y-m-d');
    if (!empty($project['start_date']) && $today < $project['start_date']) {
        return '2026-12-22'; // Project execution snapshot matching case study testing phase
    }
    return $today;
}

/**
 * Get overall project progress (% complete based on earned value vs BAC)
 */
function getOverallProgress($pdo, $projectId = 1) {
    $evm = calculateEVM($pdo, $projectId);
    if ($evm['bac'] == 0) return 0;
    return min(100, round(($evm['ev'] / $evm['bac']) * 100, 1));
}

/**
 * Count delayed activities
 */
function getDelayedActivities($pdo, $projectId = 1) {
    $project = getProject($pdo, $projectId);
    $today = getEvaluationDate($project);
    $stmt = $pdo->prepare("
        SELECT * FROM activities 
        WHERE project_id = ? 
          AND status NOT IN ('Completed') 
          AND planned_end < ?
        ORDER BY planned_end
    ");
    $stmt->execute([$projectId, $today]);
    return $stmt->fetchAll();
}

/**
 * Calculate impact of delayed activities on dependent downstream activities and final deadline
 */
function calculateDelayImpact($pdo, $projectId = 1) {
    $project = getProject($pdo, $projectId);
    $delayed = getDelayedActivities($pdo, $projectId);
    $evalDate = getEvaluationDate($project);
    $impactList = [];

    foreach ($delayed as $act) {
        $delayDays = max(1, (int)((strtotime($evalDate) - strtotime($act['planned_end'])) / 86400));
        
        $stmt = $pdo->prepare("
            SELECT a.id, a.activity_code, a.name, a.planned_start, a.planned_end, ad.dependency_type, ad.lag_days
            FROM activity_dependencies ad
            JOIN activities a ON ad.activity_id = a.id
            WHERE ad.predecessor_id = ?
        ");
        $stmt->execute([$act['id']]);
        $dependents = $stmt->fetchAll();

        $impactList[] = [
            'activity' => $act,
            'delay_days' => $delayDays,
            'dependents' => $dependents,
            'projected_delay_days' => $delayDays
        ];
    }
    return $impactList;
}

/**
 * Apply approved Change Request impact to project
 */
function applyChangeRequestImpact($pdo, $crId) {
    $stmt = $pdo->prepare("SELECT * FROM change_requests WHERE id = ? AND status = 'Approved'");
    $stmt->execute([$crId]);
    $cr = $stmt->fetch();
    if (!$cr) return false;

    // Update project budget and end date
    $stmt = $pdo->prepare("
        UPDATE projects 
        SET current_budget = current_budget + ?,
            planned_end_date = DATE_ADD(planned_end_date, INTERVAL ? DAY)
        WHERE id = ?
    ");
    $stmt->execute([
        $cr['cost_impact'],
        $cr['schedule_impact_days'],
        $cr['project_id']
    ]);

    // Mark as Implemented
    $stmt = $pdo->prepare("UPDATE change_requests SET status = 'Implemented', implemented_at = NOW() WHERE id = ?");
    $stmt->execute([$crId]);

    return true;
}

/**
 * Format currency in PKR
 */
function formatPKR($amount) {
    return 'PKR ' . number_format($amount, 0);
}

/**
 * Status badge helper
 */
function statusBadge($status) {
    $colors = [
        'Approved' => 'success', 'Completed' => 'success', 'Achieved' => 'success', 'Passed' => 'success', 'Closed' => 'success', 'Resolved' => 'success',
        'In Progress' => 'primary', 'Executing' => 'primary', 'Under Review' => 'info', 'Assessed' => 'info', 'Mitigating' => 'info',
        'Open' => 'warning', 'Pending' => 'warning', 'Proposed' => 'warning', 'Submitted' => 'warning', 'Not Started' => 'secondary',
        'Delayed' => 'danger', 'Critical' => 'danger', 'Failed' => 'danger', 'Rejected' => 'danger', 'Missed' => 'danger',
        'High' => 'danger', 'Medium' => 'warning', 'Low' => 'secondary'
    ];
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($status) . '</span>';
}

/**
 * Simple login check
 */

