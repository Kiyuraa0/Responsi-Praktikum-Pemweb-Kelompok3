<?php
declare(strict_types=1);
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'gawean';
const DB_USER = 'root';
const DB_PASS = '';
const BASE_URL = '/Responsi-Praktikum-Pemweb-Kelompok3';          // sesuaikan dengan nama folder di htdocs
const UPLOAD_DIR = __DIR__ . '/uploads/';

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }

function flash(?string $msg = null, string $type = 'ok'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Sesi kedaluwarsa. Muat ulang halaman.'); }
}

function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id, name, email, phone, role, is_active FROM users WHERE id = ?');
            $s->execute([$_SESSION['uid']]);
            $row = $s->fetch();
            if ($row && $row['is_active']) $user = $row; else unset($_SESSION['uid']);
        }
    }
    return $user;
}

function require_login(): array {
    $u = current_user();
    if (!$u) { flash('Silakan login terlebih dahulu.', 'err'); redirect(BASE_URL . '/login.php'); }
    return $u;
}
function require_role(string ...$roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('Akses ditolak.'); }
    return $u;
}
function home_for(string $role): string {
    return BASE_URL . ($role === 'admin' ? '/admin/index.php' : '/profile.php');
}

/** Validasi dan simpan gambar (maks 2 MB, JPG/PNG/WEBP). Mengembalikan nama file. */
function save_image(array $file): string {
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gambar gagal.');
    if ($file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Ukuran gambar maksimal 2 MB.');
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])] ?? null;
    if (!$ext) throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Gagal menyimpan gambar.');
    return $name;
}
function delete_image(?string $name): void {
    if ($name && is_file(UPLOAD_DIR . basename($name))) unlink(UPLOAD_DIR . basename($name));
}
