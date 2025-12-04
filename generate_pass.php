<?php
// Ganti 'password123' dengan password baru yang Anda inginkan.
$newPassword = 'password123';

// Membuat hash dari password baru.
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Menampilkan informasi di layar.
echo "<p>Gunakan password ini untuk login: <strong>" . htmlspecialchars($newPassword) . "</strong></p>";
echo "<p>Copy dan paste hash di bawah ini ke kolom 'password' di tabel 'users' pada database Anda:</p>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hashedPassword) . "</textarea>";
?>
<style>
    body { font-family: sans-serif; padding: 2em; background-color: #f4f4f9; }
    textarea { padding: 10px; font-family: monospace; border: 1px solid #ccc; border-radius: 4px; }
</style>
