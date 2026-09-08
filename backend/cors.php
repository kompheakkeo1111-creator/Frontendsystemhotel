<?php
// CORS Configuration for cross-origin requests from GitHub Pages
// Update the allowed origin to match your GitHub Pages URL

$allowedOrigins = [
    'https://kompheakkeo1111-creator.github.io',
    'http://localhost:3000', // For local development
    'http://localhost:8080', // For local development
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
