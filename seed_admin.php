<?php
// Jalankan SEKALI lewat terminal: php seed_admin.php, lalu hapus file ini.
require __DIR__ . '/config.php';
$email = 'admingawean@gawean.test'; $pass = 'Admin123$';
$s = db()->prepare('SELECT id FROM users WHERE email = ?'); $s->execute([$email]);
if ($s->fetch()) exit("Admin sudah ada.\n");
db()->prepare("INSERT INTO users (name, email, password, role) VALUES ('Administrator', ?, ?, 'admin')")
    ->execute([$email, password_hash($pass, PASSWORD_DEFAULT)]);
echo "Admin dibuat: $email / $pass (segera ganti password).\n";
