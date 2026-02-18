<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', function () {

    // ❌ Do NOT run during admin, AJAX, or REST
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    // ✅ Only run on dashboard page
    if (!is_page('dashboard-main')) {
        return;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'cf7_pdf_logs';

    // 1. Fetch unsent logbooks
    $logbooks = $wpdb->get_results("
        SELECT *
        FROM {$table}
        WHERE email_sent = 0
          AND manager_email IS NOT NULL
        ORDER BY created_at ASC
    ");

    if (empty($logbooks)) {
        return;
    }

    require_once __DIR__ . '/../email/EmailService.php';

    $emailService = new DL_EmailService();

    foreach ($logbooks as $log) {

		// 🔒 STEP A: claim the row atomically
		$claimed = $wpdb->update(
			$table,
			['email_sent' => 2], // processing
			[
				'id' => $log->id,
				'email_sent' => 0,
			],
			['%d'],
			['%d', '%d']
		);

		// If we didn't claim it, another request already did
		if ($claimed === 0) {
			continue;
		}

		// 🔒 STEP B: resolve PDF path
		$upload_dir = wp_upload_dir();
		$pdf_path = $upload_dir['basedir'] . '/logbooks/' . $log->pdf_file;

		if (!file_exists($pdf_path)) {
			// rollback claim
			$wpdb->update(
				$table,
				['email_sent' => 0],
				['id' => $log->id],
				['%d'],
				['%d']
			);
			continue;
		}

		// 🔒 STEP C: generate approval token ONCE
		$approval_token = bin2hex(random_bytes(32));
		$approval_expires_at = date('Y-m-d H:i:s', strtotime('+48 hours'));

		$wpdb->update(
			$table,
			[
				'approval_token' => $approval_token,
				'approval_token_expires_at' => $approval_expires_at,
			],
			['id' => $log->id],
			['%s', '%s'],
			['%d']
		);

		// 🔒 STEP D: build URLs
		$approval_url = add_query_arg(
			[
				'token'  => $approval_token,
				'action' => 'approve',
			],
			site_url('/logbook-approval/')
		);

		$rejection_url = add_query_arg(
			[
				'token'  => $approval_token,
				'action' => 'reject',
			],
			site_url('/logbook-approval/')
		);

		$email_body = '
		<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8; padding:24px; font-family:Arial, Helvetica, sans-serif;">
		  <tr>
			<td align="center">
			  <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:6px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.08);">

				<!-- HEADER -->
				<tr>
				  <td style="background:#0d6efd; padding:18px 24px; color:#ffffff;">
					<h2 style="margin:0; font-size:20px;">Logbook Review Required</h2>
				  </td>
				</tr>

				<!-- BODY -->
				<tr>
				  <td style="padding:24px; color:#333333; font-size:14px; line-height:1.6;">
					<p style="margin-top:0;">
					  A new student logbook has been submitted and requires your review.
					</p>

					<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0; border-collapse:collapse;">
					  <tr>
						<td style="padding:8px 0; font-weight:bold; width:120px;">Student:</td>
						<td style="padding:8px 0;">' . esc_html($log->student_name) . '</td>
					  </tr>
					  <tr>
						<td style="padding:8px 0; font-weight:bold;">Course:</td>
						<td style="padding:8px 0;">' . esc_html($log->course_name) . '</td>
					  </tr>
					</table>

					<p>
					  Please choose one of the actions below:
					</p>

					<!-- ACTION BUTTONS -->
					<table cellpadding="0" cellspacing="0" style="margin:24px 0;">
					  <tr>
						<td style="padding-right:12px;">
						  <a href="' . esc_url($approval_url) . '"
							 style="
							   display:inline-block;
							   padding:12px 20px;
							   background:#28a745;
							   color:#ffffff;
							   text-decoration:none;
							   font-weight:bold;
							   border-radius:4px;
							 ">
							✔ Approve Logbook
						  </a>
						</td>
						<td>
						  <a href="' . esc_url($rejection_url) . '"
							 style="
							   display:inline-block;
							   padding:12px 20px;
							   background:#dc3545;
							   color:#ffffff;
							   text-decoration:none;
							   font-weight:bold;
							   border-radius:4px;
							 ">
							✖ Reject Logbook
						  </a>
						</td>
					  </tr>
					</table>

					<p style="font-size:12px; color:#666666;">
					  This approval link will expire in <strong>48 hours</strong>.
					  If no action is taken within this time, the link will become invalid.
					</p>
				  </td>
				</tr>

				<!-- FOOTER -->
				<tr>
				  <td style="background:#f1f3f5; padding:16px 24px; font-size:12px; color:#666666;">
					<p style="margin:0;">
					  This is an automated message from the Digital Logbook System.<br>
					  Please do not reply to this email.
					</p>
				  </td>
				</tr>

			  </table>
			</td>
		  </tr>
		</table>
		';


		// 🔒 STEP E: send email
		$sent = $emailService->send_with_pdf(
			$log->manager_email,
			'Logbook Review Required – ' . $log->student_name,
			$email_body,
			$pdf_path
		);

		// 🔒 STEP F: finalize state
		if ($sent) {
			$wpdb->update(
				$table,
				[
					'email_sent'    => 1,
					'email_sent_at' => current_time('mysql'),
				],
				['id' => $log->id],
				['%d', '%s'],
				['%d']
			);
		} else {
			// rollback on failure
			$wpdb->update(
				$table,
				['email_sent' => 0],
				['id' => $log->id],
				['%d'],
				['%d']
			);
		}
	}

});
