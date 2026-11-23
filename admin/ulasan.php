<?php
require __DIR__ . '/../config.php';
require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $pdo->prepare('UPDATE reviews SET is_hidden = 1 - is_hidden WHERE id = ?')->execute([$id]);
        flash('Status visibilitas ulasan diperbarui.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
        flash('Ulasan berhasil dihapus.');
    }
    redirect('ulasan.php');
}

$reviews = $pdo->query("
    SELECT r.*, s.judul AS judul_layanan, c.name AS nama_klien, p.name AS nama_penyedia
    FROM reviews r
    JOIN services s ON r.service_id = s.id
    JOIN users c ON r.client_id = c.id
    JOIN users p ON r.provider_id = p.id
    ORDER BY r.created_at DESC
")->fetchAll();

$title = 'Moderasi Ulasan';
require __DIR__ . '/../partials/header.php';
?>
<h1>Moderasi Ulasan & Rating</h1>
<div class="card scroll">
    <table>
        <thead>
            <tr>
                <th>Layanan</th>
                <th>Klien</th>
                <th>Penyedia</th>
                <th>Rating</th>
                <th>Komentar</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$reviews): ?>
                <tr><td colspan="7" class="muted">Belum ada ulasan masuk.</td></tr>
            <?php endif; ?>
            <?php foreach ($reviews as $r): ?>
            <tr>
                <td><?= e($r['judul_layanan']) ?></td>
                <td><?= e($r['nama_klien']) ?></td>
                <td><?= e($r['nama_penyedia']) ?></td>
                <td>★ <?= (int)$r['rating'] ?></td>
                <td><?= e($r['komentar']) ?></td>
                <td><?= $r['is_hidden'] ? 'Disembunyikan' : 'Tampil' ?></td>
                <td class="actions">
                    <form method="POST">
                        <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="link"><?= $r['is_hidden'] ? 'Tampilkan' : 'Sembunyikan' ?></button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Hapus ulasan ini secara permanen?')">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="link danger">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
