-- Tabel users untuk admin & waka
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','waka') NOT NULL
);

-- Tabel siswa (pastikan sudah ada kolom nisn dan password jika ingin login siswa lebih aman)
ALTER TABLE siswa ADD COLUMN password VARCHAR(255) NULL;

-- Contoh data admin dan waka (password hash, ganti sesuai kebutuhan)
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$adminhash', 'admin'),
('waka', '$2y$10$wakahash', 'waka');

-- Siswa login dengan nisn (atau password jika ingin lebih aman)
-- Data siswa sudah ada di tabel siswa
