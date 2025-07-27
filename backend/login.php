
<?php
// CORS: pastikan origin sesuai frontend
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:5173'); // ganti sesuai port frontend
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
require_once('spk_functions.php');
$pdo = getPDO();

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$nisn = $data['nisn'] ?? '';
$role = $data['role'] ?? '';

if ($role === 'admin' || $role === 'waka') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND role=?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();
    $valid = false;
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $valid = true;
        } else if ($password === $user['password']) {
            // Fallback: password belum di-hash
            $valid = true;
        }
    }
    if ($valid) {
        $_SESSION['user'] = ['id'=>$user['id'], 'role'=>$user['role'], 'username'=>$user['username']];
        echo json_encode(['success'=>true, 'role'=>$user['role']]);
    } else {
        echo json_encode(['success'=>false, 'msg'=>'Login gagal!']);
    }
} elseif ($role === 'siswa') {
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE nisn=?");
    $stmt->execute([$nisn]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user'] = ['id'=>$user['id'], 'role'=>'siswa', 'nisn'=>$user['nisn']];
        echo json_encode(['success'=>true, 'role'=>'siswa']);
    } else {
        echo json_encode(['success'=>false, 'msg'=>'NISN tidak ditemukan!']);
    }
} else {
    echo json_encode(['success'=>false, 'msg'=>'Role tidak valid!']);
}
