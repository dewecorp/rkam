<?php
// Reset password darurat via CLI: php tools/reset-password.php superadmin admin123
require_once __DIR__.'/../config/bootstrap.php';
if(PHP_SAPI!=='cli'){die('CLI only');}
$u=$argv[1]??'';$p=$argv[2]??'admin123';
if(!$u)die("Usage: php reset-password.php username newpass\n");
$s=$pdo->prepare("UPDATE users SET password=?,must_change_password=1 WHERE username=?");
$s->execute([password_hash($p,PASSWORD_DEFAULT),$u]);
echo "OK $u\n";
