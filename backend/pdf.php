
<?php
// File: pdf.php
// Pastikan file fpdf.php sudah ada di folder backend (download dari http://www.fpdf.org/)
if (!file_exists('fpdf.php')) {
    die('File fpdf.php tidak ditemukan di folder backend!');
}
require_once('fpdf.php');
require_once('spk_functions.php');

$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Laporan Hasil Seleksi Siswa Baru',0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Ln(4);

// Header tabel
$pdf->SetFont('Arial','B',11);
$pdf->Cell(10,8,'No',1,0,'C');
$pdf->Cell(40,8,'Nama',1,0,'C');
$pdf->Cell(30,8,'NISN',1,0,'C');
$pdf->Cell(50,8,'Asal Sekolah',1,0,'C');
$pdf->Cell(30,8,'Nilai Q',1,0,'C');
$pdf->Cell(20,8,'Ranking',1,0,'C');
$pdf->Cell(30,8,'Keterangan',1,1,'C');
$pdf->SetFont('Arial','',11);

// Ambil data hasil seleksi dari DB
$pdo = getPDO();
$stmt = $pdo->query("SELECT s.nama, s.nisn, s.asal_sekolah, v.Qi as q, v.ranking, v.status_kelulusan FROM vikor v JOIN siswa s ON v.id_siswa = s.id ORDER BY v.ranking ASC");
$data = $stmt->fetchAll();


$no = 1;
foreach ($data as $row) {
    $cellHeight = 7;
    $startX = $pdf->GetX();
    $startY = $pdf->GetY();

    // Hitung tinggi baris maksimal (jika MultiCell Asal Sekolah lebih dari 1 baris)
    $asalSekolahWidth = 50;
    $asalSekolahLines = $pdf->GetStringWidth($row['asal_sekolah']) > $asalSekolahWidth ? ceil($pdf->GetStringWidth($row['asal_sekolah']) / $asalSekolahWidth * 1.2) : 1;
    $maxHeight = $cellHeight * $asalSekolahLines;

    $pdf->Cell(10, $maxHeight, $no++, 1, 0, 'C');
    $pdf->Cell(40, $maxHeight, $row['nama'], 1, 0);
    $pdf->Cell(30, $maxHeight, $row['nisn'], 1, 0);

    // MultiCell untuk Asal Sekolah
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell($asalSekolahWidth, $cellHeight, $row['asal_sekolah'], 1, 'L');
    // Set X ke kolom berikutnya, Y ke awal baris
    $pdf->SetXY($x + $asalSekolahWidth, $y);

    $pdf->Cell(30, $maxHeight, number_format($row['q'],6), 1, 0, 'C');
    $pdf->Cell(20, $maxHeight, $row['ranking'], 1, 0, 'C');
    $pdf->Cell(30, $maxHeight, $row['status_kelulusan'], 1, 1, 'C');
}

$pdf->Output('D', 'laporan_hasil_seleksi.pdf');
