<?php
class Auth{
public static function user(){return $_SESSION['user']??null;}
public static function check(){return isset($_SESSION['user_id']);}
public static function role(){return $_SESSION['role']??null;}
public static function id(){return $_SESSION['user_id']??null;}
public static function login($pdo,$u,$p){
$stmt=$pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");$stmt->execute([$u]);$r=$stmt->fetch();
if(!$r)return [false,'Username / password salah'];
if($r['status']!=='aktif')return [false,'Akun nonaktif'];
if(!empty($r['locked_until'])&&strtotime($r['locked_until'])>time())return [false,'Akun dikunci sementara. Coba lagi nanti'];
if(!password_verify($p,$r['password'])){
$stmt=$pdo->prepare("INSERT INTO login_attempts(username,ip,success) VALUES(?,?,0)");$stmt->execute([$u,$_SERVER['REMOTE_ADDR']??'']);
$f=(int)$r['failed_attempts']+1;$lock=null;
if($f>=5){$lock=date('Y-m-d H:i:s',time()+15*60);$f=0;}
$stmt=$pdo->prepare("UPDATE users SET failed_attempts=?,locked_until=? WHERE id=?");$stmt->execute([$f,$lock,$r['id']]);
return [false,'Username / password salah'];
}
$stmt=$pdo->prepare("UPDATE users SET failed_attempts=0,locked_until=NULL,last_login=NOW() WHERE id=?");$stmt->execute([$r['id']]);
$stmt=$pdo->prepare("INSERT INTO login_attempts(username,ip,success) VALUES(?,?,1)");$stmt->execute([$u,$_SERVER['REMOTE_ADDR']??'']);
session_regenerate_id(true);
$_SESSION['user_id']=$r['id'];$_SESSION['role']=$r['role'];
$_SESSION['user']=['id'=>$r['id'],'nama'=>$r['nama'],'username'=>$r['username'],'role'=>$r['role']];
return [true,'OK'];
}
public static function requireLogin(){if(!self::check()){header('Location: '.BASE_URL.'login');exit;}}
public static function logout(){$u=self::id();session_unset();session_destroy();}
}
