<?php
require_once __DIR__ . '/config.php';
$user = require_role('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    csrf_check();
    $id = (int)$_POST['hapus_id'];
    $stmt = db()->prepare('SELECT gambar FROM services WHERE id = ? AND provider_id = ?');
    $stmt->execute([$id, $user['id']]);
    $srv = $stmt->fetch();

    if ($srv) {
        delete_image($srv['gambar']);
        db()->prepare('DELETE FROM services WHERE id = ? AND provider_id = ?')->execute([$id, $user['id']]);
        flash('Layanan berhasil dihapus.');
    }
    redirect(BASE_URL . '/layanan_saya.php');
}

$stmt = db()->prepare('
    SELECT s.*, c.nama_kategori, l.nama_wilayah 
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN locations l ON s.location_id = l.id
    WHERE s.provider_id = ?
    ORDER BY s.created_at DESC
');
$stmt->execute([$user['id']]);
$services = $stmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>
<div class="container">
    <h2>Layanan Saya</h2>
    <p><a href="<?= BASE_URL; ?>/tambah_layanan.php" class="btn">+ Tambah Layanan Baru</a></p>
    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead><tr><th>Gambar</th><th>Judul</th><th>Kategori</th><th>Lokasi</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php foreach ($services as $row): ?>
            <tr>
                <td><img src="<?= BASE_URL; ?>/uploads/<?= e($row['gambar']); ?>" width="80" alt="Gambar"></td>
                <td><?= e($row['judul']); ?></td>
                <td><?= e($row['nama_kategori']); ?></td>
                <td><?= e($row['nama_wilayah']); ?></td>
                <td>Rp <?= number_format((int)$row['harga'], 0, ',', '.'); ?></td>
                <td><?= e($row['status']); ?></td>
                <td>
                    <a href="<?= BASE_URL; ?>/edit_layanan.php?id=<?= $row['id']; ?>" class="btn">Edit</a> |
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus layanan ini?')">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="hapus_id" value="<?= $row['id']; ?>">
                        <button type="submit" class="btnghost">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
