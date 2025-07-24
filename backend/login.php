<?php
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
    if ($user && password_verify($password, $user['password'])) {
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
