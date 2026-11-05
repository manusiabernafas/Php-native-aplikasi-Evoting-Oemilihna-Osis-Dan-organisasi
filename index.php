<?php
require __DIR__ . '/includes/bootstrap.php';

$routes = [
    // Bilik suara (pemilih login dengan NIS + token)
    'bilik'        => 'pages/bilik/index.php',
    'bilik.pilih'  => 'pages/bilik/pilih.php',
    'bilik.keluar' => 'pages/bilik/keluar.php',
    'hasil'        => 'pages/bilik/hasil.php',
    // Panel panitia
    'login'        => 'pages/admin/login.php',
    'logout'       => 'pages/admin/logout.php',
    'dashboard'    => 'pages/admin/dashboard.php',
    'kandidat'     => 'pages/admin/kandidat.php',
    'pemilih'      => 'pages/admin/pemilih.php',
    'kartu'        => 'pages/admin/kartu.php',
    'rekap'        => 'pages/admin/rekap.php',
    'pengaturan'   => 'pages/admin/pengaturan.php',
];
$publik = ['bilik', 'bilik.pilih', 'bilik.keluar', 'hasil', 'login'];

$page = $_GET['page'] ?? 'bilik';
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'bilik';
}
if (!in_array($page, $publik, true)) require_login();
verify_csrf();

require __DIR__ . '/' . $routes[$page];
