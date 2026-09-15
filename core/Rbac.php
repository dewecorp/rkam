<?php
class Rbac{
static $map=[
'superadmin'=>['*'],
'kepala_madrasah'=>['dashboard','rkam.view','rkam.approve','rkam.lock','realisasi.view','monitoring','laporan','notif'],
'bendahara'=>['dashboard','rkam.view','rkam.create','rkam.edit','rkam.submit','realisasi.*','monitoring','laporan','notif'],
'operator'=>['dashboard','master.*','rkam.view','rkam.create','rkam.edit','rkam.submit','laporan','notif'],
'viewer'=>['dashboard','rkam.view','monitoring','laporan','notif'],
];
public static function can($perm){$r=Auth::role();if(!$r)return false;if($r==='superadmin')return true;$a=self::$map[$r]??[];foreach($a as $p){if($p===$perm)return true;if(str_ends_with($p,'.*')&&str_starts_with($perm,rtrim($p,'*')))return true;}return false;}
public static function deny(){http_response_code(403);die('Akses ditolak (403)');}
public static function check($perm){if(!Auth::check()){header('Location: '.BASE_URL.'login');exit;}if(!self::can($perm))self::deny();}
}
