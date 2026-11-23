<?php
require_once __DIR__ . '/config.php';
$user = require_role('client');
$pdo = db();

$service_id = (int)($_GET['service_id'] ?? $_POST['service_id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT s.*, c.nama_kategori, l.nama_wilayah, u.name AS nama_penyedia, u.phone AS telp_penyedia,
           pp.bio, pp.skills, pp.service_area, pp.photo
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN locations l ON s.location_id = l.id
    JOIN users u ON s.provider_id = u.id
    LEFT JOIN provider_profiles pp ON u.id = pp.user_id
    WHERE s.id = ? AND s.status = 'aktif'
");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    flash('Layanan tidak ditemukan atau sedang nonaktif.', 'err');
    redirect(BASE_URL . '/katalog.php');
}

if ($service['provider_id'] == $user['id']) {
    flash('Anda tidak dapat memesan layanan milik sendiri.', 'err');
    redirect(BASE_URL . '/katalog.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $tanggal = trim($_POST['tanggal_pengerjaan'] ?? '');
        $alamat  = trim($_POST['alamat_pengerjaan'] ?? '');
        $catatan = trim($_POST['catatan'] ?? '');

        if ($tanggal === '' || strtotime($tanggal) < strtotime(date('Y-m-d'))) {
            throw new RuntimeException('Tanggal pengerjaan minimal hari ini.');
        }
        if (mb_strlen($alamat) < 8) {
            throw new RuntimeException('Alamat lengkap pengerjaan wajib diisi (minimal 8 karakter).');
        }

        $ins = $pdo->prepare("
            INSERT INTO orders (service_id, client_id, provider_id, tanggal_pengerjaan, alamat_pengerjaan, catatan, total_harga, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'menunggu')
        ");
        $ins->execute([
            $service['id'],
            $user['id'],
            $service['provider_id'],
            $tanggal,
            $alamat,
            $catatan !== '' ? $catatan : null,
            (int)$service['harga']
        ]);

        flash('Pesanan berhasil dibuat! Menunggu konfirmasi dari penyedia jasa.');
        redirect(BASE_URL . '/pesanan_saya.php');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'err');
    }
}

// Ambil ringkasan rating & ulasan untuk layanan ini
$revStat = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS rata FROM reviews WHERE service_id = ? AND is_hidden = 0");
$revStat->execute([$service['id']]);
$stat = $revStat->fetch();

$revList = $pdo->prepare("
    SELECT r.*, u.name AS nama_klien
    FROM reviews r
    JOIN users u ON r.client_id = u.id
    WHERE r.service_id = ? AND r.is_hidden = 0
    ORDER BY r.created_at DESC
    LIMIT 10
");
$revList->execute([$service['id']]);
$reviews = $revList->fetchAll();

// Ambil portofolio penyedia
$portStmt = $pdo->prepare("SELECT * FROM portfolios WHERE user_id = ? ORDER BY id DESC LIMIT 4");
$portStmt->execute([$service['provider_id']]);
$portfolios = $portStmt->fetchAll();

$title = 'Pesan Jasa: ' . $service['judul'];
require_once __DIR__ . '/partials/header.php';
?>
<h1>Pesan Jasa Lokal</h1>

<div class="card">
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <img src="<?= BASE_URL ?>/uploads/<?= e($service['gambar']) ?>" alt="<?= e($service['judul']) ?>" style="width:240px; height:170px; object-fit:cover; border-radius:8px;">
        <div style="flex:1; min-width:240px;">
            <span class="tag"><?= e($service['nama_kategori']) ?></span>
            <span class="tag"><?= e($service['nama_wilayah']) ?></span>
            <h2 style="margin-top:10px; font-size:1.35rem;"><?= e($service['judul']) ?></h2>
            <p><?= nl2br(e($service['deskripsi'])) ?></p>
            <p><strong>Penyedia Jasa:</strong> <?= e($service['nama_penyedia']) ?>
               <?= $stat['total'] > 0 ? ' — ★ ' . number_format((float)$stat['rata'], 1) . ' (' . $stat['total'] . ' ulasan)' : ' — Belum ada ulasan' ?>
            </p>
            <?php if (!empty($service['skills'])): ?>
                <p class="muted" style="font-size:.9rem;">Keahlian: <?= e($service['skills']) ?></p>
            <?php endif; ?>
            <p style="font-size:1.2rem; color:var(--accent); font-weight:700;">
                Harga: Rp <?= number_format((int)$service['harga'], 0, ',', '.') ?>
            </p>
        </div>
    </div>
</div>

<section class="card">
    <h2>Formulir Pemesanan</h2>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>">

        <label>Tanggal Pengerjaan
            <input type="date" name="tanggal_pengerjaan" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['tanggal_pengerjaan'] ?? date('Y-m-d')) ?>" required>
        </label>

        <label>Alamat Lengkap Pengerjaan
            <textarea name="alamat_pengerjaan" rows="3" placeholder="Masukkan jalan, nomor rumah, RT/RW, patokan lokasi..." required><?= e($_POST['alamat_pengerjaan'] ?? '') ?></textarea>
        </label>

        <label>Catatan Tambahan untuk Penyedia (Opsional)
            <textarea name="catatan" rows="2" placeholder="Contoh: Bawa tangga lipat, pengerjaan pagi hari, dll."><?= e($_POST['catatan'] ?? '') ?></textarea>
        </label>

        <button class="btn" type="submit">Kirim Pesanan</button>
        <a class="btn ghost" href="<?= BASE_URL ?>/katalog.php">Kembali ke Katalog</a>
    </form>
</section>

<?php if ($portfolios): ?>
<section class="card">
    <h2>Portofolio <?= e($service['nama_penyedia']) ?></h2>
    <div class="grid">
        <?php foreach ($portfolios as $p): ?>
            <figure>
                <img src="<?= BASE_URL ?>/uploads/<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>">
                <figcaption><?= e($p['title']) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="card">
    <h2>Ulasan Klien Sebelumnya (<?= (int)$stat['total'] ?>)</h2>
    <?php if (!$reviews): ?>
        <p class="muted">Belum ada ulasan untuk layanan ini.</p>
    <?php else: ?>
        <?php foreach ($reviews as $r): ?>
            <div style="border-bottom:1px solid var(--line); padding:10px 0;">
                <strong><?= e($r['nama_klien']) ?></strong>
                <span style="color:#d98200; font-weight:700; margin-left:6px;"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
                <span class="muted" style="font-size:.85rem; margin-left:8px;"><?= e($r['created_at']) ?></span>
                <p style="margin:6px 0 0;"><?= nl2br(e($r['komentar'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
