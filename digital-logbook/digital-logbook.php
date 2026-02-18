<?php
/**
 * Plugin Name: Digital Logbook
 * Description: Turns user data into digital logbooks.
 * Author: Nurul Islam
 * Version: 0.1
 */

function dl_resolve_enrollment_no() {
    // 🔧 TEMP: fixed enrollment for ACC testing
    return 'ENR-TEST-001';
}


function dl_resolve_student_email_by_enrollment($enrollment_no) {

    global $wpdb;

    if (empty($enrollment_no)) {
        return null;
    }

    return $wpdb->get_var(
        $wpdb->prepare(
            "SELECT email
             FROM {$wpdb->prefix}acc_students
             WHERE enrollment_no = %s",
            $enrollment_no
        )
    );
}

if (!defined('ABSPATH')) {
    exit;
}

add_action('phpmailer_init', function ($phpmailer) {

    $phpmailer->isSMTP();
    $phpmailer->Host       = '127.0.0.1';
    $phpmailer->Port       = 8025;
    $phpmailer->SMTPAuth   = false;
    $phpmailer->SMTPSecure = false;

});


// Load notification runner
//require_once __DIR__ . '/includes/send-manager-emails.php';
//require_once __DIR__ . '/includes/send-student-notifications.php';


add_action('init', function () {

    if (!isset($_GET['dl_test_pdf_email'])) {
        return;
    }

    require_once __DIR__ . '/email/EmailService.php';

    // 🔁 CHANGE THIS to a REAL PDF PATH you already generate
    $pdf_path = WP_CONTENT_DIR . '/uploads/logbooks/logbook-1770372663.pdf';

	if (!file_exists($pdf_path)) {
		wp_die('PDF NOT FOUND: ' . $pdf_path);
	}

    $email = new DL_EmailService();

    $sent = $email->send_with_pdf(
        'manager@test.local',
        'Logbook Submission – PDF Attached',
        '<p>Please find the attached logbook PDF for review.</p>',
        $pdf_path
    );

    wp_die($sent ? 'PDF email sent. Check Mailpit.' : 'Failed to send PDF email.');
});

add_action('phpmailer_init', function ($phpmailer) {

    $phpmailer->isSMTP();
    $phpmailer->Host = '127.0.0.1';
    $phpmailer->Port = 1025;
    $phpmailer->SMTPAuth = false;
    $phpmailer->SMTPSecure = false;
    $phpmailer->SMTPAutoTLS = false;

});
