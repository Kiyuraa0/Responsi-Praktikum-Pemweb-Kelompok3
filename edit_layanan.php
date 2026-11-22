<?php
require_once __DIR__ . '/config.php';
$user = require_role('penyedia');

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM services WHERE id = ? AND provider_id = ?');
$stmt->execute([$id, $user['id']]);
$service = $stmt->fetch();

if (!$service) {
    flash('Layanan tidak ditemukan.', 'err');
    redirect(BASE_URL . '/layanan_saya.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $judul       = trim($_POST['judul'] ?? '');
        $category_id = (int)$_POST['category_id'];
        $location_id = (int)$_POST['location_id'];
        $harga       = (int)$_POST['harga'];
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $gambar      = $service['gambar'];

        if (!empty($_FILES['gambar']['name'])) {
            $gambar_baru = save_image($_FILES['gambar']);
            delete_image($gambar);
            $gambar = $gambar_baru;
        }

        $upd = db()->prepare('
            UPDATE services 
            SET judul = ?, category_id = ?, location_id = ?, harga = ?, deskripsi = ?, gambar = ? 
            WHERE id = ? AND provider_id = ?
        ');
        $upd->execute([$judul, $category_id, $location_id, $harga, $deskripsi, $gambar, $id, $user['id']]);

        flash('Layanan berhasil diperbarui.');
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
    <h2>Edit Layanan</h2>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <label>Judul Layanan</label><br>
        <input type="text" name="judul" value="<?= e($service['judul']); ?>" required><br><br>

        <label>Kategori</label><br>
        <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id']; ?>" <?= $c['id'] == $service['category_id'] ? 'selected' : ''; ?>>
                    <?= e($c['nama_kategori']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Lokasi</label><br>
        <select name="location_id" required>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id']; ?>" <?= $l['id'] == $service['location_id'] ? 'selected' : ''; ?>>
                    <?= e($l['nama_wilayah']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Harga (Rp)</label><br>
        <input type="number" name="harga" min="1000" value="<?= (int)$service['harga']; ?>" required><br><br>

        <label>Deskripsi</label><br>
        <textarea name="deskripsi" rows="5" required><?= e($service['deskripsi']); ?></textarea><br><br>

        <label>Ganti Foto (Opsional, maks 2 MB)</label><br>
        <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp"><br><br>

        <button type="submit">Update Layanan</button>
        <a href="<?= BASE_URL; ?>/layanan_saya.php">Batal</a>
    </form>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
