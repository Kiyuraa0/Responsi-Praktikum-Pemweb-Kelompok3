<?php
require __DIR__ . '/config.php';
$_SESSION = [];
session_destroy();
session_start();
flash('Anda sudah keluar.');
redirect('login.php');
