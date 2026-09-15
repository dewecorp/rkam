<?php
require_once __DIR__.'/config/bootstrap.php';
if(Auth::check()){header('Location: '.BASE_URL);exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
CSRF::check();
[$ok,$msg]=Auth::login($pdo,$_POST['username']??'',$_POST['password']??'');
if($ok){Logger::log($pdo,'login','auth');header('Location: '.BASE_URL);exit;}
$err=$msg;Logger::log($pdo,'login_gagal','auth',null,null,['u'=>$_POST['username']??'']);
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<title>Login | <?=Security::e(Helper::setting($pdo,'app_name','RKAM Madrasah'))?></title>
<style>input{border:1.5px solid #6ee7b7 !important;border-radius:.75rem !important}input:focus{outline:none !important;border-color:#059669 !important;box-shadow:0 0 0 3px rgba(16,185,129,.18) !important}</style></head>
<body class="bg-gradient-to-br from-emerald-700 to-slate-900 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
<div class="text-center mb-6"><i class="fa-solid fa-mosque text-4xl text-emerald-700"></i>
<h1 class="text-2xl font-bold mt-2"><?=Security::e(Helper::setting($pdo,'app_name','RKAM Madrasah'))?></h1>
<p class="text-sm text-gray-500">Rencana Kegiatan & Anggaran Madrasah</p></div>
<form method="POST" class="space-y-4"><?=CSRF::field()?>
<div><label class="text-sm font-semibold">Username</label><input name="username" required class="w-full border rounded-lg p-2.5 mt-1" placeholder="superadmin"></div>
<div><label class="text-sm font-semibold">Password</label><input type="password" name="password" required class="w-full border rounded-lg p-2.5 mt-1" placeholder="••••••"></div>
<button class="w-full bg-emerald-700 text-white rounded-lg p-2.5 font-semibold hover:bg-emerald-800">Masuk</button></form>
<div class="mt-4 text-xs text-gray-500 bg-gray-50 p-3 rounded">Demo: superadmin/admin123 • kepala/kepala123 • bendahara/bendahara123 • operator/operator123 • viewer/viewer123</div></div>
<?php if($err):?><script>Swal.fire({icon:'error',title:'Login gagal',text:<?=json_encode($err)?>});</script><?php endif;?>
</body></html>
