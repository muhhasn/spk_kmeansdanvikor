
<?php
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:5173'); // ganti sesuai port frontend
}
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');
session_start();
session_destroy();
echo json_encode(['success'=>true]);
