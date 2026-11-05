<?php
require_admin();
$p = pemilihan_aktif();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['aksi'] ?? '') {
            case 'jadwal':
                $mulai = date('Y-m-d H:i:s', strtotime($_POST['mulai']));
                $selesai = date('Y-m-d H:i:s', strtotime($_POST['selesai']));
                if ($selesai <= $mulai) throw new RuntimeException('Waktu selesai harus setelah waktu mulai.');
                q('UPDATE pemilihan SET judul = ?, mulai = ?, selesai = ? WHERE id = ?', [trim($_POST['judul']), $mulai, $selesai, $p['id']]);
                break;
            case 'tutup':
                q('UPDATE pemilihan SET selesai = NOW() WHERE id = ? AND selesai > NOW()', [$p['id']]);
                break;
            case 'baru':
                db()->beginTransaction();
                q('UPDATE pemilihan SET aktif = 0');
                q('INSERT INTO pemilihan (judul, mulai, selesai, aktif) VALUES (?,?,?,1)', [trim($_POST['judul']), date('Y-m-d 07:00:00', strtotime('+7 days')), date('Y-m-d 14:00:00', strtotime('+7 days')), ]);
                db()->commit();
                break;
            case 'umum':
                q('UPDATE pengaturan SET nama_instansi = ?, hasil_publik = ? WHERE id = 1', [trim($_POST['nama_instansi']), isset($_POST['hasil_publik']) ? 1 : 0]);
                break;
            case 'user':
                if (strlen($_POST['password']) < 6) throw new RuntimeException('Password minimal 6 karakter.');
                q('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)', [trim($_POST['nama']), trim($_POST['username']), password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role'] === 'admin' ? 'admin' : 'panitia']);
                break;
        }
        flash('Pengaturan disimpan.');
    } catch (PDOException $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('Gagal: username sudah ada.', 'error');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('pengaturan');
}
$set = setting();
$users = q('SELECT * FROM users ORDER BY id')->fetchAll();
$riwayat = q('SELECT p.*, (SELECT COUNT(*) FROM suara s WHERE s.pemilihan_id = p.id) suara FROM pemilihan p ORDER BY p.id DESC')->fetchAll();
$title = 'Pengaturan';
require __DIR__ . '/../../includes/header.php';
?>
<div class="cols">
    <form method="post" class="block"><?= csrf_field() ?><input type="hidden" name="aksi" value="jadwal">
        <div class="block-head"><h2>Pemilihan aktif</h2><span class="tag <?= status_pemilihan($p) === 'buka' ? 'tag-green' : 'tag-gray' ?>"><?= status_pemilihan($p) ?></span></div>
        <div class="block-body"><div class="form-grid">
            <div class="full"><label>Judul</label><input type="text" name="judul" value="<?= e($p['judul']) ?>" required></div>
            <div><label>Dibuka</label><input type="datetime-local" name="mulai" value="<?= date('Y-m-d\TH:i', strtotime($p['mulai'])) ?>"></div>
            <div><label>Ditutup</label><input type="datetime-local" name="selesai" value="<?= date('Y-m-d\TH:i', strtotime($p['selesai'])) ?>"></div>
        </div><div class="form-actions"><button class="btn btn-black">Simpan Jadwal</button></div></div>
    </form>
    <div>
        <form method="post" class="block"><?= csrf_field() ?><input type="hidden" name="aksi" value="umum">
            <div class="block-head"><h2>Umum</h2></div>
            <div class="block-body"><label>Nama sekolah / organisasi</label><input type="text" name="nama_instansi" value="<?= e($set['nama_instansi']) ?>">
                <label style="margin-top:16px;display:flex;gap:8px;align-items:center;text-transform:none;letter-spacing:0;font-size:14px;font-weight:600"><input type="checkbox" name="hasil_publik" <?= $set['hasil_publik'] ? 'checked' : '' ?>> Tampilkan hasil di bilik setelah ditutup</label>
                <button class="btn btn-block mt" style="margin-top:16px">Simpan</button></div>
        </form>
        <form method="post" class="block mt" data-confirm="Tutup bilik suara sekarang? Pemilih tidak bisa lagi memilih."><?= csrf_field() ?><input type="hidden" name="aksi" value="tutup">
            <div class="block-body"><button class="btn btn-red btn-block" <?= status_pemilihan($p) === 'tutup' ? 'disabled' : '' ?>><?= icon('lock', 16) ?> Tutup Pemilihan Sekarang</button></div>
        </form>
    </div>
</div>
<div class="cols mt">
    <div class="block"><div class="block-head"><h2>Riwayat pemilihan</h2></div>
        <div class="table-wrap"><table><tbody><?php foreach ($riwayat as $r): ?><tr><td><strong><?= e($r['judul']) ?></strong><small><?= tgl($r['mulai']) ?></small></td><td><?= $r['suara'] ?> suara</td><td><?= $r['aktif'] ? '<span class="tag tag-red">Aktif</span>' : '' ?></td></tr><?php endforeach; ?></tbody></table></div>
        <form method="post" class="block-body filters" style="border-top:1px solid var(--hair)" data-confirm="Buat pemilihan baru? Pemilihan sekarang menjadi arsip."><?= csrf_field() ?><input type="hidden" name="aksi" value="baru"><input type="text" name="judul" placeholder="Judul pemilihan baru" required style="flex:1"><button class="btn btn-sm">Buat</button></form>
    </div>
    <div class="block"><div class="block-head"><h2>Akun panitia</h2></div>
        <div class="table-wrap"><table><tbody><?php foreach ($users as $u): ?><tr><td><strong><?= e($u['nama']) ?></strong><small><?= e($u['username']) ?></small></td><td class="text-right"><span class="tag tag-gray"><?= e($u['role']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
        <form method="post" class="block-body filters" style="border-top:1px solid var(--hair)"><?= csrf_field() ?><input type="hidden" name="aksi" value="user"><input type="text" name="nama" placeholder="Nama" required><input type="text" name="username" placeholder="Username" required><input type="password" name="password" placeholder="Password" minlength="6" required><select name="role"><option value="panitia">Panitia</option><option value="admin">Admin</option></select><button class="btn btn-sm">Tambah</button></form>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
