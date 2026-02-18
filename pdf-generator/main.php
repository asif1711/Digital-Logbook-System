<?php
/*
Plugin Name: PDF Generator - CF7 
Description: A plugin that generates PDF from a Contact Form 7 submission.
Author: Nurul Islam
Version: 0.1
*/

/* =====================================================
 * 📦 DATABASE TABLE (CREATED ON PLUGIN ACTIVATION)
 * ===================================================== */
register_activation_hook(__FILE__, function () {
    global $wpdb;

    $table = $wpdb->prefix . 'cf7_pdf_logs';
    $charset = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        form_id BIGINT UNSIGNED NOT NULL,
        student_name VARCHAR(255),
        course_name VARCHAR(255),
        manager_email VARCHAR(255),
        email_sent TINYINT(1) NOT NULL DEFAULT 0,
        email_sent_at DATETIME NULL,
        approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        approved_at DATETIME NULL,
        pdf_file VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;
	";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
});

/* =====================================================
 * 📄 PDF TEMPLATE SELECTOR
 * ===================================================== */
// template 1 | template 2
define('CF7_PDF_TEMPLATE', 'template 1');


/* =====================================================
 * 🔒 EXECUTION TOGGLE (PANIC BUTTON)
 * ===================================================== */
 // set to false to disable PDF generation safely
define('CF7_PDF_ENABLED', true); 

/* =====================================================
 * 🌍 ENVIRONMENT GUARD
 * ===================================================== */
// dev | staging | production
define('CF7_PDF_ENV', 'dev'); 

// environments where PDF generation is allowed
// dev | staging | production
define('CF7_PDF_ALLOWED_ENVS', ['dev']);

/* =====================================================
 * DEBUG (KEEP ENABLED DURING DEV)
 * ===================================================== */
define('CF7_PDF_DEBUG', false);

function cf7_pdf_log($message) {
    if ( CF7_PDF_DEBUG ) {
        error_log('[CF7 PDF] ' . $message);
    }
}

cf7_pdf_log('Plugin loaded');

/* =====================================================
 * LOAD DOMPDF
 * ===================================================== */
$dompdf_autoload = __DIR__ . '/dompdf/autoload.inc.php';
$cf7_pdf_dompdf_available = false;

if ( file_exists($dompdf_autoload) ) {
    require_once $dompdf_autoload;
    $cf7_pdf_dompdf_available = true;
    cf7_pdf_log('Dompdf loaded');
} else {
    cf7_pdf_log('WARNING: Dompdf not found');
}

use Dompdf\Dompdf;

/* =====================================================
 * FORM WHITELIST
 * ===================================================== */
$CF7_PDF_ALLOWED_FORMS = [
    80, // confirmed form ID
];

/* =====================================================
 * CF7 SUBMIT HANDLER
 * ===================================================== */
