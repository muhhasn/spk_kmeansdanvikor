<?php
// Fungsi untuk mendapatkan bobot normalisasi (total 1, 0.2 tiap semester)
function getBobotNormalisasi($n = 5) {
    $bobot = [];
    for ($i = 0; $i < $n; $i++) {
        $bobot[] = 1 / $n; // 0.2 jika n=5
    }
    return $bobot;
}
// Koneksi database
function getPDO() {
    $host = 'localhost';
    $db   = 'ta_db'; // ganti ke database baru
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    return new PDO($dsn, $user, $pass, $options);
}

// Import data siswa dari CSV
function importSiswaCSV($csvFile) {
    $pdo = getPDO();
    $handle = fopen($csvFile, 'r');
    if ($handle === false) return false;
    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        // Asumsi urutan: nisn, nama, asal_sekolah, nilai_sem1, nilai_sem2, nilai_sem3, nilai_sem4, nilai_sem5
        $nisn = $row[0];
        // Validasi format NISN
        if (!preg_match('/^\d{10}$/', $nisn)) continue;
        // Validasi NISN unik
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = ?");
        $stmt->execute([$nisn]);
        if ($stmt->fetchColumn() > 0) continue;
        $stmt = $pdo->prepare("INSERT INTO siswa (nisn, nama, asal_sekolah, nilai_sem1, nilai_sem2, nilai_sem3, nilai_sem4, nilai_sem5) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7]]);
    }
    fclose($handle);
    return true;
}

// Tambah data siswa manual
function tambahSiswa($data) {
    $pdo = getPDO();
    // Validasi NISN unik
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = ?");
    $stmt->execute([$data['nisn']]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'msg' => 'NISN sudah digunakan siswa lain!'];
    }
    // Validasi format NISN
    if (!preg_match('/^\d{10}$/', $data['nisn'])) {
        return ['success' => false, 'msg' => 'Format NISN harus 10 digit angka!'];
    }
    $stmt = $pdo->prepare("INSERT INTO siswa (nisn, nama, asal_sekolah, nilai_sem1, nilai_sem2, nilai_sem3, nilai_sem4, nilai_sem5) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['nisn'],
        $data['nama'],
        $data['asal_sekolah'],
        $data['nilai_sem1'],
        $data['nilai_sem2'],
        $data['nilai_sem3'],
        $data['nilai_sem4'],
        $data['nilai_sem5']
    ]);
    return ['success' => true];
}

// Fuzzifikasi nilai ke skala 0-1
function fuzzify($nilai, $min=0, $max=100) {
    return $nilai / 100;
}

