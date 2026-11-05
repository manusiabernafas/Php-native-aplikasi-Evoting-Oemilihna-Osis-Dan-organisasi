<?php
// Langkah 2: simpan suara. Kunci rahasia & anti suara ganda ada di sini.
$v = pemilih();
$p = pemilihan_aktif();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$v || !$p || $p['id'] != $v['pemilihan_id']) redirect('bilik');

$selesai = false;
try {
    if (status_pemilihan($p) !== 'buka') throw new RuntimeException('Bilik suara sudah ditutup.');
    $kandidat = q('SELECT id FROM kandidat WHERE id = ? AND pemilihan_id = ?', [(int) ($_POST['kandidat_id'] ?? 0), $p['id']])->fetchColumn();
    if (!$kandidat) throw new RuntimeException('Pilih salah satu pasangan calon.');

    db()->beginTransaction();
    // 1) Tandai pemilih sudah memilih. Kondisi "sudah_memilih = 0" membuat UPDATE ini
    //    hanya berhasil SEKALI walaupun tombol dikirim dua kali / dari dua perangkat.
    $st = q('UPDATE pemilih SET sudah_memilih = 1, waktu_memilih = NOW() WHERE id = ? AND sudah_memilih = 0', [$v['id']]);
    if ($st->rowCount() !== 1) throw new RuntimeException('Hak pilih ini sudah digunakan.');

    // 2) Masukkan suara TANPA identitas pemilih: id acak, waktu dibulatkan ke jam.
    q('INSERT INTO suara (id, pemilihan_id, kandidat_id, jam) VALUES (?, ?, ?, ?)', [bin2hex(random_bytes(16)), $p['id'], $kandidat, date('Y-m-d H:00:00')]);
    db()->commit();
    $selesai = true;
} catch (RuntimeException $ex) {
    if (db()->inTransaction()) db()->rollBack();
    flash($ex->getMessage(), 'error');
}
// Sesi pemilih selalu dihapus setelah mencoba memilih agar bilik siap untuk pemilih berikutnya
unset($_SESSION['pemilih']);
if (!$selesai) redirect('bilik');

$title = 'Terima kasih';
require __DIR__ . '/../../includes/booth_header.php';
?>
<div class="thanks">
    <div class="mark"><?= icon('check', 48) ?></div>
    <span class="label" style="color:var(--green)">Suara tercatat</span>
    <h1 style="margin-top:10px">Terima kasih, <?= e(explode(' ', $v['nama'])[0]) ?>.</h1>
    <p class="muted" style="font-size:17px">Suaramu sudah masuk ke kotak suara digital secara rahasia. Bilik akan kembali ke halaman awal dalam 8 detik.</p>
    <a class="btn btn-black mt" href="<?= url('bilik') ?>">Selesai</a>
</div>
<script>setTimeout(function () { location.href = '<?= url('bilik') ?>'; }, 8000);</script>
<?php require __DIR__ . '/../../includes/booth_footer.php'; ?>
