<?php
/**
 * AURA Atelier - x02.me Image Upload & WebP Compressor API Handler
 * Uploads images to https://x02.me/ using API after converting & compressing to WebP.
 */

// Enable session & admin auth check if applicable
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers for JSON response & CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, x-api-key, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only POST requests are accepted']);
    exit;
}

// Load settings for optional x02_api_key
$settingsDb = [];
$settingsFile = __DIR__ . '/../database/settings.json';
if (file_exists($settingsFile)) {
    $settingsDb = json_decode(file_get_contents($settingsFile), true) ?: [];
}

$defaultApiKey = '36f36ce6fa844e93bda76bb9255070b4';
$x02ApiKey = trim($_POST['x02_api_key'] ?? ($settingsDb['x02_api_key'] ?? getenv('X02_API_KEY') ?: $defaultApiKey));
if (empty($x02ApiKey)) {
    $x02ApiKey = $defaultApiKey;
}

// Verify uploaded file
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['file']['error'] ?? 'no_file';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No valid file uploaded. Error code: ' . $errorCode]);
    exit;
}

$uploadedFile = $_FILES['file'];
$tmpPath = $uploadedFile['tmp_name'];
$originalName = $uploadedFile['name'];
$originalSize = $uploadedFile['size'];
$mimeType = mime_content_type($tmpPath) ?: $uploadedFile['type'];

// Check if file is already a WebP
$isWebp = ($mimeType === 'image/webp') || (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) === 'webp');

$finalUploadPath = $tmpPath;
$finalUploadName = pathinfo($originalName, PATHINFO_FILENAME) . '.webp';
$tempWebpCreated = false;

// If not WebP, attempt server-side conversion to WebP using GD if available
if (!$isWebp && function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
    $rawImage = @file_get_contents($tmpPath);
    if ($rawImage) {
        $imgResource = @imagecreatefromstring($rawImage);
        if ($imgResource) {
            // Resize if excessively large (max 1920x1920)
            $w = imagesx($imgResource);
            $h = imagesy($imgResource);
            $maxDim = 1920;
            if ($w > $maxDim || $h > $maxDim) {
                if ($w > $h) {
                    $newH = (int)round(($h * $maxDim) / $w);
                    $newW = $maxDim;
                } else {
                    $newW = (int)round(($w * $maxDim) / $h);
                    $newH = $maxDim;
                }
                $resized = imagecreatetruecolor($newW, $newH);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $imgResource, 0, 0, 0, 0, $newW, $newH, $w, $h);
                imagedestroy($imgResource);
                $imgResource = $resized;
            }

            $tempWebpPath = sys_get_temp_dir() . '/x02_' . uniqid() . '.webp';
            if (@imagewebp($imgResource, $tempWebpPath, 82)) {
                $finalUploadPath = $tempWebpPath;
                $tempWebpCreated = true;
            }
            imagedestroy($imgResource);
        }
    }
}

$compressedSize = filesize($finalUploadPath);

// Prepare cURL request to https://up.x02.me/api/upload?format=json
$targetUrl = 'https://up.x02.me/api/upload?format=json';
$ch = curl_init();

$curlFile = new CURLFile($finalUploadPath, 'image/webp', $finalUploadName);
$postFields = [
    'file' => $curlFile,
    'expiry' => '' // Never expires for store products
];

$headers = [
    'x-api-key: ' . $x02ApiKey
];

curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Clean up temporary server-side webp if created
if ($tempWebpCreated && file_exists($tempWebpPath)) {
    @unlink($tempWebpPath);
}

if ($curlError) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to reach up.x02.me upload API: ' . $curlError
    ]);
    exit;
}

// Parse response from x02.me
$uploadedUrl = null;
$cleanResponse = trim($response);

// 1. Direct URL text check
if (preg_match('/^https?:\/\/[^\s"\']+/i', $cleanResponse, $matches)) {
    $uploadedUrl = $matches[0];
} else {
    // 2. JSON check
    $json = json_decode($cleanResponse, true);
    if (is_array($json)) {
        if (!empty($json['url'])) {
            $uploadedUrl = $json['url'];
        } elseif (!empty($json['file'])) {
            $uploadedUrl = is_string($json['file']) ? $json['file'] : ($json['file']['url'] ?? null);
        } elseif (!empty($json['direct_url'])) {
            $uploadedUrl = $json['direct_url'];
        } elseif (!empty($json['link'])) {
            $uploadedUrl = $json['link'];
        } elseif (!empty($json['data']['url'])) {
            $uploadedUrl = $json['data']['url'];
        } elseif (!empty($json['files'][0])) {
            $uploadedUrl = is_string($json['files'][0]) ? $json['files'][0] : ($json['files'][0]['url'] ?? null);
        } elseif (!empty($json['result']['url'])) {
            $uploadedUrl = $json['result']['url'];
        } elseif (isset($json['error'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $json['error'] . (isset($json['message']) ? ': ' . $json['message'] : ''),
                'raw' => $cleanResponse
            ]);
            exit;
        }
    }
}

if (!$uploadedUrl) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'x02.me did not return a recognizable file URL',
        'raw' => substr($cleanResponse, 0, 500)
    ]);
    exit;
}

// Successful response
echo json_encode([
    'success' => true,
    'url' => $uploadedUrl,
    'original_name' => $originalName,
    'filename' => $finalUploadName,
    'format' => 'webp',
    'original_size' => $originalSize,
    'compressed_size' => $compressedSize,
    'saved_percent' => $originalSize > 0 ? max(0, round((1 - ($compressedSize / $originalSize)) * 100)) : 0
]);
