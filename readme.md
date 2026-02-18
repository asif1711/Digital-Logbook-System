# Digital Logbook System

**Version:** 1.0\
**Author:** Nurul Islam\
**Dependencies:** WordPress 6.9 · Nginx · PHP 7.4.30 · MySQL · Contact
Form 7 · Dompdf · Mailpit (Local)

------------------------------------------------------------------------

## 📌 Overview

The Digital Logbook System is a WordPress-based workflow system that:

-   Generates PDF logbooks from Contact Form 7 submissions\
-   Stores submission metadata in a structured database\
-   Displays pending logbooks inside a Line Manager Dashboard\
-   Allows approval / rejection via dashboard (single source of truth)\
-   Sends notification emails to students\
-   Sends approved PDF copies to Admin Team

This system replaces email-based approval with a centralized dashboard
workflow.

------------------------------------------------------------------------

## 🧠 System Architecture

Contact Form 7 (submission)\
↓\
PDF Generator (Dompdf)\
↓\
PDF stored in uploads/logbooks\
↓\
Metadata stored in wp_cf7_pdf_logs\
↓\
Dashboard displays pending entries\
↓\
Line Manager approves/rejects\
↓\
Email notifications triggered\
↓\
Admin receives approved PDF copy

Single approval authority = Dashboard

------------------------------------------------------------------------

## 📦 Project Structure

digital-logbook-system/\
│\
├── pdf-generator/\
│ ├── main.php\
│ ├── dompdf/\
│ └── templates/\
│\
├── digital-logbook/\
│ ├── digital-logbook.php\
│ ├── includes/\
│ ├── email/\
│ └── line-manager-portal.php\
│\
└── README.md

------------------------------------------------------------------------

## ✅ Completed Features

### PDF Generator

-   Listens to specific CF7 form
-   Extracts required fields
-   Generates PDF using Dompdf
-   Stores PDF in wp-content/uploads/logbooks/
-   Logs metadata in database
-   Redirects user to /pdf-ready/
-   Guardrails enabled (panic switch + environment control)

### Database Logging

Table: wp_cf7_pdf_logs

Tracks: - form_id - student_name - enrollment_no - course_name -
manager_email - pdf_file - approval_status - rejection_reason -
approved_by - approved_at - created_at

### Line Manager Dashboard

-   KPI Cards (Pending / Approved / Rejected / Total)
-   Pending Approvals Table
-   View Logbook Details
-   Approve via dashboard
-   Reject with required reason
-   DB updates atomically

### Email Notification System

On Approval: - Approved PDF sent to Admin Team - Approval email sent to
student

On Rejection: - Rejection email sent to student (with reason)

SMTP works with Mailpit (local) and production SMTP later.

------------------------------------------------------------------------

## 📝 Contact Form 7 Requirements

Required fields (exact names):

\[text\* student_name\]\
\[text\* enrollment_no\]\
\[text\* course_name\]\
\[email\* manager_email\]\
\[date\* log_date\]\
\[textarea\* activity\]\
\[number\* hours\]

Field names are case-sensitive.

------------------------------------------------------------------------

## 📄 Required WordPress Pages

Create:

1.  pdf-ready\
2.  dashboard-main

Add shortcodes to dashboard page: \[lm_kpi type="pending"\]\
\[lm_pending_table\]

------------------------------------------------------------------------

## 📧 SMTP Configuration (Local)

Mailpit SMTP: 127.0.0.1:1025

Mailpit UI: http://localhost:8025

------------------------------------------------------------------------

## 🚀 Installation

1.  Clone repository
2.  Copy plugin folders into wp-content/plugins/
3.  Activate plugins in WordPress
4.  Create required pages
5.  Configure CF7 form
6.  Test full workflow

------------------------------------------------------------------------

## 📊 Work Completed

✔ PDF generation\
✔ Database logging\
✔ Dashboard approval system\
✔ Email notifications\
✔ Rejection reason enforcement\
✔ Admin PDF copy\
✔ SMTP working locally\
✔ Removal of email-based approval tokens

------------------------------------------------------------------------

## 🔮 Additional Work To Be Done

-   Role-based access (line_manager role)
-   Full logbook history page with filtering & pagination
-   Activity log section
-   Production SMTP integration
-   Cron-based email queue (optional)
-   Admin reporting dashboard

------------------------------------------------------------------------

## 🏁 Final Architecture State

Dashboard-driven\
Database-backed\
Deterministic\
Audit-capable\
Extensible\
GitHub-ready