// Proses K-Means Clustering
function kmeansClustering($k = 3, $maxIter = 100) {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, nilai_sem1, nilai_sem2, nilai_sem3, nilai_sem4, nilai_sem5 FROM siswa");
    $data = [];
    while ($row = $stmt->fetch()) {
        $fuzzy = [
            fuzzify($row['nilai_sem1']),
            fuzzify($row['nilai_sem2']),
            fuzzify($row['nilai_sem3']),
            fuzzify($row['nilai_sem4']),
            fuzzify($row['nilai_sem5'])
        ];
        $data[] = ['id' => $row['id'], 'nilai' => $fuzzy];
    }

    // Inisialisasi centroid: data ke-1, ke-2, dan ke-7
    $centroids = [];
    $centroidIdxs = [0, 1, 6];
    foreach ($centroidIdxs as $i) {
        if (isset($data[$i])) {
            $centroids[] = $data[$i]['nilai'];
        }
    }

    $maxIter = 20; // optimasi: batasi iterasi
    $isConverged = false;
    $finalIter = 0;
    for ($iter = 0; $iter < $maxIter; $iter++) {
        $clusters = array_fill(0, $k, []);
        // Assign data ke cluster terdekat
        foreach ($data as $item) {
            $distances = [];
            foreach ($centroids as $centroid) {
                $dist = 0;
                for ($i = 0; $i < 5; $i++) {
                    $dist += pow($item['nilai'][$i] - $centroid[$i], 2);
                }
                $distances[] = sqrt($dist);
            }
            $clusterIdx = array_search(min($distances), $distances);
            $clusters[$clusterIdx][] = $item;
        }
        // Update centroid
        $newCentroids = [];
        foreach ($clusters as $cluster) {
            if (count($cluster) == 0) {
                $newCentroids[] = $centroids[array_rand($centroids)];
                continue;
            }
            $sum = array_fill(0, 5, 0);
            foreach ($cluster as $item) {
                for ($i = 0; $i < 5; $i++) {
                    $sum[$i] += $item['nilai'][$i];
                }
            }
            $centroid = array_map(function($val) use ($cluster) { return $val / count($cluster); }, $sum);
            $newCentroids[] = $centroid;
        }
        // Cek konvergensi (optimasi: break jika centroid tidak berubah)
        $isConverged = true;
        for ($i = 0; $i < $k; $i++) {
            if (array_map('floatval', $centroids[$i]) !== array_map('floatval', $newCentroids[$i])) {
                $isConverged = false;
                break;
            }
        }
        $finalIter = $iter + 1;
        if ($isConverged) break;
        $centroids = $newCentroids;
    }
    // Simpan hasil cluster ke DB hanya jika sudah konvergen
    if ($isConverged) {
        $pdo->query("DELETE FROM clustering");
        foreach ($clusters as $idx => $cluster) {
            foreach ($cluster as $item) {
                $stmt = $pdo->prepare("INSERT INTO clustering (id_siswa, cluster) VALUES (?, ?)");
                $stmt->execute([$item['id'], $idx+1]); // cluster 1,2,3
            }
        }
        return ['success' => true, 'iterasi' => $finalIter, 'konvergen' => true];
    } else {
        return ['success' => false, 'iterasi' => $finalIter, 'konvergen' => false];
    }
}

