<?php
require_once __DIR__.'/../../config/bootstrap.php';
if(Auth::role()!=='superadmin'){http_response_code(403);die('403');}
$f=basename($arg??'');
$p=__DIR__.'/../../db_backups/'.$f;
if(!preg_match('/^rkam_[\d_]+\.sql$/',$f)||!is_file($p)){http_response_code(404);die('File tidak ada');}
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="'.$f.'"');
readfile($p);exit;
