<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add logging
error_log("PDF Generation Script Started");

require_once __DIR__ . '/vendor/autoload.php';

// Handle CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://invoicebutter.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rawInput = file_get_contents('php://input');
        error_log("Raw input received: " . $rawInput);

        $data = json_decode($rawInput, true);
        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        if (!$data || !isset($data['html'])) {
            error_log("Invalid input data: " . print_r($data, true));
            throw new Exception('Invalid input: html field is required');
        }

        // Generate PDF
        $mpdf = new \Mpdf\Mpdf([
            'debug' => true,
            'debugfonts' => true
        ]);

        error_log("Attempting to write HTML to PDF");
        $mpdf->WriteHTML($data['html']);

        error_log("Converting PDF to string");
        $pdfContent = $mpdf->Output('', 'S');

        error_log("PDF Generation Successful");
        echo json_encode([
            'pdf' => base64_encode($pdfContent),
            'success' => true
        ]);

    } catch (Exception $e) {
        error_log("Error in PDF generation: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());

        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'success' => false
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
        'success' => false
    ]);
}
