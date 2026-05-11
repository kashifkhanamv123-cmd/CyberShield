<?php
/**
 * Gemini AI Chatbot Backend (PHP)
 * Supports:
 * - Text chat
 * - Image upload
 * - Gemini 2.0 Flash
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

/* =========================
   1. Gemini API Key
========================= */

define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');

/* =========================
   2. Read POST Data
========================= */

$json = file_get_contents('php://input');

$data = json_decode($json, true);

$userMessage = isset($data['message'])
    ? trim($data['message'])
    : '';

$imageData = isset($data['image'])
    ? $data['image']
    : null;

if (empty($userMessage) && empty($imageData)) {

    echo json_encode([
        'error' => 'Message or image is required'
    ]);

    exit;
}

/* =========================
   3. Gemini API Settings
========================= */

$apiVersion = 'v1beta';

$model = 'gemini-2.0-flash';

$url =
    "https://generativelanguage.googleapis.com/{$apiVersion}/models/{$model}:generateContent?key="
    . GEMINI_API_KEY;

/* =========================
   4. Build Parts Array
========================= */

$parts = [];

/* ---- Text Message ---- */

if (!empty($userMessage)) {

    $parts[] = [
        "text" => $userMessage
    ];
}

/* ---- Image Upload ---- */

if ($imageData) {

    // Remove base64 image header
    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {

        $imageData = substr($imageData, strpos($imageData, ',') + 1);

        $mimeType = "image/" . strtolower($type[1]);

    } else {

        $mimeType = "image/jpeg";
    }

    $parts[] = [
        "inlineData" => [
            "mimeType" => $mimeType,
            "data" => $imageData
        ]
    ];
}

/* =========================
   5. Gemini Payload
========================= */

$payload = [

    "contents" => [
        [
            "parts" => $parts
        ]
    ],

    "system_instruction" => [
        "parts" => [
            [
                "text" =>
                    "You are Luna, an elite cybersecurity AI assistant for CyberShield. Be professional, intelligent, friendly, and helpful. Help users understand cybersecurity concepts, labs, and platform navigation."
            ]
        ]
    ]
];

/* =========================
   6. Initialize cURL
========================= */

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

/* =========================
   7. Execute Request
========================= */

$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$error = curl_error($ch);

curl_close($ch);

/* =========================
   8. Handle Errors
========================= */

if ($error) {

    echo json_encode([
        'error' => 'cURL Error: ' . $error
    ]);

    exit;
}

if ($httpCode !== 200) {

    $errorData = json_decode($response, true);

    $message =
        isset($errorData['error']['message'])
        ? $errorData['error']['message']
        : 'Unknown Gemini API Error';

    echo json_encode([
        'error' => 'Gemini API Error (' . $httpCode . '): ' . $message,
        'debug' => $errorData
    ]);

    exit;
}

/* =========================
   9. Decode Gemini Response
========================= */

$responseData = json_decode($response, true);

/* =========================
   10. Extract AI Reply
========================= */

if (
    isset(
        $responseData['candidates'][0]['content']['parts'][0]['text']
    )
) {

    $botReply =
        $responseData['candidates'][0]['content']['parts'][0]['text'];

    echo json_encode([
        'reply' => $botReply
    ]);

} else {

    echo json_encode([
        'error' => 'Invalid response structure from Gemini API',
        'debug' => $responseData
    ]);
}
?>