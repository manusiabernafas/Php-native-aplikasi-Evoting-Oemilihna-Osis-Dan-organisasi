<?php
$nav = [['dashboard', 'Monitor'], ['kandidat', 'Kandidat'], ['pemilih', 'Daftar Pemilih'], ['rekap', 'Rekapitulasi']];
if (user()['role'] === 'admin') $nav[] = ['pengaturan', 'Pengaturan'];
$current = $_GET['page'] ?? 'dashboard';
if ($current === 'kartu') $current = 'pemilih';
$flash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Monitor') ?> · E-Voting <?= e(setting()['nama_instansi']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="top">
    <div class="top-inner">
        <div class="logo"><span class="sq"><?= icon('vote', 22) ?></span><div><strong>E-Voting</strong><small><?= e(setting()['nama_instansi']) ?></small></div></div>
        <nav><?php foreach ($nav as [$k, $l]): ?><a href="<?= url($k) ?>" class="<?= $current === $k ? 'active' : '' ?>"><?= $l ?></a><?php endforeach; ?></nav>
        <div class="who"><span class="muted"><?= e(user()['nama']) ?></span><a href="<?= url('bilik') ?>" target="_blank">Bilik &nearr;</a><a href="<?= url('logout') ?>">Keluar</a></div>
    </div>
</header>
<main class="wrap">
    <div class="hero">
        <div><span class="label"><?= e($label ?? 'Panel Panitia') ?></span><h1><?= e($title ?? 'Monitor') ?></h1></div>
        <div class="hero-actions"><?= $actions ?? '' ?></div>
    </div>
    <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
