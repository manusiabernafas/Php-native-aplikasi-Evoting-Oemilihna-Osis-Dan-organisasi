# E-Voting Pemilihan Ketua OSIS & Organisasi (PHP Native + MySQL)

Aplikasi E-Voting berbasis PHP Native dan MySQL untuk pemilihan Ketua OSIS, ketua kelas, BEM, HIMA, karang taruna, koperasi, atau organisasi apa pun. Pemilih masuk ke bilik suara digital dengan NIS dan token unik, memilih salah satu pasangan calon, lalu bilik otomatis kembali siap untuk pemilih berikutnya, cocok dipasang di beberapa laptop sekaligus dalam mode kios.

Sistem dirancang agar suara benar-benar rahasia dan tidak bisa dicurangi: tabel suara sama sekali tidak menyimpan identitas pemilih, dan satu token hanya bisa dipakai sekali walaupun dikirim dari dua perangkat bersamaan. Panitia memantau partisipasi per kelas secara langsung, mencetak kartu token, mengimpor daftar pemilih dari Excel, lalu mencetak rekap dan berita acara setelah pemilihan ditutup. Tampilannya bergaya Swiss yang tegas, bersih, dan mudah dibaca.

## Fitur

- Bilik suara digital mode kios: login NIS + token, pilih, konfirmasi, otomatis kembali ke awal
- Suara rahasia: tabel suara tanpa identitas pemilih, ID acak, waktu dibulatkan ke jam
- Anti suara ganda: satu token hanya berlaku sekali, aman walau dikirim dari dua perangkat bersamaan
- Batas percobaan login token (anti tebak token)
- Jadwal buka dan tutup otomatis, plus tombol tutup pemilihan darurat
- Pasangan calon dengan nomor urut, foto, visi, dan misi
- Data kandidat terkunci otomatis setelah bilik dibuka
- Daftar pemilih tetap per kelas, impor dari CSV/Excel
- Token 6 karakter otomatis tanpa karakter yang membingungkan (O/0, I/1/L)
- Cetak kartu token per kelas siap gunting
- Ganti token pemilih yang belum memilih (bila kartu hilang)
- Monitor partisipasi per kelas secara langsung, kelas di bawah 60% ditandai
- Grafik arus pemilih per jam
- Cek integritas otomatis: jumlah suara = jumlah pemilih yang sudah memilih
- Rekap perolehan suara hanya untuk admin selama pemilihan berlangsung
- Hasil akhir dan berita acara siap cetak setelah pemilihan ditutup
- Opsi tampilkan hasil ke publik di halaman bilik
- Riwayat pemilihan (bisa dipakai untuk pemilihan tahun berikutnya)
- 2 peran: Admin (pembina) dan Panitia
- Data contoh 448 pemilih di 16 kelas dan 3 pasangan calon, tampilan Swiss style yang responsif

## Instalasi

**Kebutuhan:** PHP 8.0+ (ekstensi `fileinfo` aktif) dan MySQL/MariaDB (XAMPP atau Laragon).

1. Ekstrak ZIP, lalu pindahkan folder `e-voting` ke:
   - XAMPP: `C:\xampp\htdocs\e-voting`
   - Laragon: `C:\laragon\www\e-voting`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/e_voting.sql` → **Import**.

   Atau lewat terminal:
   ```bash
   mysql -u root -p < database/e_voting.sql
   ```
   Pemilihan contoh otomatis dibuka hari ini jam 07.00 sampai besok jam 14.00.

4. Sesuaikan `config/database.php` jika user/password MySQL kamu berbeda:
   ```php
   return [
       'host'     => 'localhost',
       'port'     => 3306,
       'database' => 'e_voting',
       'username' => 'root',
       'password' => '',
   ];
   ```

5. **Bilik suara:** `http://localhost/e-voting` (halaman awal). Coba pakai NIS dan token dari menu Daftar Pemilih.

6. **Panel panitia:** `http://localhost/e-voting/index.php?page=login`
   - Admin / pembina: `admin` / `admin123`
   - Panitia: `panitia` / `admin123`

7. Untuk pemilihan sungguhan: buka **Pengaturan** → buat pemilihan baru dan atur jadwal → isi **Kandidat** → impor **Daftar Pemilih** dari CSV → cetak kartu token.

**Tips:** pakai beberapa laptop sebagai bilik suara, cukup buka alamat aplikasi di browser masing-masing (satu jaringan WiFi/LAN dengan komputer server).

## Contoh file CSV pemilih

```
nis;nama;kelas
26001;Budi Santoso;X 1
26002;Siti Aminah;X 1
```

---
Source code ini dibagikan gratis oleh **Papicode** — https://papicode.id
