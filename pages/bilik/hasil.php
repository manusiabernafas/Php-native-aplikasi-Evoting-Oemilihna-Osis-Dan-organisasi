<?php
$p = pemilihan_aktif();
$boleh = $p && (user() || (setting()['hasil_publik'] && status_pemilihan($p) === 'tutup'));
if (!$boleh) { flash('Hasil belum dapat ditampilkan.', 'error'); redirect('bilik'); }
$hasil = q('SELECT k.*, COUNT(s.id) suara FROM kandidat k LEFT JOIN suara s ON s.kandidat_id = k.id WHERE k.pemilihan_id = ? GROUP BY k.id ORDER BY suara DESC, k.no_urut', [$p['id']])->fetchAll();
$total = array_sum(array_column($hasil, 'suara'));
$dpt = (int) q('SELECT COUNT(*) FROM pemilih WHERE pemilihan_id = ?', [$p['id']])->fetchColumn();
$title = 'Hasil Pemilihan';
require __DIR__ . '/../../includes/booth_header.php';
?>
<div class="hero" style="grid-template-columns:1fr"><div><span class="label" style="color:var(--red)">Hasil resmi · partisipasi <?= $dpt ? round($total / $dpt * 100, 1) : 0 ?>%</span><h1><?= e($p['judul']) ?></h1></div></div>
<?php foreach ($hasil as $i => $h): $pct = $total ? round($h['suara'] / $total * 100, 1) : 0; ?>
    <div class="result <?= $i === 0 ? 'lead' : '' ?>"><div class="no"><?= str_pad((string) $h['no_urut'], 2, '0', STR_PAD_LEFT) ?></div>
        <div><strong><?= e($h['ketua']) ?><?= $h['wakil'] ? ' & ' . e($h['wakil']) : '' ?></strong><div class="track"><div class="fill" style="width:<?= $pct ?>%"></div></div></div>
        <div class="pct"><?= $pct ?>%<small><?= $h['suara'] ?> suara</small></div></div>
<?php endforeach; ?>
<?php require __DIR__ . '/../../includes/booth_footer.php'; ?>
