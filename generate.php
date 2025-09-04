<?php
declare(strict_types=1);

// Basic backend to handle AJAX submission, file uploads and PDF generation
// using Dompdf. Works standalone or inside WordPress (no WP APIs required).

ini_set('display_errors', '0');
error_reporting(E_ALL);

// Allow large PDFs/images if needed
ini_set('memory_limit', '512M');
set_time_limit(120);

// CORS headers if accessed cross-origin (adjust as necessary)
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Ensure uploads dir exists
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
	@mkdir($uploadsDir, 0775, true);
}

function sanitize_text(string $v): string {
	return trim(filter_var($v, FILTER_UNSAFE_RAW));
}

function sanitize_array(array $arr): array {
	$out = [];
	foreach ($arr as $k => $v) {
		if (is_array($v)) { $out[$k] = sanitize_array($v); }
		else { $out[$k] = sanitize_text((string)$v); }
	}
	return $out;
}

function handle_upload(string $field, string $uploadsDir): ?string {
	if (!isset($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
		return null;
	}
	$allowed = ['image/png' => '.png', 'image/jpeg' => '.jpg'];
	$mime = mime_content_type($_FILES[$field]['tmp_name']);
	if (!isset($allowed[$mime])) {
		return null; // silently ignore unsupported uploads
	}
	$ext = $allowed[$mime];
	$target = $uploadsDir . '/' . uniqid($field . '_', true) . $ext;
	if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
		return null;
	}
	return $target;
}

try {
	// Sanitize inputs
	$data = [
		'quotation_no' => isset($_POST['quotation_no']) ? sanitize_text((string)$_POST['quotation_no']) : '',
		'quotation_date' => isset($_POST['quotation_date']) ? sanitize_text((string)$_POST['quotation_date']) : '',
		'currency' => isset($_POST['currency']) ? sanitize_text((string)$_POST['currency']) : 'INR',
		'company_name' => isset($_POST['company_name']) ? sanitize_text((string)$_POST['company_name']) : '',
		'company_email' => isset($_POST['company_email']) ? sanitize_text((string)$_POST['company_email']) : '',
		'customer_name' => isset($_POST['customer_name']) ? sanitize_text((string)$_POST['customer_name']) : '',
		'customer_email' => isset($_POST['customer_email']) ? sanitize_text((string)$_POST['customer_email']) : '',
		'items' => isset($_POST['items']) && is_array($_POST['items']) ? sanitize_array($_POST['items']) : ['name'=>[], 'qty'=>[], 'price'=>[], 'tax'=>[]],
		'terms' => isset($_POST['terms']) && is_array($_POST['terms']) ? array_map('sanitize_text', $_POST['terms']) : [],
	];

	$logoPath = handle_upload('company_logo', $uploadsDir);
	$signaturePath = handle_upload('signature', $uploadsDir);

	// Render HTML via template
	ob_start();
	// Variables for template scope
	$_TEMPLATE_DATA = $data; // keep for debugging if needed
	$data = $data; // explicit
	$logoPath = $logoPath; $signaturePath = $signaturePath;
	require __DIR__ . '/templates/quote-template.php';
	$html = ob_get_clean();

	$options = new Options();
	$options->set('isHtml5ParserEnabled', true);
	$options->setChroot(__DIR__);
	$options->setIsRemoteEnabled(true);
	$dompdf = new Dompdf($options);
	$dompdf->loadHtml($html);
	$dompdf->setPaper('A4', 'portrait');
	$dompdf->render();

	$filename = 'quotation_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['quotation_no'] ?: 'Q') . '.pdf';
	$pdfOutput = $dompdf->output();

	// Stream as download to AJAX as a blob
	header('Content-Type: application/pdf');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('X-Filename: ' . $filename);
	echo $pdfOutput;
	exit;
}
catch (Throwable $e) {
	http_response_code(500);
	header('Content-Type: text/plain');
	echo 'Error generating PDF: ' . $e->getMessage();
	if (function_exists('error_log')) {
		error_log('[quotation-generator] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
	}
	exit;
}

