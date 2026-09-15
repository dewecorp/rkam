<?php
define('DB_HOST','localhost');
define('DB_NAME','rkam_db');
define('DB_USER','root');
define('DB_PASS','');
try{
$p=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$pdo=$p;
}catch(PDOException $e){
error_log($e->getMessage());
http_response_code(500);
die('Koneksi database gagal. Cek config/database.php');
}
