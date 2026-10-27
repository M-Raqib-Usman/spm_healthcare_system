# Smart Healthcare Project Management System
## CSE-494 Software Project Management - Mid-term Project

### How to Run (XAMPP / WAMP / Laragon)

1. **Copy the folder**
   - Copy the entire `spm_system` folder into your web server root:
     - XAMPP: `C:\xampp\htdocs\`
     - WAMP: `C:\wamp64\www\`
     - Laragon: `C:\laragon\www\`

2. **Create the Database**
   - Open **phpMyAdmin** → http://localhost/phpmyadmin
   - Click **Import**
   - First import: `database/schema.sql`
   - Then import: `database/sample_data.sql`

3. **Configure Database (if needed)**
   - Open `includes/config.php`
   - Default settings:
     ```php
     DB_HOST = 'localhost'
     DB_USER = 'root'
     DB_PASS = ''          // change if you have a password
     DB_NAME = 'spm_healthcare'
     BASE_URL = '/spm_system/'
     ```

4. **Open the Application**
   - Go to: **http://localhost/spm_system/**

### Features Implemented

| Module | Features |
|--------|----------|
| **Dashboard** | Overall progress, EVM (CPI/SPI), delayed tasks, critical defects, pending CRs |
| **Project Charter** | Objectives, stakeholders, scope, budget, deadline |
| **Project Plan** | Scope, schedule, cost, quality, risk, communication summaries |
| **Requirements** | Add, prioritize, approve/reject (only approved enter scope) |
| **WBS** | Hierarchical Work Breakdown Structure |
| **Change Requests** | Submit, approve, reject, **implement** (auto-updates budget & schedule) |
| **Schedule** | Activities, assignments, Gantt-style timeline, delay detection |
| **Cost & EVM** | PV, EV, AC, CPI, SPI, CV, SV, EAC, VAC + automatic warnings |
| **Defects** | Track defects with severity, status, assignment |
| **Quality** | Checklists + inspection results |
| **Risks & PDPC** | Prioritization Matrix + risk response actions |
| **Milestones** | Track milestones and project status |

### Case Study Data Included
- Original budget: PKR 5,000,000 | 16 weeks
- After CR-001 (AI Diagnosis) + CR-002 (Mobile App): +PKR 800,000 and +3 weeks
- Critical defects in Appointment Scheduling and Patient Records
- Full team as per question paper

### Important Notes for Demo
1. Go to **Change Requests** → you can see CR-001 and CR-002 already Implemented.
2. You can create a new CR, Approve it, then click **Implement** → budget and end date will update automatically.
3. On **Cost & EVM** page you will see CPI/SPI warnings if thresholds are breached.
4. On **Schedule** page you can see the Gantt-style timeline and update activity progress.

Good luck with your mid-term!
