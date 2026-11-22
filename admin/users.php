<?php
require __DIR__ . '/../config.php';
$admin = require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0); $action = $_POST['action'] ?? '';
    if ($id === (int)$admin['id']) {
        flash('Anda tidak dapat mengubah akun sendiri dari sini.', 'err');
    } else {
        if ($action === 'toggle') {
            $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            flash('Status akun diperbarui.');
        } elseif ($action === 'role' && in_array($_POST['role'] ?? '', ['client', 'provider', 'admin'], true)) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$_POST['role'], $id]);
            if ($_POST['role'] === 'provider') $pdo->prepare('INSERT IGNORE INTO provider_profiles (user_id) VALUES (?)')->execute([$id]);
            flash('Peran diperbarui.');
        } elseif ($action === 'delete') {
            $s = $pdo->prepare('SELECT image FROM portfolios WHERE user_id = ?'); $s->execute([$id]);
            foreach ($s->fetchAll(PDO::FETCH_COLUMN) as $img) delete_image($img);
            $p = $pdo->prepare('SELECT photo FROM provider_profiles WHERE user_id = ?'); $p->execute([$id]); delete_image($p->fetchColumn() ?: null);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('Pengguna dihapus.');
        }
    }
    redirect('users.php?' . http_build_query(['q' => $_GET['q'] ?? '', 'page' => $_GET['page'] ?? 1]));
}

$q = trim($_GET['q'] ?? ''); $page = max(1, (int)($_GET['page'] ?? 1)); $per = 10;
$where = $q !== '' ? 'WHERE name LIKE :q OR email LIKE :q' : '';
$total = $pdo->prepare("SELECT COUNT(*) FROM users $where");
if ($q !== '') $total->bindValue(':q', "%$q%");
$total->execute(); $pages = max(1, (int)ceil($total->fetchColumn() / $per));
$page = min($page, $pages);

$st = $pdo->prepare("SELECT id, name, email, role, is_active, created_at FROM users $where ORDER BY id DESC LIMIT $per OFFSET " . ($page - 1) * $per);
if ($q !== '') $st->bindValue(':q', "%$q%");
$st->execute(); $users = $st->fetchAll();

$title = 'Kelola pengguna';
require __DIR__ . '/../partials/header.php';
?>
<h1>Kelola pengguna</h1>
<form method="get" class="inline"><input name="q" value="<?= e($q) ?>" placeholder="Cari nama atau email"><button class="btn">Cari</button></form>
<div class="scroll">
<table>
  <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Aksi</th></tr>
  <?php if (!$users): ?><tr><td colspan="5" class="muted">Tidak ada pengguna yang cocok.</td></tr><?php endif; ?>
  <?php foreach ($users as $r): $self = $r['id'] == $admin['id']; ?>
  <tr>
    <td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td>
    <td>
      <form method="post" class="inline">
        <?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <select name="role" onchange="this.form.submit()" <?= $self ? 'disabled' : '' ?>>
          <?php foreach (['client', 'provider', 'admin'] as $ro): ?><option <?= $r['role'] === $ro ? 'selected' : '' ?>><?= $ro ?></option><?php endforeach; ?>
        </select>
      </form>
    </td>
    <td><?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
    <td class="actions">
      <?php if (!$self): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="link"><?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
      <form method="post" onsubmit="return confirm('Hapus pengguna ini beserta datanya?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="link danger">Hapus</button></form>
      <?php else: ?><span class="muted">Akun Anda</span><?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<nav class="pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a class="<?= $i === $page ? 'on' : '' ?>" href="?<?= http_build_query(['q' => $q, 'page' => $i]) ?>"><?= $i ?></a>
  <?php endfor; ?>
</nav>
<?php require __DIR__ . '/../partials/footer.php'; ?>
