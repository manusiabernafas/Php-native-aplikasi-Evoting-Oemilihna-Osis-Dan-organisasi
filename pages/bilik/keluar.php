<?php
unset($_SESSION['pemilih']);
flash('Kamu keluar tanpa memilih. Token masih bisa digunakan.', 'success');
redirect('bilik');