// Proses VIKOR untuk siswa di cluster 2 dan 3
function vikorRanking($topLulus = 10) {
    $pdo = getPDO();
    // Ambil siswa dari cluster 2 dan 3 saja (sesuai laporan manual)
    $stmt = $pdo->query("SELECT s.id, s.nilai_sem1, s.nilai_sem2, s.nilai_sem3, s.nilai_sem4, s.nilai_sem5 FROM siswa s JOIN clustering c ON s.id = c.id_siswa WHERE c.cluster IN (2,3)");
    $siswa = [];
    while ($row = $stmt->fetch()) {
        $fuzzy = [
            fuzzify($row['nilai_sem1']),
            fuzzify($row['nilai_sem2']),
            fuzzify($row['nilai_sem3']),
            fuzzify($row['nilai_sem4']),
            fuzzify($row['nilai_sem5'])
        ];
        $siswa[] = ['id' => $row['id'], 'nilai' => $fuzzy];
    }
    $n = count($siswa);
    if ($n == 0) return false;
    $bobot = getBobotNormalisasi(5); // [0.2, 0.2, 0.2, 0.2, 0.2]
    // F* dan F-
    $fstar = [];
    $fmin = [];
    for ($i = 0; $i < 5; $i++) {
        $fstar[$i] = max(array_map(function($s) use ($i) { return $s['nilai'][$i]; }, $siswa));
        $fmin[$i] = min(array_map(function($s) use ($i) { return $s['nilai'][$i]; }, $siswa));
    }
    // Hitung Si, Ri
    $result = [];
    foreach ($siswa as $row) {
        $Si = 0;
        $Ri = -INF;
        for ($i = 0; $i < 5; $i++) {
            $val = $bobot[$i] * ($fstar[$i] - $row['nilai'][$i]) / ($fstar[$i] - $fmin[$i]);
            $Si += $val;
            if ($val > $Ri) $Ri = $val;
        }
        $result[] = ['id' => $row['id'], 'Si' => $Si, 'Ri' => $Ri];
    }
    // Hitung Qi dengan proteksi pembagian nol
    $Sstar = min(array_column($result, 'Si'));
    $Smin = max(array_column($result, 'Si'));
    $Rstar = min(array_column($result, 'Ri'));
    $Rmin = max(array_column($result, 'Ri'));
    $denomS = ($Smin - $Sstar) == 0 ? 1 : ($Smin - $Sstar);
    $denomR = ($Rmin - $Rstar) == 0 ? 1 : ($Rmin - $Rstar);
    $Q = [];
    $v = 0.5;
    foreach ($result as $row) {
        $Qi = $v * (($row['Si'] - $Sstar) / $denomS) + (1-$v) * (($row['Ri'] - $Rstar) / $denomR);
        $Q[] = ['id' => $row['id'], 'Si' => $row['Si'], 'Ri' => $row['Ri'], 'Qi' => $Qi];
    }
    // Ranking dan status kelulusan
    usort($Q, function($a, $b) { return $a['Qi'] <=> $b['Qi']; });
    $pdo->query("DELETE FROM vikor");
    $pdo->query("DELETE FROM hasil_seleksi");
    foreach ($Q as $rank => $row) {
        $status = ($rank < $topLulus) ? 'Lulus' : 'Tidak Lulus';
        $stmt = $pdo->prepare("INSERT INTO vikor (id_siswa, Si, Ri, Qi, ranking, status_kelulusan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$row['id'], $row['Si'], $row['Ri'], $row['Qi'], $rank+1, $status]);
        $stmt2 = $pdo->prepare("INSERT INTO hasil_seleksi (id_siswa, ranking, status_kelulusan) VALUES (?, ?, ?)");
        $stmt2->execute([$row['id'], $rank+1, $status]);
    }
    return true;
}

// Update data siswa
function updateSiswa($data) {
    $pdo = getPDO();
    // Validasi NISN unik (selain milik id yang diedit)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = ? AND id != ?");
    $stmt->execute([$data['nisn'], $data['id']]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'msg' => 'NISN sudah digunakan siswa lain!'];
    }
    // Validasi format NISN
    if (!preg_match('/^\d{10}$/', $data['nisn'])) {
        return ['success' => false, 'msg' => 'Format NISN harus 10 digit angka!'];
    }
    $stmt = $pdo->prepare("UPDATE siswa SET nisn=?, nama=?, asal_sekolah=?, nilai_sem1=?, nilai_sem2=?, nilai_sem3=?, nilai_sem4=?, nilai_sem5=? WHERE id=?");
    $stmt->execute([
        $data['nisn'],
        $data['nama'],
        $data['asal_sekolah'],
        $data['nilai_sem1'],
        $data['nilai_sem2'],
        $data['nilai_sem3'],
        $data['nilai_sem4'],
        $data['nilai_sem5'],
        $data['id']
    ]);
    return ['success' => true];
}

// Hapus data siswa beserta data terkait
function deleteSiswa($id) {
    $pdo = getPDO();
    // Hapus data terkait dulu
    $delClustering = $pdo->prepare("DELETE FROM clustering WHERE id_siswa = ?");
    $delClustering->execute([$id]);
    $delVikor = $pdo->prepare("DELETE FROM vikor WHERE id_siswa = ?");
    $delVikor->execute([$id]);
    $delHasil = $pdo->prepare("DELETE FROM hasil_seleksi WHERE id_siswa = ?");
    $delHasil->execute([$id]);
    // Baru hapus data utama
    $delSiswa = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
    $delSiswa->execute([$id]);
    $deletedRows = $delSiswa->rowCount();
    if ($deletedRows > 0) {
        return ['success' => true, 'deleted' => $deletedRows];
    } else {
        return ['success' => false, 'msg' => 'ID siswa tidak ditemukan atau gagal dihapus!', 'deleted' => 0];
    }
}
