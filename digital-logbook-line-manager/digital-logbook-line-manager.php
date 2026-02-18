<?php
/**
 * Plugin Name: Digital Logbook - Line Manager Portal
 * Description: Line Manager Dashboard for logbook approvals.
 * Author: Nurul Islam
 * Version: 0.1
 */

define('LM_ADMIN_TEAM_EMAIL', 'admin@test.local');

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Dashboard Shortcode
 */
function lm_line_manager_dashboard() {

    if (!current_user_can('manage_options')) {
        return '';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cf7_pdf_logs';

    $pending  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approval_status = 'pending'");
    $approved = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approval_status = 'approved'");
    $rejected = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approval_status = 'rejected'");
    $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

    return json_encode([
        'pending'  => $pending,
        'approved' => $approved,
        'rejected' => $rejected,
        'total'    => $total
    ]);
}

function lm_kpi_value($atts) {

    if (!current_user_can('manage_options')) {
        return '';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cf7_pdf_logs';

    $type = $atts['type'] ?? '';

    switch ($type) {
        case 'pending':
            return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approval_status = 'pending'");
        case 'approved':
            return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approval_status = 'approved'");
        case 'rejected':
            return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approval_status = 'rejected'");
        case 'total':
            return $wpdb->get_var("SELECT COUNT(*) FROM $table");
        default:
            return '';
    }
}

add_action('init', function () {

    if (!isset($_POST['lm_action'], $_POST['lm_id'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cf7_pdf_logs';

    $action = sanitize_text_field($_POST['lm_action']);
    $id     = intval($_POST['lm_id']);

    if (!in_array($action, ['approve', 'reject'], true)) {
        return;
    }

    $rejection_reason = isset($_POST['rejection_reason'])
        ? sanitize_textarea_field($_POST['rejection_reason'])
        : '';

    // Require reason if rejecting
    if ($action === 'reject' && empty($rejection_reason)) {
        wp_die('Rejection reason is required.');
    }

    $data = [
        'approval_status' => $action === 'approve' ? 'approved' : 'rejected',
        'approved_at'     => current_time('mysql'),
        'approved_by'     => get_current_user_id(),
    ];

    if ($action === 'reject') {
        $data['rejection_reason'] = $rejection_reason;
    }

    $wpdb->update(
        $table,
        $data,
        ['id' => $id]
    );

	require_once WP_CONTENT_DIR . '/plugins/digital-logbook/email/EmailService.php';

	$emailService = new DL_EmailService();

	// Fetch updated log
	$log = $wpdb->get_row(
		$wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id)
	);

	$upload_dir = wp_upload_dir();
	$pdf_path = $upload_dir['basedir'] . '/logbooks/' . $log->pdf_file;

	// ---------- APPROVED ----------
	if ($action === 'approve') {

		// 1️⃣ Send PDF copy to Admin Team
		if (file_exists($pdf_path)) {

			$admin_subject = 'Approved Logbook – ' . $log->student_name;

			$admin_body = '
				<p>Logbook approved.</p>
				<p><strong>Student:</strong> ' . esc_html($log->student_name) . '</p>
				<p><strong>Course:</strong> ' . esc_html($log->course_name) . '</p>
			';

			$emailService->send_with_pdf(
				LM_ADMIN_TEAM_EMAIL,
				$admin_subject,
				$admin_body,
				$pdf_path
			);
		}

		// 2️⃣ Send approval email to student
		$student_email = dl_resolve_student_email_by_enrollment($log->enrollment_no);

		if ($student_email) {

			$student_subject = 'Your Logbook Has Been Approved';

			$student_body = '
				<p>Dear ' . esc_html($log->student_name) . ',</p>
				<p>Your logbook for <strong>' . esc_html($log->course_name) . '</strong> has been approved.</p>
			';

			$emailService->send_html(
				$student_email,
				$student_subject,
				$student_body
			);
		}
	}

	// ---------- REJECTED ----------
	if ($action === 'reject') {

		$student_email = dl_resolve_student_email_by_enrollment($log->enrollment_no);

		if ($student_email) {

			$student_subject = 'Your Logbook Has Been Rejected';

			$student_body = '
				<p>Dear ' . esc_html($log->student_name) . ',</p>
				<p>Your logbook for <strong>' . esc_html($log->course_name) . '</strong> was rejected.</p>
				<p><strong>Reason:</strong></p>
				<p>' . nl2br(esc_html($log->rejection_reason)) . '</p>
			';

			$emailService->send_html(
				$student_email,
				$student_subject,
				$student_body
			);
		}
	}


    wp_redirect(remove_query_arg(['lm_view']));
    exit;
});

add_shortcode('lm_kpi', 'lm_kpi_value');

function lm_pending_table() {

    if (!current_user_can('manage_options')) {
        return '';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cf7_pdf_logs';

    $rows = $wpdb->get_results(
        "SELECT id, student_name, course_name, created_at
         FROM $table
         WHERE approval_status = 'pending'
         ORDER BY created_at ASC
         LIMIT 5"
    );

    ob_start();
	
	echo lm_view_logbook();
	
    ?>
	
    <div class="lm-card">
        <div class="lm-card-header">
            <h3>Pending Approvals</h3>
        </div>

        <div class="lm-table-wrapper">
            <table class="lm-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">
                            No pending approvals.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->student_name); ?></td>
                            <td><?php echo esc_html($row->course_name); ?></td>
                            <td><?php echo esc_html(date('d M Y', strtotime($row->created_at))); ?></td>
                            <td>
                                <span class="lm-badge lm-badge-pending">Pending</span>
                            </td>
                            <td>
                                <a class="lm-btn lm-btn-pdf" href="?lm_view=<?php echo esc_attr($row->id); ?>">
									View
								</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <div class="lm-card-footer">
            <a href="#">View All Pending</a>
        </div>
    </div>

    <?php

    return ob_get_clean();
}

function lm_view_logbook() {

    if (!isset($_GET['lm_view'])) {
        return '';
    }

    if (!current_user_can('manage_options')) {
        return '';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cf7_pdf_logs';

    $id = intval($_GET['lm_view']);

    $log = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id)
    );

    if (!$log) {
        return '<p>Logbook not found.</p>';
    }

    ob_start();
    ?>

    <div class="lm-card" style="margin-bottom:20px;">
        <h3>Logbook Details</h3>

        <p><strong>Student:</strong> <?php echo esc_html($log->student_name); ?></p>
        <p><strong>Enrollment No:</strong> <?php echo esc_html($log->enrollment_no); ?></p>
        <p><strong>Course:</strong> <?php echo esc_html($log->course_name); ?></p>
        <p><strong>Submitted:</strong> <?php echo esc_html($log->created_at); ?></p>

        <p>
            <?php
			$upload_dir = wp_upload_dir();
			$pdf_url = $upload_dir['baseurl'] . '/logbooks/' . $log->pdf_file;
			?>

			<a href="<?php echo esc_url($pdf_url); ?>" 
			   target="_blank"
			   class="lm-btn lm-btn-pdf">
				View PDF
			</a>

        </p>

        <hr class="lm-separator">

		<form method="post" style="margin-top:15px;" id="lm-action-form">

			<input type="hidden" name="lm_id" value="<?php echo esc_attr($log->id); ?>">
			<input type="hidden" name="lm_action" id="lm_action_input">

			<div style="display:flex; gap:10px;">

				<button type="button"
						onclick="lmApprove()"
						class="lm-btn lm-btn-approve">
					Approve
				</button>

				<button type="button"
						onclick="lmShowReject()"
						class="lm-btn lm-btn-reject">
					Reject
				</button>

			</div>

			<!-- Hidden rejection block -->
			<div id="lm-reject-block" style="display:none; margin-top:15px;">

				<label style="display:block; margin-bottom:6px; font-weight:600;">
					Rejection Reason
				</label>

				<textarea name="rejection_reason"
						  id="lm-rejection-reason"
						  style="width:100%; min-height:80px; padding:8px; border-radius:6px; border:1px solid #ccc;"></textarea>

				<br><br>

				<button type="button"
						onclick="lmConfirmReject()"
						class="lm-btn lm-btn-reject">
					Confirm Reject
				</button>

			</div>

		</form>


    </div>

	<script>
		function lmApprove() {
			document.getElementById('lm_action_input').value = 'approve';
			document.getElementById('lm-action-form').submit();
		}

		function lmShowReject() {
			const block = document.getElementById('lm-reject-block');
			block.style.display = 'block';
			block.scrollIntoView({ behavior: 'smooth' });
			document.getElementById('lm-rejection-reason').focus();

		}

		function lmConfirmReject() {
			const reason = document.getElementById('lm-rejection-reason').value.trim();

			if (!reason) {
				alert('Rejection reason is required.');
				return;
			}

			document.getElementById('lm_action_input').value = 'reject';
			document.getElementById('lm-action-form').submit();
		}
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('lm_pending_table', 'lm_pending_table');