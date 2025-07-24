
<?php
// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once 'spk_functions.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['success' => false];

switch ($action) {
    case 'vikor':
        // Proses VIKOR ranking
        $top = isset($_GET['top']) ? intval($_GET['top']) : 10;
        $result = vikorRanking($top);
        if ($result === false) {
            $response['success'] = false;
            $response['msg'] = 'VIKOR gagal: Data siswa di cluster 2/3 tidak ditemukan. Jalankan K-Means dulu.';
        } else {
            $response['success'] = true;
        }
        break;
    case 'deleteall':
        // Hapus semua data terkait dulu, baru siswa
        if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $pdo = getPDO();
            $pdo->query("DELETE FROM clustering");
            $pdo->query("DELETE FROM vikor");
            $pdo->query("DELETE FROM hasil_seleksi");
            $pdo->query("DELETE FROM siswa");
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['msg'] = 'Metode request tidak didukung.';
        }
        break;
    case 'import':
        // Import data siswa dari CSV
        if (isset($_FILES['file'])) {
            $tmp = $_FILES['file']['tmp_name'];
            $result = importSiswaCSV($tmp);
            $response['success'] = $result;
        } else {
            $response['error'] = 'File tidak ditemukan.';
        }
        break;
    case 'kmeans':
        // Proses K-Means clustering
        $result = kmeansClustering();
        if (is_array($result)) {
            $response = $result;
        } else {
            $response['success'] = $result;
        }
        break;
    // ...existing code...
    case 'clustering':
        // Ambil hasil clustering
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT s.nama, c.cluster FROM clustering c JOIN siswa s ON c.id_siswa = s.id ORDER BY c.cluster, s.nama");
        $response['success'] = true;
        $response['data'] = $stmt->fetchAll();
        break;
    case 'vikorhasil':
        // Ambil hasil akhir VIKOR
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT s.id, s.nama, s.nisn, s.asal_sekolah, v.Qi as q, v.ranking FROM vikor v JOIN siswa s ON v.id_siswa = s.id ORDER BY v.ranking ASC");
        $response['success'] = true;
        $response['data'] = $stmt->fetchAll();
        break;
    case 'siswa':
        $pdo = getPDO();
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET') {
            // Ambil semua data siswa
            $stmt = $pdo->query("SELECT id, nisn, nama, asal_sekolah, nilai_sem1, nilai_sem2, nilai_sem3, nilai_sem4, nilai_sem5 FROM siswa ORDER BY id ASC");
            $response['success'] = true;
            $response['data'] = $stmt->fetchAll();
        } elseif ($method === 'POST') {
            // Tambah data siswa dengan validasi NISN
            $input = json_decode(file_get_contents('php://input'), true);
            require_once 'spk_functions.php';
            $result = tambahSiswa($input);
            $response = $result;
        } elseif ($method === 'PUT') {
            // Update data siswa (termasuk NISN)
            $input = json_decode(file_get_contents('php://input'), true);
            require_once 'spk_functions.php';
            $result = updateSiswa($input);
            $response = $result;
        } elseif ($method === 'DELETE') {
            // Hapus data siswa dan data terkait via fungsi deleteSiswa, id bisa dari body atau query string
            require_once 'spk_functions.php';
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['id'])) {
                $id = $input['id'];
            } elseif (isset($_GET['id'])) {
                $id = $_GET['id'];
            } else {
                $id = null;
            }
            if ($id !== null) {
                $result = deleteSiswa($id);
                $response = $result;
            } else {
                $response = ['success' => false, 'msg' => 'ID siswa tidak ditemukan'];
            }
        } else {
            $response['error'] = 'Metode tidak didukung.';
        }
        break;
    default:
        $response['error'] = 'Aksi tidak valid.';
}

echo json_encode($response);
