<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_loaded', function () {

    global $wpdb;
    $table = $wpdb->prefix . 'cf7_pdf_logs';

    // Fetch approved/rejected logbooks not yet notified
    $rows = $wpdb->get_results("
        SELECT *
        FROM {$table}
        WHERE approval_status IN ('approved','rejected')
          AND student_notified = 0
          AND enrollment_no IS NOT NULL
    ");

    if (empty($rows)) {
        return;
    }

    require_once __DIR__ . '/../email/EmailService.php';
    $emailService = new DL_EmailService();

    foreach ($rows as $log) {

        // 🔒 Claim row (prevents duplicates)
        $claimed = $wpdb->update(
            $table,
            ['student_notified' => 2], // processing
            [
                'id' => $log->id,
                'student_notified' => 0,
            ],
            ['%d'],
            ['%d', '%d']
        );

        if ($claimed === 0) {
            continue;
        }

        // Resolve student email via ACC mock
        $student_email = dl_resolve_student_email_by_enrollment($log->enrollment_no);

        if (empty($student_email)) {
            // rollback
            $wpdb->update(
                $table,
                ['student_notified' => 0],
                ['id' => $log->id],
                ['%d'],
                ['%d']
            );
            continue;
        }

        // Build email
        if ($log->approval_status === 'approved') {
            $subject = 'Your Logbook Has Been Approved';
            $body = '
                <p>Dear ' . esc_html($log->student_name) . ',</p>
                <p>Your logbook for <strong>' . esc_html($log->course_name) . '</strong> has been approved.</p>
            ';
        } else {
            $subject = 'Your Logbook Has Been Rejected';
            $body = '
				<p>Dear ' . esc_html($log->student_name) . ',</p>
				<p>Your logbook for <strong>' . esc_html($log->course_name) . '</strong> was rejected.</p>
				<p><strong>Reason:</strong></p>
				<p>' . nl2br(esc_html($log->rejection_reason)) . '</p>
				<p>Please review and resubmit.</p>
			';

        }

        // Send email
        $sent = $emailService->send_html(
            $student_email,
            $subject,
            $body
        );

        if ($sent) {
            $wpdb->update(
                $table,
                [
                    'student_notified' => 1,
                    'student_notified_at' => current_time('mysql'),
                ],
                ['id' => $log->id],
                ['%d', '%s'],
                ['%d']
            );
        } else {
            // rollback on failure
            $wpdb->update(
                $table,
                ['student_notified' => 0],
                ['id' => $log->id],
                ['%d'],
                ['%d']
            );
        }
    }
});
