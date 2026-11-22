<?php
require_once __DIR__ . '/../config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['tambah'])) {
        $nama = trim($_POST['nama_kategori'] ?? '');
        if ($nama !== '') {
            db()->prepare('INSERT INTO categories (nama_kategori) VALUES (?)')->execute([$nama]);
            flash('Kategori berhasil ditambahkan.');
        }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $nama = trim($_POST['nama_kategori'] ?? '');
        if ($nama !== '') {
            db()->prepare('UPDATE categories SET nama_kategori = ? WHERE id = ?')->execute([$nama, $id]);
            flash('Kategori berhasil diperbarui.');
        }
    } elseif (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash('Kategori berhasil dihapus.');
    }
    redirect(BASE_URL . '/admin/kategori.php');
}

$categories = db()->query('SELECT * FROM categories ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/../partials/header.php';
?>
<div class="container">
    <h2>Kelola Kategori Jasa (Admin)</h2>
    <form method="POST" style="margin-bottom: 20px; display:flex; gap:8px;">
        <?= csrf_field(); ?>
        <input type="text" name="nama_kategori" placeholder="Nama Kategori Baru..." required>
        <button type="submit" name="tambah">Tambah Kategori</button>
    </form>
    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead><tr><th>No</th><th>Nama Kategori</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php foreach ($categories as $i => $row): ?>
            <tr>
                <td><?= $i + 1; ?></td>
                <td>
                    <form method="POST" style="display:flex; gap:8px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <input type="text" name="nama_kategori" value="<?= e($row['nama_kategori']); ?>" required>
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
