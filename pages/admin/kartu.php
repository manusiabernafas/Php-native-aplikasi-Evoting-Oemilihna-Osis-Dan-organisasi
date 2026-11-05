<?php
$p = pemilihan_aktif();
if (!$p) redirect('dashboard');
$kelas = $_GET['kelas'] ?? '';
$rows = q('SELECT * FROM pemilih WHERE pemilihan_id = ? AND kelas = ? AND sudah_memilih = 0 ORDER BY nama', [$p['id'], $kelas])->fetchAll();
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Kartu Token <?= e($kelas) ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body style="padding:20px" onload="window.print()">
<p class="no-print muted">Gunting per kartu dan bagikan ke siswa. Pemilih yang sudah memilih tidak dicetak.</p>
<div class="cards">
    <?php foreach ($rows as $r): ?>
        <div class="tcard">
            <small class="label" style="color:var(--red)"><?= e($p['judul']) ?></small>
            <div style="margin-top:6px"><b><?= e($r['nama']) ?></b> · <?= e($r['kelas']) ?></div>
            <small>NIS <span class="mono"><?= e($r['nis']) ?></span></small>
            <div class="tk"><?= e($r['token']) ?></div>
            <small>Buka bilik suara, masukkan NIS & token. Rahasiakan token ini.</small>
        </div>
    <?php endforeach; ?>
</div>
</body></html>
