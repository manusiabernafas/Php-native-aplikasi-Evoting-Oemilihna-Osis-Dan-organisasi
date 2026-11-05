<?php
$p = pemilihan_aktif();
if (!$p) { flash('Belum ada pemilihan aktif. Buat di Pengaturan.', 'error'); redirect(user()['role'] === 'admin' ? 'pengaturan' : 'kandidat'); }
$status = status_pemilihan($p);
$dpt = (int) q('SELECT COUNT(*) FROM pemilih WHERE pemilihan_id = ?', [$p['id']])->fetchColumn();
$sudah = (int) q('SELECT COUNT(*) FROM pemilih WHERE pemilihan_id = ? AND sudah_memilih = 1', [$p['id']])->fetchColumn();
$suara = (int) q('SELECT COUNT(*) FROM suara WHERE pemilihan_id = ?', [$p['id']])->fetchColumn();
$perKelas = q('SELECT kelas, COUNT(*) dpt, SUM(sudah_memilih) sudah FROM pemilih WHERE pemilihan_id = ? GROUP BY kelas ORDER BY kelas', [$p['id']])->fetchAll();
$perJam = q("SELECT HOUR(waktu_memilih) j, COUNT(*) n FROM pemilih WHERE pemilihan_id = ? AND sudah_memilih = 1 AND DATE(waktu_memilih) = CURDATE() GROUP BY j ORDER BY j", [$p['id']])->fetchAll(PDO::FETCH_KEY_PAIR);
$maxJam = max(1, ...array_values($perJam ?: [1]));
$sisa = max(0, strtotime($p['selesai']) - time());

$title = 'Monitor Pemilihan';
$label = $p['judul'];
$actions = '<a class="btn" href="' . url('bilik') . '" target="_blank">Buka Bilik Suara</a><a class="btn btn-black" href="' . url('dashboard') . '">Muat Ulang</a>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="numbers">
    <div class="num"><span class="label">Status</span><strong style="font-size:36px;margin-top:18px"><?= ['belum' => 'Belum dibuka', 'buka' => 'Dibuka', 'tutup' => 'Ditutup'][$status] ?></strong>
        <span><?= $status === 'buka' ? 'sisa ' . floor($sisa / 3600) . ' jam ' . floor($sisa % 3600 / 60) . ' menit' : jam($p['mulai']) . ' – ' . jam($p['selesai']) ?></span></div>
    <div class="num"><span class="label">Daftar pemilih</span><strong><?= $dpt ?></strong><span>siswa terdaftar</span></div>
    <div class="num red"><span class="label">Sudah memilih</span><strong><?= $sudah ?></strong><span>partisipasi <?= $dpt ? round($sudah / $dpt * 100, 1) : 0 ?>%</span></div>
    <div class="num"><span class="label">Integritas</span><strong style="font-size:36px;margin-top:18px"><?= $suara === $sudah ? 'Sesuai' : 'Selisih' ?></strong><span><?= $suara ?> suara di kotak = <?= $sudah ?> pemilih</span></div>
</div>
<div class="cols">
    <div class="block">
        <div class="block-head"><h2>Partisipasi per kelas</h2><span class="label muted">% sudah memilih</span></div>
        <div class="block-body">
            <?php foreach ($perKelas as $k): $pct = round($k['sudah'] / $k['dpt'] * 100); ?>
                <div class="part"><b><?= e($k['kelas']) ?></b><div class="track"><div class="fill" style="width:<?= $pct ?>%;<?= $pct < 60 ? 'background:var(--red)' : '' ?>"></div></div><span class="text-right"><?= $pct ?>%</span></div>
            <?php endforeach; ?>
            <p class="muted" style="font-size:13px;margin:14px 0 0">Merah: partisipasi di bawah 60%. Panitia bisa mengingatkan kelas tersebut.</p>
        </div>
    </div>
    <div class="block">
        <div class="block-head"><h2>Arus pemilih hari ini</h2></div>
        <div class="block-body">
            <?php for ($j = 7; $j <= 15; $j++): $n = $perJam[$j] ?? 0; ?>
                <div class="part"><b><?= str_pad((string) $j, 2, '0', STR_PAD_LEFT) ?>.00</b><div class="track"><div class="fill" style="width:<?= round($n / $maxJam * 100) ?>%"></div></div><span class="text-right"><?= $n ?></span></div>
            <?php endfor; ?>
            <p class="muted" style="font-size:13px;margin:14px 0 0">Hasil perolehan suara hanya ditampilkan di menu Rekapitulasi<?= user()['role'] === 'admin' ? '' : ' setelah pemilihan ditutup' ?>.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
