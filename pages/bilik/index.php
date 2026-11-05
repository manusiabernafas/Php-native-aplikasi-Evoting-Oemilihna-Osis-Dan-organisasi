<?php
$p = pemilihan_aktif();
$status = $p ? status_pemilihan($p) : 'tidak-ada';
$error = null;

// Langkah 1: pemilih masuk dengan NIS + token
if (!pemilih() && $_SERVER['REQUEST_METHOD'] === 'POST' && $p) {
    $_SESSION['coba'] = array_filter($_SESSION['coba'] ?? [], fn ($t) => $t > time() - 600);
    if (count($_SESSION['coba']) >= 5) {
        $error = 'Terlalu banyak percobaan. Tunggu 10 menit atau hubungi panitia.';
    } elseif ($status !== 'buka') {
        $error = 'Bilik suara sedang tidak dibuka.';
    } else {
        $v = q('SELECT * FROM pemilih WHERE pemilihan_id = ? AND nis = ?', [$p['id'], trim($_POST['nis'] ?? '')])->fetch();
        if (!$v || !hash_equals($v['token'], strtoupper(trim($_POST['token'] ?? '')))) {
            $_SESSION['coba'][] = time();
            $error = 'NIS atau token tidak cocok.';
        } elseif ($v['sudah_memilih']) {
            $error = 'Kamu sudah menggunakan hak pilih pada ' . tgl($v['waktu_memilih']) . ' pukul ' . jam($v['waktu_memilih']) . '.';
        } else {
            session_regenerate_id(true);
            $_SESSION['pemilih'] = ['id' => $v['id'], 'nama' => $v['nama'], 'kelas' => $v['kelas'], 'pemilihan_id' => $p['id']];
            redirect('bilik');
        }
    }
}

$kandidat = $p ? q('SELECT * FROM kandidat WHERE pemilihan_id = ? ORDER BY no_urut', [$p['id']])->fetchAll() : [];
$title = 'Bilik Suara';
require __DIR__ . '/../../includes/booth_header.php';

if (!pemilih()): ?>
    <form method="post" class="booth-login">
        <?= csrf_field() ?>
        <span class="label" style="color:var(--red)"><?= $status === 'buka' ? 'Bilik suara dibuka' : ($status === 'belum' ? 'Belum dibuka' : 'Pemilihan ditutup') ?></span>
        <h1 style="margin-top:8px">Gunakan hak pilihmu.</h1>
        <?php if ($p): ?><p class="muted" style="margin:0 0 24px"><?= tgl($p['mulai']) ?> <?= jam($p['mulai']) ?> – <?= tgl($p['selesai']) ?> <?= jam($p['selesai']) ?></p><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($status === 'buka'): ?>
            <div style="margin-bottom:14px"><label>NIS</label><input type="text" name="nis" inputmode="numeric" required autofocus autocomplete="off"></div>
            <div style="margin-bottom:22px"><label>Token</label><input type="text" name="token" class="token-input" maxlength="6" required autocomplete="off" placeholder="······"></div>
            <button class="btn btn-red btn-block"><?= icon('lock', 16) ?> Masuk ke Bilik</button>
            <p class="muted" style="font-size:13px;margin:16px 0 0">Token dibagikan panitia pada kartu pemilih. Pilihanmu bersifat rahasia: sistem tidak menyimpan siapa memilih siapa.</p>
        <?php elseif ($status === 'tutup' && setting()['hasil_publik']): ?>
            <a class="btn btn-black btn-block" href="<?= url('hasil') ?>"><?= icon('trophy', 16) ?> Lihat Hasil Pemilihan</a>
        <?php endif; ?>
        <p style="text-align:center;margin:22px 0 0;font-size:13px"><a href="<?= url('login') ?>">Login panitia</a></p>
    </form>
<?php else: ?>
    <div class="hero" style="grid-template-columns:1fr">
        <div><span class="label" style="color:var(--red)">Halo, <?= e(pemilih()['nama']) ?> · <?= e(pemilih()['kelas']) ?></span><h1>Pilih satu pasangan calon.</h1></div>
    </div>
    <form method="post" action="<?= url('bilik.pilih') ?>" id="form-suara">
        <?= csrf_field() ?>
        <div class="candidates">
            <?php foreach ($kandidat as $k): ?>
                <label class="cand pick">
                    <input type="radio" name="kandidat_id" value="<?= $k['id'] ?>" required data-nama="<?= e($k['no_urut'] . '. ' . $k['ketua'] . ($k['wakil'] ? ' & ' . $k['wakil'] : '')) ?>">
                    <div class="no"><?= str_pad((string) $k['no_urut'], 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="photo"><?php if ($k['foto']): ?><img src="assets/uploads/<?= e($k['foto']) ?>" alt=""><?php else: ?><span class="ini"><?= e(strtoupper(substr($k['ketua'], 0, 1) . substr((string) $k['wakil'], 0, 1))) ?></span><?php endif; ?></div>
                    <h3><?= e($k['ketua']) ?></h3><div class="sub">Ketua · <?= e($k['kelas_ketua']) ?></div>
                    <?php if ($k['wakil']): ?><h3 style="margin-top:8px;font-size:18px"><?= e($k['wakil']) ?></h3><div class="sub">Wakil · <?= e($k['kelas_wakil']) ?></div><?php endif; ?>
                    <div class="visi"><?= e($k['visi']) ?></div>
                    <ul><?php foreach (array_filter(explode("\n", (string) $k['misi'])) as $m): ?><li><?= e(trim($m)) ?></li><?php endforeach; ?></ul>
                    <div class="grow"></div>
                    <div class="choose">Pilih nomor <?= (int) $k['no_urut'] ?></div>
                </label>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:22px"><button class="btn btn-red" style="font-size:17px;padding:16px 26px"><?= icon('check', 18) ?> Kirim Suara</button></div>
    </form>
    <script>
    document.getElementById('form-suara').addEventListener('submit', function (e) {
        var c = this.querySelector('input[name=kandidat_id]:checked');
        if (!c || !confirm('Yakin memilih pasangan ' + c.dataset.nama + '?\nSuara yang sudah dikirim tidak bisa diubah.')) e.preventDefault();
    });
    </script>
<?php endif;
require __DIR__ . '/../../includes/booth_footer.php';
