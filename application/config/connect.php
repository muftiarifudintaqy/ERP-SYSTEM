<?php
$koneksi = mysqli_connect(
    app_env('CONNECT_DB_HOST'),
    app_env('CONNECT_DB_USERNAME'),
    app_env('CONNECT_DB_PASSWORD'),
    app_env('CONNECT_DB_DATABASE')
);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
echo "Koneksi berhasil!";
?>
