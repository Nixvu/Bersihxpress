<?php
session_start();
// Hapus semua session
session_unset();
session_destroy();
// Redirect ke halaman login
header('Location: auth/masuk.php');
exit();
