-- Setup database ta_db
CREATE DATABASE IF NOT EXISTS ta_db;
USE ta_db;

-- Tabel siswa
CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(10) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    asal_sekolah VARCHAR(100) NOT NULL,
    nilai_sem1 FLOAT NOT NULL,
    nilai_sem2 FLOAT NOT NULL,
    nilai_sem3 FLOAT NOT NULL,
    nilai_sem4 FLOAT NOT NULL,
    nilai_sem5 FLOAT NOT NULL
);

-- Tabel clustering
CREATE TABLE IF NOT EXISTS clustering (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    cluster INT NOT NULL,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id) ON DELETE CASCADE
);

-- Tabel vikor
CREATE TABLE IF NOT EXISTS vikor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    Si FLOAT NOT NULL,
    Ri FLOAT NOT NULL,
    Qi FLOAT NOT NULL,
    ranking INT NOT NULL,
    status_kelulusan VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id) ON DELETE CASCADE
);

-- Tabel hasil seleksi
CREATE TABLE IF NOT EXISTS hasil_seleksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    ranking INT NOT NULL,
    status_kelulusan VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id) ON DELETE CASCADE
);
