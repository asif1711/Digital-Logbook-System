Digital Logbook System

Version: 1.0
Author: Nurul Islam
Dependencies: WordPress 6.9 · Nginx · PHP 7.4.30 · MySQL · Contact Form 7 · Dompdf · Mailpit (Local)

📌 Overview

The Digital Logbook System is a WordPress-based workflow system that:

Generates PDF logbooks from Contact Form 7 submissions

Stores submission metadata in a structured database

Displays pending logbooks inside a Line Manager Dashboard

Allows approval / rejection via dashboard (single source of truth)

Sends notification emails to students

Sends approved PDF copies to Admin Team

This system replaces email-based approval with a centralized dashboard workflow.

🧠 System Architecture

Contact Form 7 (submission)
↓
PDF Generator (Dompdf)
↓
PDF stored in uploads/logbooks
↓
Metadata stored in wp_cf7_pdf_logs
↓
Dashboard displays pending entries
↓
Line Manager approves/rejects
↓
Email notifications triggered
↓
Admin receives approved PDF copy

Single approval authority = Dashboard

📦 Project Structure

digital-logbook-system/
│
├── pdf-generator/
│ ├── main.php
│ ├── dompdf/
│ └── templates/
│
├── digital-logbook/
│ ├── digital-logbook.php
│ ├── includes/
│ ├── email/
│ └── line-manager-portal.php
│
└── README.md

✅ Completed Features
1️⃣ PDF Generator

Listens to specific CF7 form

Extracts required fields

Generates PDF using Dompdf

Stores PDF in:
wp-content/uploads/logbooks/

Logs metadata in database

Redirects user to /pdf-ready/

Guardrails enabled (panic switch + environment control)

2️⃣ Database Logging

Table:

wp_cf7_pdf_logs


Tracks:

form_id

student_name

enrollment_no

course_name

manager_email

pdf_file

approval_status

rejection_reason

approved_by

approved_at

created_at

Supports audit and reporting.

3️⃣ Line Manager Dashboard

KPI Cards (Pending / Approved / Rejected / Total)

Pending Approvals Table

View Logbook Details

View PDF button

Approve via dashboard

Reject with required reason

DB updates atomically

UI controlled and responsive

Approval happens ONLY inside dashboard.

4️⃣ Email Notification System
On Approval:

Approved PDF sent to Admin Team

Approval email sent to student

On Rejection:

Rejection email sent to student

Includes rejection reason

SMTP works with:

Mailpit (local)

Production SMTP later

No duplicate sending.

🔐 Security Model

No approval via email links

No public approval tokens

Dashboard-based authorization

Uses WordPress capability checks

Database-level state tracking

Idempotent email sending

No duplicate notifications

📝 Contact Form 7 Requirements

Required fields (exact names):

[text* student_name]
[text* enrollment_no]
[text* course_name]
[email* manager_email]
[date* log_date]
[textarea* activity]
[number* hours]


Field names are case-sensitive.

📄 Required WordPress Pages

Create:

/pdf-ready

Empty content

Plugin injects download UI

/dashboard-main

Contains dashboard shortcodes:

[lm_kpi type="pending"]
[lm_pending_table]

📁 PDF Storage

Location:

wp-content/uploads/logbooks/


Filename:

logbook-<timestamp>.pdf

📧 SMTP Configuration (Local)

Mailpit required:

SMTP:

127.0.0.1:1025


UI:

http://localhost:8025


Configured via:

add_action('phpmailer_init', function ($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = '127.0.0.1';
    $phpmailer->Port = 1025;
    $phpmailer->SMTPAuth = false;
    $phpmailer->SMTPSecure = false;
    $phpmailer->SMTPAutoTLS = false;
});

🚀 Installation Guide
Step 1 — Clone Repository
git clone https://github.com/your-username/digital-logbook-system.git


Copy plugin folders into:

wp-content/plugins/

Step 2 — Activate Plugins

Activate PDF Generator

Activate Digital Logbook

Step 3 — Create Required Pages

pdf-ready

dashboard-main

Step 4 — Configure CF7 Form

Use required field names.

Step 5 — Run Test Flow

Submit form

Confirm PDF created

Confirm DB row inserted

Open dashboard

Approve

Check Mailpit for:

Admin email with PDF

Student notification

🗂 GitHub Upload Instructions
Initialize Repository

Inside project root:

git init
git add .
git commit -m "Initial Digital Logbook System"


Create GitHub repository (via web).

Then:

git remote add origin https://github.com/your-username/digital-logbook-system.git
git branch -M main
git push -u origin main

Recommended .gitignore

Add:

/wp-content/uploads/
/node_modules/
/vendor/
/*.log


Do NOT commit:

uploads

debug logs

environment secrets

📊 Work Completed (As Per Initial Flow)

✔ PDF generation
✔ Database logging
✔ Dashboard approval system
✔ Email notifications
✔ Rejection reason enforcement
✔ Admin PDF copy
✔ SMTP working locally
✔ Removal of email-based approval tokens

🔮 Additional Work To Be Done (Future Roadmap)
🔹 1. Role-Based Access

Create custom role:

line_manager


Remove reliance on manage_options.

🔹 2. Full Logbook History Page

Add:

Filtering by status

Search by student

Pagination

🔹 3. Activity Log Section

Display:

Who approved

When

Rejection reason

Audit trace

🔹 4. Production SMTP Integration

SendGrid / AWS SES

Environment detection

Failover handling

🔹 5. Cron-Based Email Queue (Optional)

Move email sending to:

WP Cron

Action Scheduler

🔹 6. Admin Reporting Dashboard

Monthly approvals

Export CSV

Analytics view

🧭 Current System Status

Core workflow: Stable
Dashboard approval: Stable
Email system: Stable
Local environment: Stable
Production-ready: After SMTP + role hardening

🏁 Final Architecture State

The Digital Logbook System is now:

Dashboard-driven

Database-backed

Deterministic

Extensible


Help you structure the repository p
