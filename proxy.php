<?php
/**
 * Binance Futures CORS Proxy for Trading Signal Dashboard
 * Relays API requests from localhost browser to fapi.binance.com
 */

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

// Suppress raw error outputs to keep response JSON valid
ini_set('display_errors', 0);
error_reporting(E_ALL);

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Validate and build Binance Futures API target URLs
switch ($action) {
    case 'ticker':
        $url = 'https://fapi.binance.com/fapi/v1/ticker/24hr?symbol=BTCUSDT';
        break;
    case 'klines':
        $interval = isset($_GET['interval']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_GET['interval']) : '1h';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $url = "https://fapi.binance.com/fapi/v1/klines?symbol=BTCUSDT&interval={$interval}&limit={$limit}";
        break;
    case 'depth':
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        $url = "https://fapi.binance.com/fapi/v1/depth?symbol=BTCUSDT&limit={$limit}";
        break;
    case 'premiumIndex':
        $url = 'https://fapi.binance.com/fapi/v1/premiumIndex?symbol=BTCUSDT';
        break;
    default:
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid or missing action parameter. Allowed: ticker, klines, depth, premiumIndex"
        ]);
        exit;
}

// Execute request using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AntigravityTradingSignal/1.0');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass local certificate validation constraints

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    http_response_code(502);
    echo json_encode([
        "success" => false,
        "error" => "cURL Error: " . $error_msg,
        "target_url" => $url
    ]);
} else {
    // Forward the HTTP response code from Binance
    http_response_code($httpCode);
    echo $response;
}

curl_close($ch);
?>
