-- =====================================================
-- Sample Data - Smart Healthcare Project Management
-- Realistic data based on the case study
-- =====================================================

USE spm_healthcare;

-- -----------------------------------------------------
-- Team Members (as per case study)
-- -----------------------------------------------------
INSERT INTO users (name, email, password, role, phone) VALUES
('Ahmed Khan', 'ahmed.khan@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Project Manager', '0300-1112233'), -- password: password
('Sara Ali', 'sara.ali@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Backend Developer', '0301-2223344'),
('Usman Raza', 'usman.raza@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Backend Developer', '0302-3334455'),
('Fatima Zahra', 'fatima.zahra@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Frontend Developer', '0303-4445566'),
('Hassan Iqbal', 'hassan.iqbal@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Frontend Developer', '0304-5556677'),
('Ayesha Noor', 'ayesha.noor@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Database Developer', '0305-6667788'),
('Bilal Ahmed', 'bilal.ahmed@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'QA Engineer', '0306-7778899');

-- -----------------------------------------------------
-- Main Project
-- -----------------------------------------------------
INSERT INTO projects (name, description, start_date, planned_end_date, approved_budget, current_budget, status) VALUES
('Smart Healthcare Project Management System',
 'Development of a comprehensive system for a network of five hospitals covering patient appointments, laboratory reports, doctor schedules, online consultations, and hospital billing.',
 '2026-11-01', '2027-02-20', 5000000.00, 5000000.00, 'Executing');

-- -----------------------------------------------------
-- Project Charter
-- -----------------------------------------------------
INSERT INTO project_charter (project_id, objectives, stakeholders, scope_boundaries, assumptions, constraints, success_criteria) VALUES
(1,
 '1. Deliver a fully functional Smart Healthcare system for 5 hospitals within 16 weeks.\n2. Manage appointments, lab reports, doctor schedules, online consultations and billing.\n3. Ensure high quality, secure and scalable solution.\n4. Stay within approved budget of PKR 5,000,000.',
 '• Hospital Management (Client)\n• Project Manager (Ahmed Khan)\n• Development Team (2 Backend, 2 Frontend, 1 DB, 1 QA)\n• End Users: Doctors, Nurses, Admin Staff, Patients',
 'In Scope: Appointment management, Lab reports, Doctor schedules, Online consultations, Hospital billing, User authentication & roles.\nOut of Scope (initially): AI diagnosis, Native mobile apps, Integration with external insurance systems, Hardware installation.',
 '• Hospitals will provide necessary data and access in time.\n• Team will remain stable throughout the project.\n• No major technology stack changes.',
 '• Hard deadline of 16 weeks.\n• Fixed budget of PKR 5,000,000.\n• Must work on existing hospital IT infrastructure.',
 '• All core modules delivered and accepted by client.\n• Zero critical defects at go-live.\n• Budget variance ≤ 5%.\n• Schedule variance ≤ 1 week.');

-- -----------------------------------------------------
-- Project Management Plan (summary)
-- -----------------------------------------------------
INSERT INTO project_plans (project_id, scope_summary, schedule_summary, cost_summary, quality_summary, risk_summary, communication_plan) VALUES
(1,
 'Core modules: Appointments, Lab Reports, Doctor Schedules, Online Consultations, Billing. Later change requests for AI Diagnosis and Mobile App were approved.',
 'Original duration: 16 weeks (01-Nov-2026 to 20-Feb-2027). After approved CRs: extended by 3 weeks.',
 'Approved budget: PKR 5,000,000. After AI + Mobile CR: increased by PKR 800,000 → Current budget PKR 5,800,000.',
 'Defect tracking, quality checklists for each major deliverable, weekly QA reviews, final UAT.',
 'Key risks: Scope creep, schedule delay due to new requirements, critical defects in core modules, resource unavailability.',
 'Weekly status meetings, daily stand-ups, bi-weekly client demos, monthly steering committee.');

-- -----------------------------------------------------
-- Requirements (original + later ones)
-- -----------------------------------------------------
INSERT INTO requirements (project_id, req_code, title, description, priority, status, source, created_by, approved_by, approved_at) VALUES
(1, 'REQ-001', 'Patient Appointment Management', 'Allow patients and staff to book, reschedule and cancel appointments across 5 hospitals.', 'High', 'Approved', 'Client', 1, 1, '2026-10-15 10:00:00'),
(1, 'REQ-002', 'Laboratory Reports Module', 'Upload, view and manage laboratory test reports with doctor access.', 'High', 'Approved', 'Client', 1, 1, '2026-10-15 10:05:00'),
(1, 'REQ-003', 'Doctor Schedule Management', 'Manage doctor availability, shifts and leave across hospitals.', 'High', 'Approved', 'Client', 1, 1, '2026-10-15 10:10:00'),
(1, 'REQ-004', 'Online Consultation', 'Video/audio consultation feature between doctor and patient.', 'High', 'Approved', 'Client', 1, 1, '2026-10-15 10:15:00'),
(1, 'REQ-005', 'Hospital Billing System', 'Generate invoices, track payments, insurance claims support.', 'High', 'Approved', 'Client', 1, 1, '2026-10-15 10:20:00'),
(1, 'REQ-006', 'User Authentication & Roles', 'Secure login with role-based access (Admin, Doctor, Nurse, Patient, Lab).', 'High', 'Approved', 'Internal', 1, 1, '2026-10-15 10:25:00'),
(1, 'REQ-007', 'AI-based Preliminary Diagnosis', 'AI module that suggests possible diagnoses based on symptoms and history.', 'High', 'Approved', 'Client Change Request', 1, 1, '2026-12-05 14:00:00'),
(1, 'REQ-008', 'Mobile Application (Android/iOS)', 'Native or hybrid mobile app for patients and doctors.', 'High', 'Approved', 'Client Change Request', 1, 1, '2026-12-05 14:05:00'),
(1, 'REQ-009', 'Multi-language Support', 'Support Urdu and English interfaces.', 'Medium', 'Proposed', 'Client', 1, NULL, NULL);

-- -----------------------------------------------------
-- Work Breakdown Structure
-- -----------------------------------------------------
INSERT INTO wbs (project_id, parent_id, wbs_code, name, description, level, type) VALUES
(1, NULL, '1', 'Smart Healthcare System', 'Root', 1, 'Deliverable'),
(1, 1, '1.1', 'Project Management', 'Initiation, Planning, Monitoring, Closure', 2, 'Work Package'),
(1, 1, '1.2', 'Requirements & Design', 'Gathering, Analysis, Architecture, UI/UX', 2, 'Work Package'),
(1, 1, '1.3', 'Backend Development', 'APIs, Business Logic, Integrations', 2, 'Work Package'),
(1, 1, '1.4', 'Frontend Development', 'Web UI + later Mobile', 2, 'Work Package'),
(1, 1, '1.5', 'Database Design & Development', 'Schema, Optimization, Migrations', 2, 'Work Package'),
(1, 1, '1.6', 'Quality Assurance', 'Testing, Defect Management, UAT', 2, 'Work Package'),
(1, 1, '1.7', 'Deployment & Training', 'Go-live, Documentation, Training', 2, 'Work Package');

-- Sub-items under Backend
INSERT INTO wbs (project_id, parent_id, wbs_code, name, description, level, type) VALUES
(1, 4, '1.3.1', 'Appointment API', '', 3, 'Work Package'),
(1, 4, '1.3.2', 'Lab Reports API', '', 3, 'Work Package'),
(1, 4, '1.3.3', 'Doctor Schedule API', '', 3, 'Work Package'),
(1, 4, '1.3.4', 'Consultation Service', '', 3, 'Work Package'),
(1, 4, '1.3.5', 'Billing Engine', '', 3, 'Work Package'),
(1, 4, '1.3.6', 'AI Diagnosis Module', 'Added via CR', 3, 'Work Package');

-- -----------------------------------------------------
-- Activities (realistic schedule)
-- -----------------------------------------------------
INSERT INTO activities (project_id, wbs_id, activity_code, name, description, assigned_to, planned_start, planned_end, duration_days, percent_complete, status, is_milestone, planned_cost, actual_cost) VALUES
-- Initiation & Planning
(1, 2, 'ACT-001', 'Project Kick-off Meeting', 'Kick-off with client and team', 1, '2026-11-01', '2026-11-01', 1, 100, 'Completed', 1, 25000, 25000),
(1, 2, 'ACT-002', 'Finalize Project Charter & Plan', 'Complete charter and management plan', 1, '2026-11-02', '2026-11-05', 4, 100, 'Completed', 0, 80000, 78000),
(1, 3, 'ACT-003', 'Requirements Gathering Workshops', 'Workshops with hospital staff', 1, '2026-11-06', '2026-11-12', 5, 100, 'Completed', 0, 120000, 115000),
(1, 3, 'ACT-004', 'System Architecture Design', 'High-level and detailed design', 2, '2026-11-13', '2026-11-20', 6, 100, 'Completed', 0, 150000, 148000),
(1, 3, 'ACT-005', 'UI/UX Wireframes & Prototypes', 'Design screens for all modules', 4, '2026-11-15', '2026-11-25', 8, 100, 'Completed', 0, 180000, 175000),

-- Core Development
(1, 6, 'ACT-006', 'Database Schema Design', 'Complete ERD and schema', 6, '2026-11-21', '2026-11-28', 6, 100, 'Completed', 0, 90000, 88000),
(1, 4, 'ACT-007', 'Appointment Module Backend', 'APIs for appointment management', 2, '2026-11-29', '2026-12-10', 10, 100, 'Completed', 0, 280000, 275000),
(1, 4, 'ACT-008', 'Lab Reports Module Backend', 'APIs for lab reports', 3, '2026-12-01', '2026-12-12', 10, 90, 'In Progress', 0, 260000, 240000),
(1, 4, 'ACT-009', 'Doctor Schedule Backend', 'Schedule management APIs', 2, '2026-12-08', '2026-12-18', 9, 70, 'In Progress', 0, 220000, 160000),
(1, 5, 'ACT-010', 'Frontend - Appointment Screens', 'Web UI for appointments', 4, '2026-12-05', '2026-12-18', 10, 80, 'In Progress', 0, 200000, 165000),
(1, 5, 'ACT-011', 'Frontend - Lab & Schedule Screens', 'UI for lab and doctor schedules', 5, '2026-12-10', '2026-12-22', 10, 50, 'In Progress', 0, 210000, 110000),

-- Change Request related (AI + Mobile) - later
(1, 9, 'ACT-012', 'AI Diagnosis Module Development', 'Develop and integrate AI preliminary diagnosis', 2, '2026-12-20', '2027-01-15', 18, 20, 'In Progress', 0, 450000, 95000),
(1, 5, 'ACT-013', 'Mobile App Development', 'Android + iOS patient/doctor app', 4, '2026-12-22', '2027-01-25', 25, 10, 'In Progress', 0, 550000, 60000),

-- Testing & Closure
(1, 7, 'ACT-014', 'Integration Testing', 'Full system integration testing', 7, '2027-01-20', '2027-02-05', 12, 0, 'Not Started', 0, 180000, 0),
(1, 7, 'ACT-015', 'User Acceptance Testing (UAT)', 'Client UAT across hospitals', 7, '2027-02-06', '2027-02-15', 8, 0, 'Not Started', 0, 120000, 0),
(1, 8, 'ACT-016', 'Deployment & Go-Live', 'Production deployment and training', 1, '2027-02-16', '2027-02-20', 5, 0, 'Not Started', 1, 150000, 0),
(1, 2, 'ACT-017', 'Project Closure & Lessons Learned', 'Final documentation and closure', 1, '2027-02-21', '2027-02-25', 4, 0, 'Not Started', 1, 50000, 0);

-- Dependencies (simple FS chain)
INSERT INTO activity_dependencies (activity_id, predecessor_id, dependency_type, lag_days) VALUES
(2, 1, 'FS', 0),
(3, 2, 'FS', 0),
(4, 3, 'FS', 0),
(5, 3, 'FS', 0),
(6, 4, 'FS', 0),
(7, 6, 'FS', 0),
(8, 6, 'FS', 0),
(9, 6, 'FS', 0),
(10, 7, 'FS', 0),
(11, 8, 'FS', 0),
(12, 9, 'FS', 5),
(13, 10, 'FS', 0),
(14, 12, 'FS', 0),
(14, 13, 'FS', 0),
(15, 14, 'FS', 0),
(16, 15, 'FS', 0),
(17, 16, 'FS', 0);

-- -----------------------------------------------------
-- Change Requests (the critical ones from case study)
-- -----------------------------------------------------
INSERT INTO change_requests (project_id, cr_code, title, description, justification, cost_impact, schedule_impact_days, status, requested_by, reviewed_by, reviewed_at, implemented_at) VALUES
(1, 'CR-001', 'Add AI-based Preliminary Diagnosis Module',
 'Hospital management requested an AI module that analyzes patient symptoms and history to suggest possible diagnoses before doctor consultation.',
 'Improves doctor efficiency and patient experience. Competitive advantage for the hospital network.',
 450000.00, 18, 'Implemented', 1, 1, '2026-12-05 14:00:00', '2026-12-06 09:00:00'),
(1, 'CR-002', 'Develop Mobile Application',
 'Native/hybrid mobile application for patients (book appointments, view reports, consultations) and doctors (schedules, consultations).',
 'Majority of users prefer mobile access. Essential for modern healthcare delivery.',
 350000.00, 15, 'Implemented', 1, 1, '2026-12-05 14:10:00', '2026-12-06 09:30:00'),
(1, 'CR-003', 'Multi-language Support (Urdu)',
 'Add full Urdu language support for all user interfaces.',
 'Large portion of patients and staff prefer Urdu.',
 120000.00, 7, 'Under Review', 1, NULL, NULL, NULL);

-- After approving CR-001 + CR-002 the budget and schedule should be updated (we will handle this in application logic)
-- For sample data we already increased current_budget conceptually; we will update it via the system.

-- -----------------------------------------------------
-- Defects (critical ones mentioned in case study)
-- -----------------------------------------------------
INSERT INTO defects (project_id, defect_code, title, description, severity, status, assigned_to, reported_by, reported_at) VALUES
(1, 'DEF-001', 'Critical: Appointment Double-Booking',
 'System allows two patients to book the same doctor slot at the same time under concurrent requests.',
 'Critical', 'Open', 2, 7, '2026-12-18 11:30:00'),
(1, 'DEF-002', 'Critical: Patient Record Data Loss',
 'In rare cases when saving patient history, some fields are not persisted correctly.',
 'Critical', 'In Progress', 6, 7, '2026-12-19 09:15:00'),
(1, 'DEF-003', 'High: Lab Report PDF Generation Fails',
 'PDF generation for lab reports fails when report contains special characters.',
 'High', 'Open', 3, 7, '2026-12-20 14:00:00'),
(1, 'DEF-004', 'Medium: Doctor Schedule Calendar UI lag',
 'Calendar view becomes slow when loading more than 3 months of data.',
 'Medium', 'Open', 5, 7, '2026-12-21 16:45:00'),
(1, 'DEF-005', 'Low: Typo in Billing Invoice Footer',
 'Minor spelling mistake in the generated invoice footer.',
 'Low', 'Resolved', 4, 7, '2026-12-15 10:00:00');

-- -----------------------------------------------------
-- Quality Checklists
-- -----------------------------------------------------
INSERT INTO quality_checklists (project_id, checklist_name, deliverable) VALUES
(1, 'Appointment Module Quality Checklist', 'Appointment Management Module'),
(1, 'Database Quality Checklist', 'Database Schema & Performance'),
(1, 'Security & Access Control Checklist', 'Authentication & Authorization');

INSERT INTO quality_checklist_items (checklist_id, item_text, status, inspected_by, inspected_at, comments) VALUES
(1, 'Appointment booking works for all 5 hospitals', 'Passed', 7, '2026-12-12 15:00:00', 'Verified'),
(1, 'Reschedule and cancel functions correctly', 'Passed', 7, '2026-12-12 15:10:00', NULL),
(1, 'Double-booking prevention works under load', 'Failed', 7, '2026-12-18 11:00:00', 'Critical defect DEF-001 found'),
(1, 'Email/SMS notifications sent correctly', 'Passed', 7, '2026-12-12 15:30:00', NULL),
(2, 'All foreign keys and constraints present', 'Passed', 6, '2026-11-28 17:00:00', NULL),
(2, 'Indexes created on frequently queried columns', 'Passed', 6, '2026-11-28 17:15:00', NULL),
(2, 'Backup and recovery procedure tested', 'Pending', NULL, NULL, NULL),
(3, 'Password hashing using strong algorithm', 'Passed', 7, '2026-12-01 10:00:00', 'bcrypt used'),
(3, 'Role-based access control enforced', 'Passed', 7, '2026-12-01 10:20:00', NULL),
(3, 'Session timeout implemented', 'Passed', 7, '2026-12-01 10:30:00', NULL);

-- -----------------------------------------------------
-- Risks
-- -----------------------------------------------------
INSERT INTO risks (project_id, risk_code, title, description, category, likelihood, impact, priority, status, owner_id) VALUES
(1, 'RSK-001', 'Scope Creep from additional client requests', 'Client may keep adding new features beyond AI and Mobile.', 'Scope', 4, 5, 'Critical', 'Mitigating', 1),
(1, 'RSK-002', 'Schedule overrun due to approved change requests', 'AI + Mobile already added 3 weeks. Further delays possible.', 'Schedule', 4, 4, 'High', 'Mitigating', 1),
(1, 'RSK-003', 'Critical defects in core modules delaying UAT', 'Appointment and Patient Record defects are critical.', 'Quality', 3, 5, 'High', 'Assessed', 7),
(1, 'RSK-004', 'Key team member unavailability', 'Illness or resignation of backend or DB developer.', 'Resource', 2, 4, 'Medium', 'Identified', 1),
(1, 'RSK-005', 'Performance issues under multi-hospital load', 'System may slow down with concurrent users from 5 hospitals.', 'Technical', 3, 3, 'Medium', 'Identified', 2);

-- Risk Responses (PDPC style)
INSERT INTO risk_responses (risk_id, response_type, action_description, responsible_id, status) VALUES
(1, 'Preventive', 'Strict change control board. All new requests go through formal CR process with impact analysis.', 1, 'In Progress'),
(1, 'Corrective', 'If approved, re-baseline schedule and budget immediately and communicate to stakeholders.', 1, 'Planned'),
(2, 'Preventive', 'Buffer of 5 working days already included. Daily progress tracking on critical path.', 1, 'In Progress'),
(2, 'Contingency', 'If delay exceeds 5 days, request overtime or temporary additional resource.', 1, 'Planned'),
(3, 'Corrective', 'Immediate high-priority fix for DEF-001 and DEF-002. Daily defect triage meetings.', 7, 'In Progress'),
(3, 'Preventive', 'Increase unit and integration test coverage for appointment and patient modules.', 7, 'Planned'),
(4, 'Preventive', 'Cross-training of team members on critical modules.', 1, 'Planned'),
(5, 'Preventive', 'Load testing planned in Integration Testing phase with simulated 5-hospital concurrent users.', 2, 'Planned');

-- -----------------------------------------------------
-- Milestones
-- -----------------------------------------------------
INSERT INTO milestones (project_id, name, planned_date, actual_date, status, description) VALUES
(1, 'Project Kick-off', '2026-11-01', '2026-11-01', 'Achieved', 'Official start'),
(1, 'Requirements Sign-off', '2026-11-12', '2026-11-12', 'Achieved', 'Original requirements approved'),
(1, 'Design Complete', '2026-11-25', '2026-11-25', 'Achieved', 'Architecture + UI approved'),
(1, 'Core Modules Development Complete', '2026-12-20', NULL, 'Pending', 'Appointments, Lab, Schedule, Billing'),
(1, 'AI + Mobile Modules Complete', '2027-01-25', NULL, 'Pending', 'After approved CRs'),
(1, 'UAT Complete', '2027-02-15', NULL, 'Pending', 'Client acceptance'),
(1, 'Go-Live', '2027-02-20', NULL, 'Pending', 'Production deployment');

-- -----------------------------------------------------
-- Update project current budget after approved CRs (450k + 350k = 800k)
-- -----------------------------------------------------
UPDATE projects SET current_budget = 5800000.00, planned_end_date = '2027-03-13' WHERE id = 1;
-- Note: Original end was 20-Feb. +3 weeks ≈ 13-Mar-2027.