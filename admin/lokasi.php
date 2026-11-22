<?php
require_once __DIR__ . '/../config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['tambah'])) {
        $nama = trim($_POST['nama_wilayah'] ?? '');
        if ($nama !== '') {
            db()->prepare('INSERT INTO locations (nama_wilayah) VALUES (?)')->execute([$nama]);
            flash('Wilayah berhasil ditambahkan.');
        }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $nama = trim($_POST['nama_wilayah'] ?? '');
        if ($nama !== '') {
            db()->prepare('UPDATE locations SET nama_wilayah = ? WHERE id = ?')->execute([$nama, $id]);
            flash('Wilayah berhasil diperbarui.');
        }
    } elseif (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        db()->prepare('DELETE FROM locations WHERE id = ?')->execute([$id]);
        flash('Wilayah berhasil dihapus.');
    }
    redirect(BASE_URL . '/admin/lokasi.php');
}

$locations = db()->query('SELECT * FROM locations ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/../partials/header.php';
?>
<div class="container">
    <h2>Kelola Wilayah / Lokasi Layanan (Admin)</h2>
    <form method="POST" style="margin-bottom: 20px; display:flex; gap:8px;">
        <?= csrf_field(); ?>
        <input type="text" name="nama_wilayah" placeholder="Nama Kecamatan / Wilayah..." required>
        <button type="submit" name="tambah">Tambah Wilayah</button>
    </form>
    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead><tr><th>No</th><th>Nama Wilayah</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php foreach ($locations as $i => $row): ?>
            <tr>
                <td><?= $i + 1; ?></td>
                <td>
                    <form method="POST" style="display:flex; gap:8px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <input type="text" name="nama_wilayah" value="<?= e($row['nama_wilayah']); ?>" required>
                        <button type="submit" name="edit">Simpan</button>
                    </form>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Yakin hapus?')" style="display:inline;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <button type="submit" name="hapus">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
