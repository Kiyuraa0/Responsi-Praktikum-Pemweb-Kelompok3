<?php
require_once __DIR__ . '/config.php';
$user = require_role('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $judul       = trim($_POST['judul'] ?? '');
        $category_id = (int)$_POST['category_id'];
        $location_id = (int)$_POST['location_id'];
        $harga       = (int)$_POST['harga'];
        $deskripsi   = trim($_POST['deskripsi'] ?? '');

        if ($judul === '' || $deskripsi === '' || $harga < 1000) {
            throw new RuntimeException('Lengkapi semua data layanan dengan benar.');
        }

        $gambar = save_image($_FILES['gambar']);
        $stmt = db()->prepare('
            INSERT INTO services (provider_id, category_id, location_id, judul, deskripsi, harga, gambar) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$user['id'], $category_id, $location_id, $judul, $deskripsi, $harga, $gambar]);

        flash('Layanan baru berhasil ditambahkan.');
        redirect(BASE_URL . '/layanan_saya.php');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'err');
    }
}

$categories = db()->query('SELECT * FROM categories ORDER BY nama_kategori ASC')->fetchAll();
$locations  = db()->query('SELECT * FROM locations ORDER BY nama_wilayah ASC')->fetchAll();
require_once __DIR__ . '/partials/header.php';
?>
<div class="container">
    <h2>Tambah Layanan Baru</h2>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <label>Judul Layanan</label><br>
        <input type="text" name="judul" required><br><br>

        <label>Kategori</label><br>
        <select name="category_id" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id']; ?>"><?= e($c['nama_kategori']); ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Area / Lokasi Layanan</label><br>
        <select name="location_id" required>
            <option value="">-- Pilih Lokasi --</option>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id']; ?>"><?= e($l['nama_wilayah']); ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Harga (Rp)</label><br>
        <input type="number" name="harga" min="1000" required><br><br>

        <label>Deskripsi Layanan</label><br>
        <textarea name="deskripsi" rows="5" required></textarea><br><br>

        <label>Foto Layanan (Maks 2 MB: JPG/PNG/WEBP)</label><br>
        <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp" required><br><br>

        <button type="submit" class="btn">Simpan Layanan</button>
        <a href="<?= BASE_URL; ?>/layanan_saya.php" class="btn ghost">Batal</a>
    </form>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
