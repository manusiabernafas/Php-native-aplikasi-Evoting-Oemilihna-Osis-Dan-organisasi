<?php
$p = pemilihan_aktif();
if (!$p) redirect('dashboard');
$status = status_pemilihan($p);
if (user()['role'] !== 'admin' && $status !== 'tutup') { flash('Rekapitulasi suara dibuka untuk panitia setelah pemilihan ditutup.', 'error'); redirect('dashboard'); }
$hasil = q('SELECT k.*, COUNT(s.id) suara FROM kandidat k LEFT JOIN suara s ON s.kandidat_id = k.id WHERE k.pemilihan_id = ? GROUP BY k.id ORDER BY suara DESC, k.no_urut', [$p['id']])->fetchAll();
$total = array_sum(array_column($hasil, 'suara'));
$dpt = (int) q('SELECT COUNT(*) FROM pemilih WHERE pemilihan_id = ?', [$p['id']])->fetchColumn();
$sudah = (int) q('SELECT COUNT(*) FROM pemilih WHERE pemilihan_id = ? AND sudah_memilih = 1', [$p['id']])->fetchColumn();
$set = setting();
$title = $status === 'tutup' ? 'Hasil Akhir' : 'Rekap Sementara';
$label = $p['judul'];
$actions = '<button class="btn btn-black" onclick="window.print()">Cetak Berita Acara</button>';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($status !== 'tutup'): ?><div class="alert alert-error">Pemilihan masih berlangsung. Angka di bawah adalah rekap sementara dan hanya terlihat oleh admin.</div><?php endif; ?>
<div class="numbers">
    <div class="num"><span class="label">DPT</span><strong><?= $dpt ?></strong></div>
    <div class="num"><span class="label">Suara masuk</span><strong><?= $total ?></strong><span><?= $sudah === $total ? 'sesuai jumlah pemilih' : 'SELISIH dengan pemilih (' . $sudah . ')' ?></span></div>
    <div class="num red"><span class="label">Partisipasi</span><strong><?= $dpt ? round($total / $dpt * 100, 1) : 0 ?>%</strong></div>
    <div class="num"><span class="label">Tidak memilih</span><strong><?= $dpt - $sudah ?></strong></div>
</div>
<div class="block">
    <div class="block-head"><h2>Perolehan suara</h2><span class="label muted"><?= tgl(date('Y-m-d')) ?> <?= date('H.i') ?></span></div>
    <div class="block-body">
        <?php foreach ($hasil as $i => $h): $pct = $total ? round($h['suara'] / $total * 100, 1) : 0; ?>
            <div class="result <?= $i === 0 && $total ? 'lead' : '' ?>"><div class="no"><?= str_pad((string) $h['no_urut'], 2, '0', STR_PAD_LEFT) ?></div>
                <div><strong><?= e($h['ketua']) ?><?= $h['wakil'] ? ' & ' . e($h['wakil']) : '' ?></strong><div class="track"><div class="fill" style="width:<?= $pct ?>%"></div></div></div>
                <div class="pct"><?= $pct ?>%<small><?= $h['suara'] ?> suara</small></div></div>
        <?php endforeach; ?>
    </div>
</div>
<div class="block mt">
    <div class="block-head"><h2>Berita acara</h2></div>
    <div class="block-body" style="line-height:1.8">
        Pada hari ini, <?= tgl(date('Y-m-d')) ?>, telah dilaksanakan <b><?= e($p['judul']) ?></b> di <?= e($set['nama_instansi']) ?> secara elektronik
        mulai <?= tgl($p['mulai']) ?> pukul <?= jam($p['mulai']) ?> sampai <?= tgl($p['selesai']) ?> pukul <?= jam($p['selesai']) ?>.
        Dari <?= $dpt ?> pemilih terdaftar, sebanyak <?= $sudah ?> pemilih menggunakan hak pilihnya (<?= $dpt ? round($sudah / $dpt * 100, 1) : 0 ?>%) dengan <?= $total ?> suara sah.
        <?php if ($status === 'tutup' && $total): ?>Pasangan calon nomor urut <b><?= $hasil[0]['no_urut'] ?></b>, <?= e($hasil[0]['ketua']) ?><?= $hasil[0]['wakil'] ? ' dan ' . e($hasil[0]['wakil']) : '' ?>, memperoleh suara terbanyak.<?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:40px;text-align:center"><div>Ketua Panitia<br><br><br>(..............................)</div><div>Pembina OSIS<br><br><br>(..............................)</div></div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
