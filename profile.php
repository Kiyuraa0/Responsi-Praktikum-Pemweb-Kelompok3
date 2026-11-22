<?php
require __DIR__ . '/config.php';
$u = require_role('client', 'provider');
$pdo = db();
$isProvider = $u['role'] === 'provider';

if ($isProvider) {   // pastikan baris profil ada
    $pdo->prepare('INSERT IGNORE INTO provider_profiles (user_id) VALUES (?)')->execute([$u['id']]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update') {
            $name = trim($_POST['name'] ?? ''); $phone = trim($_POST['phone'] ?? '');
            if (mb_strlen($name) < 3) throw new RuntimeException('Nama minimal 3 karakter.');
            if ($phone !== '' && !preg_match('/^[0-9+]{8,16}$/', $phone)) throw new RuntimeException('Nomor telepon tidak valid.');
            $pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?')->execute([$name, $phone ?: null, $u['id']]);

            if ($isProvider) {
                $old = $pdo->prepare('SELECT photo FROM provider_profiles WHERE user_id = ?'); $old->execute([$u['id']]);
                $photo = $old->fetchColumn();
                if (!empty($_FILES['photo']['name'])) {
                    $new = save_image($_FILES['photo']); delete_image($photo); $photo = $new;
                }
                $pdo->prepare('UPDATE provider_profiles SET bio = ?, skills = ?, service_area = ?, photo = ? WHERE user_id = ?')
                    ->execute([trim($_POST['bio'] ?? ''), trim($_POST['skills'] ?? ''), trim($_POST['service_area'] ?? ''), $photo, $u['id']]);
            }
            flash('Profil diperbarui.');
        } elseif ($action === 'password') {
            $s = $pdo->prepare('SELECT password FROM users WHERE id = ?'); $s->execute([$u['id']]);
            if (!password_verify($_POST['current'] ?? '', $s->fetchColumn())) throw new RuntimeException('Password lama salah.');
            if (strlen($_POST['new'] ?? '') < 8) throw new RuntimeException('Password baru minimal 8 karakter.');
            if ($_POST['new'] !== ($_POST['confirm'] ?? '')) throw new RuntimeException('Konfirmasi password tidak sama.');
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]);
            flash('Password diganti.');
        } elseif ($isProvider && $action === 'add_portfolio') {
            $t = trim($_POST['title'] ?? '');
            if ($t === '') throw new RuntimeException('Judul portofolio wajib diisi.');
            $img = save_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            $pdo->prepare('INSERT INTO portfolios (user_id, title, image) VALUES (?,?,?)')->execute([$u['id'], $t, $img]);
            flash('Portofolio ditambahkan.');
        } elseif ($isProvider && $action === 'del_portfolio') {
            $s = $pdo->prepare('SELECT image FROM portfolios WHERE id = ? AND user_id = ?'); $s->execute([(int)$_POST['id'], $u['id']]);
            if ($img = $s->fetchColumn()) {
                delete_image($img);
                $pdo->prepare('DELETE FROM portfolios WHERE id = ? AND user_id = ?')->execute([(int)$_POST['id'], $u['id']]);
                flash('Portofolio dihapus.');
            }
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
    }
    redirect('profile.php');
}

$u = current_user();
$profile = []; $items = [];
if ($isProvider) {
    $s = $pdo->prepare('SELECT * FROM provider_profiles WHERE user_id = ?'); $s->execute([$u['id']]); $profile = $s->fetch();
    $s = $pdo->prepare('SELECT * FROM portfolios WHERE user_id = ? ORDER BY id DESC'); $s->execute([$u['id']]); $items = $s->fetchAll();
}
$title = 'Profil saya';
require __DIR__ . '/partials/header.php';
?>
<h1>Profil saya <span class="tag"><?= $isProvider ? 'Penyedia jasa' : 'Klien' ?></span></h1>

<section class="card">
  <h2>Data diri</h2>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="update">
    <label>Nama <input name="name" value="<?= e($u['name']) ?>" required></label>
    <label>Email <input value="<?= e($u['email']) ?>" disabled></label>
    <label>No. telepon <input name="phone" value="<?= e($u['phone']) ?>"></label>
    <?php if ($isProvider): ?>
      <?php if (!empty($profile['photo'])): ?><img class="avatar" src="uploads/<?= e($profile['photo']) ?>" alt="Foto profil"><?php endif; ?>
      <label>Foto profil (JPG/PNG/WEBP, maks 2 MB) <input type="file" name="photo" accept="image/*"></label>
      <label>Bio singkat <textarea name="bio" rows="4"><?= e($profile['bio'] ?? '') ?></textarea></label>
      <label>Keahlian (pisahkan dengan koma) <input name="skills" value="<?= e($profile['skills'] ?? '') ?>"></label>
      <label>Area layanan <input name="service_area" value="<?= e($profile['service_area'] ?? '') ?>" placeholder="Contoh: Purwokerto, Banyumas"></label>
    <?php endif; ?>
    <button class="btn">Simpan perubahan</button>
  </form>
</section>

<?php if ($isProvider): ?>
<section class="card">
  <h2>Portofolio</h2>
  <form method="post" enctype="multipart/form-data" class="inline">
    <?= csrf_field() ?><input type="hidden" name="action" value="add_portfolio">
    <label>Judul karya <input name="title" required></label>
    <label>Gambar <input type="file" name="image" accept="image/*" required></label>
    <button class="btn">Tambah karya</button>
  </form>
  <?php if (!$items): ?><p class="muted">Belum ada karya. Unggah foto hasil kerjamu agar klien percaya.</p><?php endif; ?>
  <div class="grid">
    <?php foreach ($items as $it): ?>
      <figure>
        <img src="uploads/<?= e($it['image']) ?>" alt="<?= e($it['title']) ?>">
        <figcaption><?= e($it['title']) ?>
          <form method="post" onsubmit="return confirm('Hapus karya ini?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="del_portfolio"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button class="link danger">Hapus</button>
          </form>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="card">
  <h2>Ganti password</h2>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <label>Password lama <input type="password" name="current" required></label>
    <label>Password baru <input type="password" name="new" minlength="8" required></label>
    <label>Ulangi password baru <input type="password" name="confirm" minlength="8" required></label>
    <button class="btn">Ganti password</button>
  </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