add_action('wpcf7_mail_sent', function ($contact_form) use (
    $cf7_pdf_dompdf_available,
    $CF7_PDF_ALLOWED_FORMS
) {

    cf7_pdf_log('CF7 mail sent hook fired');

    // ---- EXECUTION TOGGLE CHECK ----
    if ( ! CF7_PDF_ENABLED ) {
        cf7_pdf_log('Execution disabled by CF7_PDF_ENABLED switch');
        return;
    }

	// ---- ENVIRONMENT GUARD ----
	if ( ! in_array(CF7_PDF_ENV, CF7_PDF_ALLOWED_ENVS, true) ) {
		cf7_pdf_log(
			'Execution blocked by environment guard. ENV=' . CF7_PDF_ENV
		);
		
		// 🔒 IMPORTANT: clear stale PDF reference
		delete_transient('cf7_last_pdf');
	
		return;
	}

    // ---- FORM AWARENESS ----
    $form_id    = (int) $contact_form->id();
    $form_title = $contact_form->title();

    cf7_pdf_log("Form triggered: ID={$form_id}, Title='{$form_title}'");

    if ( ! empty($CF7_PDF_ALLOWED_FORMS) && ! in_array($form_id, $CF7_PDF_ALLOWED_FORMS, true) ) {
    cf7_pdf_log("BLOCKED: Form {$form_id} is not whitelisted");
    return;
	}

    // ---- DEPENDENCY CHECKS ----
    if ( ! class_exists('WPCF7_Submission') ) {
        cf7_pdf_log('WARNING: Contact Form 7 submission class missing');
        return;
    }

    if ( ! $cf7_pdf_dompdf_available ) {
        cf7_pdf_log('WARNING: PDF skipped (Dompdf unavailable)');
        return;
    }

    $submission = WPCF7_Submission::get_instance();
    if ( ! $submission ) {
        cf7_pdf_log('WARNING: Submission object missing');
        return;
    }

    // ---- DATA EXTRACTION ----
    $data = $submission->get_posted_data();

    $student_name = $data['student_name'] ?? '';
    $course_name  = $data['course_name'] ?? '';
	
	$enrollment_no = function_exists('dl_resolve_enrollment_no')
    ? dl_resolve_enrollment_no()
    : null;
	
    $log_date     = $data['log_date'] ?? '';
    $activity     = $data['activity'] ?? '';
    $hours        = $data['hours'] ?? '';

    if ( $student_name === '' || $course_name === '' ) {
        cf7_pdf_log('WARNING: Required fields missing');
        return;
    }

    $logbook_rows = [[
        'date'     => $log_date,
        'activity' => $activity,
        'hours'    => $hours,
    ]];

    // ---- PDF RENDER ----
    ob_start();
    $template_file = __DIR__ . '/templates/' . CF7_PDF_TEMPLATE . '.php';

	if ( ! file_exists($template_file) ) {
		cf7_pdf_log('ERROR: Template not found: ' . CF7_PDF_TEMPLATE);
		return;
	}

	include $template_file;

    $html = ob_get_clean();

    $pdf = new Dompdf();
    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'portrait');
    $pdf->render();

    // ---- STORAGE ----
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/logbooks';

    if ( ! file_exists($pdf_dir) ) {
        wp_mkdir_p($pdf_dir);
        cf7_pdf_log('Created logbooks directory');
    }

    $filename = 'logbook-' . time() . '.pdf';
    file_put_contents($pdf_dir . '/' . $filename, $pdf->output());

    cf7_pdf_log("PDF saved: {$filename}");
	
	// ===============================
	// Line Manager email mapping
	// ===============================
	$course_manager_map = [
		'MCA' => 'mca.manager@example.com',
		'BCA' => 'bca.manager@example.com',
		'MBA' => 'mba.manager@example.com',
	];

	// Resolve manager email for this logbook
	$manager_email = $course_manager_map[$course_name] ?? null;

	
	/* =====================================================
	 * 📦 DATABASE LOG (PHASE 2)
	 * ===================================================== */
	global $wpdb;
	$table = $wpdb->prefix . 'cf7_pdf_logs';

	$wpdb->insert(
    $table,
    [
        'form_id'       => $form_id,
        'student_name'  => $student_name,
        'course_name'   => $course_name,
		'enrollment_no' => $enrollment_no,
        'manager_email' => $manager_email,
        'pdf_file'      => $filename,
    ],
    [
        '%d',
        '%s',
        '%s',
		'%s',
        '%s',
        '%s',
    ]
	);

	$log_id = $wpdb->insert_id;


	cf7_pdf_log('DB record inserted');

});

/* =====================================================
 * CLIENT-SIDE REDIRECT
 * ===================================================== */
add_action('wp_footer', function () {
    ?>
    <script>
    document.addEventListener('wpcf7mailsent', function (event) {

        // Only redirect for the whitelisted form ID
        if (event.detail && event.detail.contactFormId === 80) {
            window.location.href = "<?php echo esc_url( site_url('/pdf-ready/') ); ?>";
        }

    }, false);
    </script>
    <?php
});

/* =====================================================
 * DOWNLOAD PAGE (DB-BASED, NO TRANSIENTS)
 * ===================================================== */
add_filter('the_content', function ($content) {

    if ( ! is_page('pdf-ready') ) {
        return $content;
    }

    // 🔒 HARD BLOCK WHEN DISABLED
    if ( ! defined('CF7_PDF_ENABLED') || CF7_PDF_ENABLED === false ) {
        return '<p>PDF generation is currently disabled.</p>';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cf7_pdf_logs';

    // Fetch the most recent PDF
    $row = $wpdb->get_row("
        SELECT pdf_file
        FROM {$table}
        ORDER BY created_at DESC
        LIMIT 1
    ");

    if ( ! $row || empty($row->pdf_file) ) {
        return '<p>Sorry, your PDF is not available.</p>';
    }

    $pdf_url = content_url('uploads/logbooks/' . basename($row->pdf_file));

    return '
        <h2>Your PDF is ready</h2>
        <p>Please click the button below to download your logbook.</p>
        <p>
            <a href="' . esc_url($pdf_url) . '" download>
                ⬇ Download PDF
            </a>
        </p>
    ';
});

