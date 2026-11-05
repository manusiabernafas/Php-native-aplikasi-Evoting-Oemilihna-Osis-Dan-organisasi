<?php
$p = pemilihan_aktif();
$admin = user()['role'] === 'admin';
$terkunci = $p && status_pemilihan($p) !== 'belum';   // kandidat tidak boleh diubah setelah pemilihan dibuka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && $p) {
    try {
        if ($terkunci) throw new RuntimeException('Kandidat tidak bisa diubah setelah bilik suara dibuka.');
        $id = (int) ($_POST['id'] ?? 0);
        if (($_POST['aksi'] ?? '') === 'hapus') {
            q('DELETE FROM kandidat WHERE id = ? AND pemilihan_id = ?', [$id, $p['id']]);
            flash('Kandidat dihapus.');
            redirect('kandidat');
        }
        $foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto']['tmp_name']);
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if (!$ext || $_FILES['foto']['size'] > 2 * 1024 * 1024) throw new RuntimeException('Foto harus JPG/PNG/WEBP maksimal 2 MB.');
            $foto = 'kandidat_' . bin2hex(random_bytes(6)) . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/../../assets/uploads/' . $foto);
        }
        $v = [(int) $_POST['no_urut'], trim($_POST['ketua']), trim($_POST['kelas_ketua']), trim($_POST['wakil']), trim($_POST['kelas_wakil']), trim($_POST['visi']), trim($_POST['misi'])];
        if ($v[0] < 1 || $v[1] === '') throw new RuntimeException('Nomor urut dan nama ketua wajib diisi.');
        if ($id) {
            q('UPDATE kandidat SET no_urut=?, ketua=?, kelas_ketua=?, wakil=?, kelas_wakil=?, visi=?, misi=?' . ($foto ? ', foto=?' : '') . ' WHERE id=? AND pemilihan_id=?', [...$v, ...($foto ? [$foto] : []), $id, $p['id']]);
        } else {
            q('INSERT INTO kandidat (pemilihan_id, no_urut, ketua, kelas_ketua, wakil, kelas_wakil, visi, misi, foto) VALUES (?,?,?,?,?,?,?,?,?)', [$p['id'], ...$v, $foto]);
        }
        flash('Data kandidat disimpan.');
    } catch (PDOException $e) {
        flash('Nomor urut sudah dipakai kandidat lain.', 'error');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('kandidat');
}
$kandidat = $p ? q('SELECT * FROM kandidat WHERE pemilihan_id = ? ORDER BY no_urut', [$p['id']])->fetchAll() : [];
$edit = isset($_GET['edit']) ? q('SELECT * FROM kandidat WHERE id = ?', [(int) $_GET['edit']])->fetch() : null;
$title = 'Pasangan Calon';
$label = count($kandidat) . ' pasangan' . ($terkunci ? ' · terkunci karena pemilihan sudah dibuka' : '');
require __DIR__ . '/../../includes/header.php';
?>
<div class="candidates">
    <?php foreach ($kandidat as $k): ?>
        <div class="cand">
            <div class="no"><?= str_pad((string) $k['no_urut'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="photo"><?php if ($k['foto']): ?><img src="assets/uploads/<?= e($k['foto']) ?>" alt=""><?php else: ?><span class="ini"><?= e(strtoupper(substr($k['ketua'], 0, 1) . substr((string) $k['wakil'], 0, 1))) ?></span><?php endif; ?></div>
            <h3><?= e($k['ketua']) ?></h3><div class="sub">Ketua · <?= e($k['kelas_ketua']) ?></div>
            <?php if ($k['wakil']): ?><h3 style="margin-top:8px;font-size:18px"><?= e($k['wakil']) ?></h3><div class="sub">Wakil · <?= e($k['kelas_wakil']) ?></div><?php endif; ?>
            <div class="visi"><?= e($k['visi']) ?></div>
            <ul><?php foreach (array_filter(explode("\n", (string) $k['misi'])) as $m): ?><li><?= e(trim($m)) ?></li><?php endforeach; ?></ul>
            <?php if ($admin && !$terkunci): ?><div class="filters"><a class="btn btn-sm" href="<?= url('kandidat', ['edit' => $k['id']]) ?>">Ubah</a>
                <form method="post" data-confirm="Hapus pasangan nomor <?= $k['no_urut'] ?>?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $k['id'] ?>"><button class="btn btn-sm">Hapus</button></form></div><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($admin && !$terkunci): ?>
<form method="post" enctype="multipart/form-data" class="block mt" style="max-width:900px">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <div class="block-head"><h2><?= $edit ? 'Ubah pasangan nomor ' . $edit['no_urut'] : 'Tambah pasangan calon' ?></h2></div>
    <div class="block-body"><div class="form-grid">
        <div><label>Nomor urut</label><input type="number" name="no_urut" min="1" value="<?= (int) ($edit['no_urut'] ?? count($kandidat) + 1) ?>" required></div>
        <div><label>Foto pasangan (opsional)</label><input type="file" name="foto" accept="image/*"></div>
        <div><label>Nama ketua</label><input type="text" name="ketua" value="<?= e($edit['ketua'] ?? '') ?>" required></div>
        <div><label>Kelas ketua</label><input type="text" name="kelas_ketua" value="<?= e($edit['kelas_ketua'] ?? '') ?>"></div>
        <div><label>Nama wakil</label><input type="text" name="wakil" value="<?= e($edit['wakil'] ?? '') ?>"></div>
        <div><label>Kelas wakil</label><input type="text" name="kelas_wakil" value="<?= e($edit['kelas_wakil'] ?? '') ?>"></div>
        <div class="full"><label>Visi</label><input type="text" name="visi" value="<?= e($edit['visi'] ?? '') ?>"></div>
        <div class="full"><label>Misi (satu baris satu poin)</label><textarea name="misi" rows="4"><?= e($edit['misi'] ?? '') ?></textarea></div>
    </div><div class="form-actions"><button class="btn btn-black">Simpan</button></div></div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
