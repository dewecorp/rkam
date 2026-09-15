<?php
class Logger{
public static function log($pdo,$aksi,$modul,$rid=null,$before=null,$after=null){
try{$s=$pdo->prepare("INSERT INTO activity_logs(user_id,aktivitas,modul,record_id,data_before,data_after,ip,user_agent) VALUES(?,?,?,?,?,?,?,?)");
$s->execute([Auth::id(),$aksi,$modul,$rid,is_array($before)?json_encode($before):$before,is_array($after)?json_encode($after):$after,$_SERVER['REMOTE_ADDR']??'',substr($_SERVER['HTTP_USER_AGENT']??'',0,250)]);}catch(Exception $e){error_log($e->getMessage());}
}
public static function notify($pdo,$judul,$pesan,$role=null,$uid=null,$modul=null,$rid=null){
try{$s=$pdo->prepare("INSERT INTO notifications(user_id,role_target,judul,pesan,modul,record_id) VALUES(?,?,?,?,?,?)");$s->execute([$uid,$role,$judul,$pesan,$modul,$rid]);}catch(Exception $e){}
}
}
