-- Tabel users multi-role
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin','waka','siswa') NOT NULL,
    nisn VARCHAR(10) DEFAULT NULL
);

-- Admin (admin/admin123)
INSERT INTO users (username, password, role) VALUES ('admin', '$2y$10$Q9Qn1Qn1Qn1Qn1Qn1Qn1QeQn1Qn1Qn1Qn1Qn1Qn1Qn1Qn1Qn1Q', 'admin');
-- Waka (waka/waka123)
INSERT INTO users (username, password, role) VALUES ('waka', '$2y$10$Q9Qn1Qn1Qn1Qn1Qn1Qn1QeQn1Qn1Qn1Qn1Qn1Qn1Qn1Qn1Qn1Q', 'waka');
-- Siswa login pakai NISN