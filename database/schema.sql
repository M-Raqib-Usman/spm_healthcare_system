-- =====================================================
-- Smart Healthcare Project Management System
-- Database Schema - MySQL
-- Case Study: 5 Hospitals Network - 16 weeks, PKR 5,000,000
-- =====================================================

CREATE DATABASE IF NOT EXISTS spm_healthcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spm_healthcare;

-- -----------------------------------------------------
-- 1. Users / Team Members
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Project Manager','Backend Developer','Frontend Developer','Database Developer','QA Engineer') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- 2. Projects (main project table)
-- -----------------------------------------------------
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    planned_end_date DATE NOT NULL,
    actual_end_date DATE NULL,
    approved_budget DECIMAL(15,2) NOT NULL DEFAULT 5000000.00,
    current_budget DECIMAL(15,2) NOT NULL DEFAULT 5000000.00,
    status ENUM('Initiated','Planning','Executing','Monitoring','Closing','Closed') DEFAULT 'Initiated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- 3. Project Charter
-- -----------------------------------------------------
CREATE TABLE project_charter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    objectives TEXT NOT NULL,
    stakeholders TEXT NOT NULL,
    scope_boundaries TEXT NOT NULL,
    assumptions TEXT,
    constraints TEXT,
    success_criteria TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- 4. Project Management Plan
-- -----------------------------------------------------
CREATE TABLE project_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    scope_summary TEXT,
    schedule_summary TEXT,
    cost_summary TEXT,
    quality_summary TEXT,
    risk_summary TEXT,
    communication_plan TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- 5. Requirements
-- -----------------------------------------------------
CREATE TABLE requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    req_code VARCHAR(20) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('High','Medium','Low') DEFAULT 'Medium',
    status ENUM('Proposed','Approved','Rejected','Deferred') DEFAULT 'Proposed',
    source VARCHAR(100),
    created_by INT,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- -----------------------------------------------------
-- 6. Work Breakdown Structure (WBS)
-- -----------------------------------------------------
CREATE TABLE wbs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    parent_id INT NULL,
    wbs_code VARCHAR(50) NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    level INT DEFAULT 1,
    type ENUM('Deliverable','Work Package','Activity') DEFAULT 'Work Package',
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES wbs(id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- 7. Activities / Tasks (for Schedule)
-- -----------------------------------------------------
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    wbs_id INT NULL,
    activity_code VARCHAR(20) NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT NULL,
    planned_start DATE,
    planned_end DATE,
    actual_start DATE NULL,
    actual_end DATE NULL,
    duration_days INT NOT NULL DEFAULT 1,
    percent_complete DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('Not Started','In Progress','Completed','Delayed','On Hold') DEFAULT 'Not Started',
    is_milestone TINYINT(1) DEFAULT 0,
    planned_cost DECIMAL(12,2) DEFAULT 0.00,
    actual_cost DECIMAL(12,2) DEFAULT 0.00,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (wbs_id) REFERENCES wbs(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- 8. Activity Dependencies
-- -----------------------------------------------------
CREATE TABLE activity_dependencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    predecessor_id INT NOT NULL,
    dependency_type ENUM('FS','SS','FF','SF') DEFAULT 'FS', -- Finish-to-Start etc.
    lag_days INT DEFAULT 0,
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY (predecessor_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- 9. Change Requests
-- -----------------------------------------------------
CREATE TABLE change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    cr_code VARCHAR(20) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    justification TEXT,
    cost_impact DECIMAL(12,2) DEFAULT 0.00,
    schedule_impact_days INT DEFAULT 0,
    status ENUM('Submitted','Under Review','Approved','Rejected','Implemented') DEFAULT 'Submitted',
    requested_by INT,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    implemented_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- -----------------------------------------------------
-- 10. Defects (Quality)
-- -----------------------------------------------------
CREATE TABLE defects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    defect_code VARCHAR(20) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    severity ENUM('Critical','High','Medium','Low') DEFAULT 'Medium',
    status ENUM('Open','In Progress','Resolved','Closed','Reopened') DEFAULT 'Open',
    assigned_to INT NULL,
    reported_by INT,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolution_notes TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- -----------------------------------------------------
-- 11. Quality Checklists
-- -----------------------------------------------------
CREATE TABLE quality_checklists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    checklist_name VARCHAR(150) NOT NULL,
    deliverable VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE quality_checklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checklist_id INT NOT NULL,
    item_text VARCHAR(300) NOT NULL,
    status ENUM('Pending','Passed','Failed','N/A') DEFAULT 'Pending',
    inspected_by INT NULL,
    inspected_at DATETIME NULL,
    comments TEXT,
    FOREIGN KEY (checklist_id) REFERENCES quality_checklists(id) ON DELETE CASCADE,
    FOREIGN KEY (inspected_by) REFERENCES users(id)
);

-- -----------------------------------------------------
-- 12. Risks
-- -----------------------------------------------------
CREATE TABLE risks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    risk_code VARCHAR(20) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    likelihood INT NOT NULL DEFAULT 3,   -- 1-5
    impact INT NOT NULL DEFAULT 3,       -- 1-5
    score INT GENERATED ALWAYS AS (likelihood * impact) STORED,
    priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    status ENUM('Identified','Assessed','Mitigating','Closed','Occurred') DEFAULT 'Identified',
    owner_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- -----------------------------------------------------
-- 13. Risk Responses (for PDPC style)
-- -----------------------------------------------------
CREATE TABLE risk_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    risk_id INT NOT NULL,
    response_type ENUM('Preventive','Corrective','Contingency','Transfer','Accept') NOT NULL,
    action_description TEXT NOT NULL,
    responsible_id INT NULL,
    status ENUM('Planned','In Progress','Completed') DEFAULT 'Planned',
    FOREIGN KEY (risk_id) REFERENCES risks(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_id) REFERENCES users(id)
);

-- -----------------------------------------------------
-- 14. Project Milestones (extra tracking)
-- -----------------------------------------------------
CREATE TABLE milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    planned_date DATE NOT NULL,
    actual_date DATE NULL,
    status ENUM('Pending','Achieved','Missed') DEFAULT 'Pending',
    description TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- 15. EVM Snapshots (optional historical tracking)
-- -----------------------------------------------------
CREATE TABLE evm_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    snapshot_date DATE NOT NULL,
    planned_value DECIMAL(15,2) NOT NULL,
    earned_value DECIMAL(15,2) NOT NULL,
    actual_cost DECIMAL(15,2) NOT NULL,
    cpi DECIMAL(8,4),
    spi DECIMAL(8,4),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Indexes for performance
-- -----------------------------------------------------
CREATE INDEX idx_activities_project ON activities(project_id);
CREATE INDEX idx_requirements_status ON requirements(status);
CREATE INDEX idx_change_requests_status ON change_requests(status);
CREATE INDEX idx_defects_severity ON defects(severity, status);
CREATE INDEX idx_risks_score ON risks(score);