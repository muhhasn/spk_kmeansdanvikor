
// File: pdf.php
// Pastikan file fpdf.php sudah ada di folder backend (download dari http://www.fpdf.org/)
require_once('fpdf.php');
require_once('spk_functions.php');

$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();
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
    $pdf->Cell(10,8,$no++,1,0,'C');
    $pdf->Cell(40,8,$row['nama'],1,0);
    $pdf->Cell(30,8,$row['nisn'],1,0);
    $pdf->Cell(50,8,$row['asal_sekolah'],1,0);
    $pdf->Cell(30,8,number_format($row['q'],6),1,0,'C');
    $pdf->Cell(20,8,$row['ranking'],1,0,'C');
    $pdf->Cell(30,8,$row['status_kelulusan'],1,1,'C');
}

$pdf->Output('D', 'laporan_hasil_seleksi.pdf');
