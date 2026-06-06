<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = htmlspecialchars($_POST['nama']);
    $status = htmlspecialchars($_POST['status']);
    $ucapan = htmlspecialchars($_POST['ucapan']);

    // DI SINI: Anda bisa menambahkan query INSERT INTO ke database MySQL jika diperlukan.

    // Contoh feedback sukses sederhana menggunakan JavaScript alert
    echo "<script>
            alert('Terima kasih $nama, ucapan Anda telah kami terima!');
            window.location.href = 'index.php';
          </script>";
} else {
    header("Location: index.php");
}
?>