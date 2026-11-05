<?php $flash = flash(); $p = pemilihan_aktif(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Bilik Suara') ?> · <?= e(setting()['nama_instansi']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="booth-top">
    <div class="top-inner">
        <div class="logo"><span class="sq"><?= icon('vote', 22) ?></span><div><strong><?= e($p['judul'] ?? 'E-Voting') ?></strong><small><?= e(setting()['nama_instansi']) ?></small></div></div>
        <?php if (pemilih()): ?><a class="btn btn-sm" style="border-color:#fff;color:#fff;background:transparent" href="<?= url('bilik.keluar') ?>">Batal & Keluar</a><?php endif; ?>
    </div>
</header>
<main class="booth">
    <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
