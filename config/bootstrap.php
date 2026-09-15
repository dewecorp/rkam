<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__.'/app.php';
require_once __DIR__.'/database.php';
try{if(isset($pdo))$pdo->exec("SET time_zone='+07:00'");}catch(Exception $e){}
require_once __DIR__.'/../core/Security.php';
require_once __DIR__.'/../core/Auth.php';
require_once __DIR__.'/../core/CSRF.php';
require_once __DIR__.'/../core/Helper.php';
require_once __DIR__.'/../core/Logger.php';
require_once __DIR__.'/../core/Rbac.php';
Security::initSession();
set_error_handler(function($n,$s,$f,$l){error_log("[$n] $s in $f:$l");return true;});
if(isset($pdo)&&empty($GLOBALS['__notif_pruned'])){$GLOBALS['__notif_pruned']=1;try{$pdo->exec("DELETE FROM notifications WHERE created_at < (NOW() - INTERVAL 1 DAY)");}catch(Exception $e){}}
