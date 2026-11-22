CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
);

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_wilayah VARCHAR(100) NOT NULL
);

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    harga INT NOT NULL,
    gambar VARCHAR(255) NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (nama_kategori) VALUES 
('Tukang & Renovasi'), ('Jahit & Permak'), ('Kebersihan'), ('Fotografi & Desain');

INSERT INTO locations (nama_wilayah) VALUES 
('Purwokerto Utara'), ('Purwokerto Selatan'), ('Purwokerto Timur'), ('Purbalingga');
