<?php
if (!defined('ABSPATH')) {
    exit;
}

class DL_EmailService {

    public function send_html($to, $subject, $message) {

        
        if (empty($to)) {
            return false;
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        return wp_mail($to, $subject, $message, $headers);
    }

    

    public function send_with_pdf($to, $subject, $message, $pdf_path) {

       

        if (!file_exists($pdf_path)) {
            error_log('PDF not found: ' . $pdf_path);
            return false;
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        return wp_mail($to, $subject, $message, $headers, [$pdf_path]);
    }
}

