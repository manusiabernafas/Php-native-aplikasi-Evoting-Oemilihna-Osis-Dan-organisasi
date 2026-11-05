<?php
$p = pemilihan_aktif();
if (!$p) redirect('dashboard');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['aksi'] ?? '') {
            case 'tambah':
                if (!preg_match('/^\d{3,20}$/', $_POST['nis']) || trim($_POST['nama']) === '' || trim($_POST['kelas']) === '') throw new RuntimeException('NIS (angka), nama, dan kelas wajib diisi.');
                q('INSERT INTO pemilih (pemilihan_id, nis, nama, kelas, token) VALUES (?,?,?,?,?)', [$p['id'], $_POST['nis'], trim($_POST['nama']), trim($_POST['kelas']), buat_token()]);
                flash('Pemilih ditambahkan.');
                break;
            case 'import':
                // CSV: nis;nama;kelas (baris pertama boleh judul kolom). Token dibuat otomatis.
                if (empty($_FILES['csv']['tmp_name'])) throw new RuntimeException('Pilih file CSV.');
                $f = fopen($_FILES['csv']['tmp_name'], 'r');
                $baru = $lewat = 0;
                db()->beginTransaction();
                while (($row = fgetcsv($f, 0, str_contains((string) file_get_contents($_FILES['csv']['tmp_name'], false, null, 0, 300), ';') ? ';' : ',')) !== false) {
                    [$nis, $nama, $kelas] = array_map('trim', array_pad($row, 3, ''));
                    if (!preg_match('/^\d{3,20}$/', $nis) || $nama === '' || $kelas === '') { $lewat++; continue; }
                    for ($i = 0; $i < 5; $i++) {   // ulang bila token kebetulan sama (UNIQUE)
                        try { q('INSERT INTO pemilih (pemilihan_id, nis, nama, kelas, token) VALUES (?,?,?,?,?)', [$p['id'], $nis, $nama, $kelas, buat_token()]); $baru++; break; }
                        catch (PDOException $e) { if (q('SELECT 1 FROM pemilih WHERE pemilihan_id = ? AND nis = ?', [$p['id'], $nis])->fetchColumn()) { $lewat++; break; } }
                    }
                }
                db()->commit();
                flash("$baru pemilih diimpor, $lewat baris dilewati (judul kolom, data tidak valid, atau NIS sudah ada).");
                break;
            case 'token':
                $st = q('UPDATE pemilih SET token = ? WHERE id = ? AND pemilihan_id = ? AND sudah_memilih = 0', [buat_token(), (int) $_POST['id'], $p['id']]);
                flash($st->rowCount() ? 'Token baru dibuat. Cetak ulang kartunya.' : 'Pemilih sudah memilih, token tidak bisa diganti.', $st->rowCount() ? 'success' : 'error');
                break;
        }
    } catch (PDOException $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('NIS sudah terdaftar.', 'error');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('pemilih', ['kelas' => $_GET['kelas'] ?? '']);
}
$kelasList = q('SELECT kelas, COUNT(*) n, SUM(sudah_memilih) s FROM pemilih WHERE pemilihan_id = ? GROUP BY kelas ORDER BY kelas', [$p['id']])->fetchAll();
$kelas = $_GET['kelas'] ?? ($kelasList[0]['kelas'] ?? '');
$cari = trim($_GET['cari'] ?? '');
$rows = $cari !== ''
    ? q('SELECT * FROM pemilih WHERE pemilihan_id = ? AND (nis LIKE ? OR nama LIKE ?) ORDER BY kelas, nama LIMIT 100', [$p['id'], "%$cari%", "%$cari%"])->fetchAll()
    : q('SELECT * FROM pemilih WHERE pemilihan_id = ? AND kelas = ? ORDER BY nama', [$p['id'], $kelas])->fetchAll();
$title = 'Daftar Pemilih Tetap';
$label = array_sum(array_column($kelasList, 'n')) . ' pemilih · ' . count($kelasList) . ' kelas';
$actions = '<a class="btn btn-black" href="' . url('kartu', ['kelas' => $kelas]) . '" target="_blank">Cetak Kartu Token ' . e($kelas) . '</a>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="cols">
    <div class="block">
        <div class="block-head">
            <form class="filters" method="get"><input type="hidden" name="page" value="pemilih">
                <select name="kelas" onchange="this.form.submit()"><?php foreach ($kelasList as $k): ?><option <?= $kelas === $k['kelas'] && $cari === '' ? 'selected' : '' ?>><?= e($k['kelas']) ?></option><?php endforeach; ?></select>
                <input type="search" name="cari" value="<?= e($cari) ?>" placeholder="atau cari NIS / nama"></form>
            <span class="label muted"><?= count($rows) ?> siswa</span>
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Token</th><th>Status</th><th></th></tr></thead>
            <tbody><?php foreach ($rows as $r): ?>
                <tr><td class="mono"><?= e($r['nis']) ?></td><td><strong><?= e($r['nama']) ?></strong></td><td><?= e($r['kelas']) ?></td>
                    <td class="mono"><?= $r['sudah_memilih'] ? '<span class="muted">••••••</span>' : e($r['token']) ?></td>
                    <td><?= $r['sudah_memilih'] ? '<span class="tag tag-green">Sudah ' . jam($r['waktu_memilih']) . '</span>' : '<span class="tag tag-gray">Belum</span>' ?></td>
                    <td class="text-right"><?php if (!$r['sudah_memilih']): ?><form method="post" data-confirm="Buat token baru untuk <?= e($r['nama']) ?>?"><?= csrf_field() ?><input type="hidden" name="aksi" value="token"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm">Token baru</button></form><?php endif; ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div>
    <div>
        <form method="post" enctype="multipart/form-data" class="block">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="import">
            <div class="block-head"><h2>Impor dari CSV / Excel</h2></div>
            <div class="block-body">
                <p class="muted" style="margin:0 0 12px;font-size:14px">Format kolom: <b class="mono">nis;nama;kelas</b>. Simpan dari Excel sebagai CSV. Token dibuat otomatis untuk setiap siswa.</p>
                <input type="file" name="csv" accept=".csv,text/csv" required>
                <button class="btn btn-black btn-block mt" style="margin-top:14px"><?= icon('upload', 16) ?> Impor Pemilih</button>
            </div>
        </form>
        <form method="post" class="block mt">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
            <div class="block-head"><h2>Tambah satu pemilih</h2></div>
            <div class="block-body"><div class="form-grid">
                <div><label>NIS</label><input type="text" name="nis" required></div><div><label>Kelas</label><input type="text" name="kelas" value="<?= e($kelas) ?>" required></div>
                <div class="full"><label>Nama</label><input type="text" name="nama" required></div>
            </div><button class="btn btn-block mt" style="margin-top:16px">Tambah</button></div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
