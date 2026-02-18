# PDF Generator

**Version:** 0.1
**Author:** Nurul Islam  
**Dependencies:** WordPress 6.9 + nginx Web server + PHP v.7.4.30 + Contact Form 7 + Dompdf

---

## 📌 Overview

This plugin generates a **PDF logbook** when a specific Contact Form 7 form is submitted and provides a **controlled download link** on a dedicated page.

---

## ✅ What This Plugin Does

- Listens to a **specific Contact Form 7 form**
- Extracts predefined form fields
- Generates a PDF using Dompdf
- Stores the PDF in WordPress uploads
- Redirects the user to a download page
- Allows **safe disabling at any time**

---

## 🧠 Architecture Summary

Contact Form 7 (submission)
↓
Plugin guardrails (panic switch, env, form ID)
↓
PDF generation (Dompdf)
↓
File stored in uploads/logbooks
↓
CF7 redirects to /pdf-ready/
↓
Plugin injects download link

Redirect logic is **owned by Contact Form 7**, not the plugin.

---

## 📦 Installation

### Required files

pdf-generator/
├── main.php
├── dompdf/
│ 		└── autoload.inc.php
└── templates/
		└── logbook.php
		
---
		
## 📄 Required WordPress Page

Create a page with:

- **Slug:** `pdf-ready`
- **Title:** Any
- **Content:** Leave empty

The plugin dynamically injects the download UI.

---

## 📝 Contact Form 7 Setup

### Required Form Fields

The CF7 form **must** contain fields with these exact names:

```text
[text* student_name]
[text* course_name]
[date* log_date]
[textarea* activity]
[number* hours]

[submit "Generate PDF"]

⚠️ Field names are case-sensitive.

Redirect Configuration

In the Additional Settings tab of the CF7 form, add:

	on_sent_ok: "location = '/pdf-ready/';"

This is the only supported redirect method.

## 🔐 Plugin Guardrails

This plugin is intentionally defensive.
Understanding these settings is critical.

1️. Execution Toggle (Panic Button)

	define('CF7_PDF_ENABLED', true);
	
When disabled:

	CF7 still works

	No PDFs are generated

	No errors occur
	
2. Environment Guard

	define('CF7_PDF_ENV', 'dev');
	define('CF7_PDF_ALLOWED_ENVS', ['dev', 'staging']);


PDF generation only runs if the current environment is allowed.

Example:

CF7_PDF_ENV = 'production'

→ PDF generation blocked

This prevents accidental production execution.
This feature can be removed if the plugin is tested and functional at staging level.

3. Hard Form Whitelist
	
	$CF7_PDF_ALLOWED_FORMS = [
		80, // input form ID
	];

Only whitelisted form IDs can generate PDFs.
All other forms are ignored safely.

## 📁 PDF Storage

PDFs are stored in:

	wp-content/uploads/logbooks/


Filenames follow this format:

	logbook-<timestamp>.pdf

## 🧾 PDF Template
	Location
		templates/"here"

Purpose
	Layout
	Styling
	Variable rendering

Available variables
	$student_name
	$course_name
	$logbook_rows
	
## 🧪 Logging & Debugging

Debug logging can be enabled:

	define('CF7_PDF_DEBUG', true);

Logs appear in PHP error logs as:

	[CF7 PDF] PDF saved: logbook-1770008549.pdf

Recommended:

	Enable on local/staging
	Disable on production if not needed

## 🔁 Safe Rollback Procedure

If anything unexpected happens:

Set:

	define('CF7_PDF_ENABLED', false);

Save the file

Result:

	PDF generation stops immediately
	Site remains fully functional
	No plugin deactivation required

## 🔒 Security Model

	No admin UI
	No database tables
	No direct file exposure
	Uses WordPress uploads system
	Uses transient for short-lived state
	
## 🔮 Future Extensions

	This plugin is currently compatible with only Contact Form 7,
	but can be easily used for other form types also, e.g. Gravity Forms.