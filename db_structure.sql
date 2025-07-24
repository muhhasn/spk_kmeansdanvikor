CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    asal_sekolah VARCHAR(100) NOT NULL,
    nilai_sem1 FLOAT NOT NULL,
    nilai_sem2 FLOAT NOT NULL,
    nilai_sem3 FLOAT NOT NULL,
    nilai_sem4 FLOAT NOT NULL,
    nilai_sem5 FLOAT NOT NULL
);

CREATE TABLE clustering (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT,
    cluster INT,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id)
);

CREATE TABLE vikor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT,
    Si FLOAT,
    Ri FLOAT,
    Qi FLOAT,
    ranking INT,
    status_kelulusan VARCHAR(20),
    FOREIGN KEY (id_siswa) REFERENCES siswa(id)
);

CREATE TABLE hasil_seleksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT,
    ranking INT,
    status_kelulusan VARCHAR(20),
    FOREIGN KEY (id_siswa) REFERENCES siswa(id)
);
