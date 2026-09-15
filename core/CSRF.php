<?php
class CSRF{
public static function token(){if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));return $_SESSION['csrf_token'];}
public static function field(){return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(self::token(),ENT_QUOTES).'">';}
public static function verify($t){return isset($_SESSION['csrf_token'])&&is_string($t)&&hash_equals($_SESSION['csrf_token'],$t);}
public static function check(){ $t=$_POST['csrf_token']??$_SERVER['HTTP_X_CSRF_TOKEN']??''; if(!self::verify($t)){http_response_code(403);die('CSRF token invalid');} }
}
