<?php
require_once __DIR__ . '/functions.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Healthcare - Project Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-hospital"></i> Smart Healthcare SPM
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage=='index.php'?'active':'' ?>" href="<?= BASE_URL ?>index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-folder2-open"></i> Initiation
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/charter.php">Project Charter</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/plan.php">Project Plan</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/milestones.php">Milestones & Status</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-diagram-3"></i> Scope
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/requirements.php">Requirements</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/wbs.php">Work Breakdown Structure</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/change_requests.php">Change Requests</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage=='schedule.php'?'active':'' ?>" href="<?= BASE_URL ?>modules/schedule.php">
                        <i class="bi bi-calendar-week"></i> Schedule
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage=='cost.php'?'active':'' ?>" href="<?= BASE_URL ?>modules/cost.php">
                        <i class="bi bi-currency-dollar"></i> Cost & EVM
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-check"></i> Quality & Risk
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/defects.php">Defect Tracking</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/quality.php">Quality Checklists</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/risks.php">Risks & PDPC</a></li>
                    </ul>
                </li>
            </ul>
            <span class="navbar-text text-white">
                <i class="bi bi-person-badge"></i> Project Manager
            </span>
        </div>
    </div>
</nav>
<main>
