<?php
class Security{
public static function initSession(){
if(session_status()===PHP_SESSION_NONE){
$isHttps=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>$isHttps]);
session_start();
}
if(isset($_SESSION['LAST_ACTIVITY'])&&(time()-$_SESSION['LAST_ACTIVITY']>7200)){session_unset();session_destroy();self::initSession();}
$_SESSION['LAST_ACTIVITY']=time();
}
public static function e($s){return htmlspecialchars((string)($s??''),ENT_QUOTES,'UTF-8');}
public static function rupiah($n){return 'Rp '.number_format((float)$n,0,',','.');}
public static function num($v){$v=preg_replace('/[^0-9.\-]/','',(string)$v);return $v===''||!is_numeric($v)?0:(float)$v;}
public static function int($v){return (int)self::num($v);}
public static function money($v){return round(self::num($v),2);}
public static function romawi($m){$r=['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];return $r[max(1,min(12,(int)$m))-1];}
public static function json($d,$c=200){http_response_code($c);header('Content-Type: application/json');echo json_encode($d);exit;}
public static function onlyPost(){if($_SERVER['REQUEST_METHOD']!=='POST'){self::json(['ok'=>false,'msg'=>'Metode tidak valid'],405);}}
}
