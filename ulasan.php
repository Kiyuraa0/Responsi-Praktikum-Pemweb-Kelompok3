<?php
require_once __DIR__ . '/config.php';
$user = require_role('client', 'provider');
$pdo = db();

// Jika Klien mengirim ulasan untuk pesanan selesai
if ($user['role'] === 'client') {
    $order_id = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

    if ($order_id > 0) {
        $st = $pdo->prepare("
            SELECT o.*, s.judul AS judul_layanan, p.name AS nama_penyedia, r.id AS review_id
            FROM orders o
            JOIN services s ON o.service_id = s.id
            JOIN users p ON o.provider_id = p.id
            LEFT JOIN reviews r ON r.order_id = o.id
            WHERE o.id = ? AND o.client_id = ?
        ");
        $st->execute([$order_id, $user['id']]);
        $order = $st->fetch();

        if (!$order || $order['status'] !== 'selesai') {
            flash('Ulasan hanya dapat diberikan untuk pesanan yang sudah selesai.', 'err');
            redirect(BASE_URL . '/pesanan_saya.php');
        }
        if ($order['review_id']) {
            flash('Anda sudah memberikan ulasan untuk pesanan ini.', 'err');
            redirect(BASE_URL . '/pesanan_saya.php');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            try {
                $rating   = (int)($_POST['rating'] ?? 0);
                $komentar = trim($_POST['komentar'] ?? '');

                if ($rating < 1 || $rating > 5) {
                    throw new RuntimeException('Pilih rating antara 1 sampai 5 bintang.');
                }
                if (mb_strlen($komentar) < 5) {
                    throw new RuntimeException('Komentar ulasan minimal 5 karakter.');
                }

                $ins = $pdo->prepare("
                    INSERT INTO reviews (order_id, service_id, client_id, provider_id, rating, komentar)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([
                    $order['id'],
                    $order['service_id'],
                    $user['id'],
                    $order['provider_id'],
                    $rating,
                    $komentar
                ]);

                flash('Terima kasih! Ulasan dan rating Anda telah dipublikasikan.');
                redirect(BASE_URL . '/pesanan_saya.php');
            } catch (RuntimeException $e) {
                flash($e->getMessage(), 'err');
            }
        }

        $title = 'Beri Rating & Ulasan';
        require_once __DIR__ . '/partials/header.php';
        ?>
        <section class="card narrow">
            <h1>Beri Rating & Ulasan</h1>
            <p><strong>Layanan:</strong> <?= e($order['judul_layanan']) ?><br>
               <strong>Penyedia:</strong> <?= e($order['nama_penyedia']) ?><br>
               <strong>Tanggal Pengerjaan:</strong> <?= e($order['tanggal_pengerjaan']) ?></p>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">

                <label>Rating Kepuasan
                    <select name="rating" required>
                        <option value="5">★★★★★ (5 - Sangat Memuaskan)</option>
                        <option value="4">★★★★☆ (4 - Memuaskan)</option>
                        <option value="3">★★★☆☆ (3 - Cukup Baik)</option>
                        <option value="2">★★☆☆☆ (2 - Kurang Memuaskan)</option>
                        <option value="1">★☆☆☆☆ (1 - Buruk)</option>
                    </select>
                </label>

                <label>Ulasan Pengalaman Anda
                    <textarea name="komentar" rows="4" placeholder="Ceritakan kualitas pekerjaan, ketepatan waktu, dan keramahan penyedia jasa..." required><?= e($_POST['komentar'] ?? '') ?></textarea>
                </label>

                <button class="btn" type="submit">Kirim Ulasan</button>
                <a class="btn ghost" href="<?= BASE_URL ?>/pesanan_saya.php">Batal</a>
            </form>
        </section>
        <?php
        require_once __DIR__ . '/partials/footer.php';
        exit;
    }
}

// Tampilan Riwayat Ulasan (Untuk Penyedia melihat reputasinya, atau Klien melihat ulasan yang pernah ditulis)
$isProvider = $user['role'] === 'provider';
if ($isProvider) {
    $statStmt = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS rata FROM reviews WHERE provider_id = ? AND is_hidden = 0");
    $statStmt->execute([$user['id']]);
    $summary = $statStmt->fetch();

    $listStmt = $pdo->prepare("
        SELECT r.*, s.judul AS judul_layanan, u.name AS nama_klien
        FROM reviews r
        JOIN services s ON r.service_id = s.id
        JOIN users u ON r.client_id = u.id
        WHERE r.provider_id = ? AND r.is_hidden = 0
        ORDER BY r.created_at DESC
    ");
    $listStmt->execute([$user['id']]);
} else {
    $summary = null;
    $listStmt = $pdo->prepare("
        SELECT r.*, s.judul AS judul_layanan, u.name AS nama_penyedia
        FROM reviews r
        JOIN services s ON r.service_id = s.id
        JOIN users u ON r.provider_id = u.id
        WHERE r.client_id = ?
        ORDER BY r.created_at DESC
    ");
    $listStmt->execute([$user['id']]);
}
$reviews = $listStmt->fetchAll();

$title = $isProvider ? 'Ulasan & Reputasi Saya' : 'Ulasan Saya';
require_once __DIR__ . '/partials/header.php';
?>
<h1><?= $isProvider ? 'Ulasan & Reputasi Penyedia Jasa' : 'Ulasan yang Saya Berikan' ?></h1>

<?php if ($isProvider): ?>
<div class="stats">
    <div class="stat">
        <strong>★ <?= number_format((float)$summary['rata'], 1) ?></strong>
        <span>Rata-rata Rating</span>
    </div>
    <div class="stat">
        <strong><?= (int)$summary['total'] ?></strong>
        <span>Total Ulasan Diterima</span>
    </div>
</div>
<?php endif; ?>

<section class="card">
    <?php if (!$reviews): ?>
        <p class="muted">Belum ada ulasan yang tercatat.</p>
    <?php else: ?>
        <?php foreach ($reviews as $r): ?>
            <div style="border-bottom:1px solid var(--line); padding:12px 0;">
                <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
                    <strong><?= e($r['judul_layanan']) ?></strong>
                    <span class="muted" style="font-size:.85rem;"><?= e($r['created_at']) ?></span>
                </div>
                <p style="margin:4px 0;">
                    <span style="color:#d98200; font-weight:700;"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
                    <span class="muted"> — <?= $isProvider ? 'Oleh: ' . e($r['nama_klien']) : 'Untuk: ' . e($r['nama_penyedia']) ?></span>
                </p>
                <p style="margin:6px 0 0;"><?= nl2br(e($r['komentar'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
