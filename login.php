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
<?php $m=Helper::madrasah($pdo); $appNm=Helper::setting($pdo,'app_name','SIRKAM'); ?>
<title>Login | <?=Security::e($appNm)?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',system-ui,sans-serif}input{border:1.5px solid #6ee7b7 !important;border-radius:.9rem !important}input:focus{outline:none !important;border-color:#059669 !important;box-shadow:0 0 0 3px rgba(16,185,129,.18) !important}</style></head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900">
<div class="w-full max-w-4xl grid md:grid-cols-2 overflow-hidden rounded-3xl shadow-2xl bg-white">
<div class="hidden md:flex flex-col justify-center p-8 text-white bg-gradient-to-br from-emerald-600 to-emerald-800">
<div><?php if(!empty($m['logo'])&&is_file(__DIR__.'/uploads/logo/'.$m['logo'])):?><img src="uploads/logo/<?=Security::e($m['logo'])?>" class="w-14 h-14 rounded-2xl object-contain bg-white p-1"><?php else:?><div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-3xl"><i class="fa-solid fa-mosque"></i></div><?php endif;?>
<h1 class="text-5xl font-black tracking-tight mt-4"><?=Security::e($appNm)?></h1>
<div class="text-base font-bold text-white mt-2 uppercase tracking-widest"><?=Security::e($m['nama_madrasah']??'')?></div>
<p class="text-xs text-emerald-100 mt-1">Rencana Kegiatan & Anggaran Madrasah</p></div></div>
<div class="p-8">
<div class="md:hidden text-center mb-6"><?php if(!empty($m['logo'])&&is_file(__DIR__.'/uploads/logo/'.$m['logo'])):?><img src="uploads/logo/<?=Security::e($m['logo'])?>" class="w-12 h-12 rounded-2xl object-contain bg-emerald-50 border border-emerald-200 p-1 mx-auto"><?php else:?><i class="fa-solid fa-mosque text-3xl text-emerald-700"></i><?php endif;?>
<h1 class="text-4xl font-black tracking-tight mt-2"><?=Security::e($appNm)?></h1><div class="text-sm font-bold text-emerald-800 uppercase tracking-widest"><?=Security::e($m['nama_madrasah']??'')?></div></div>
<h2 class="text-xl font-extrabold text-slate-800">Masuk</h2>
<p class="text-sm text-gray-500 mb-5">Gunakan akun madrasah Anda</p>
<form method="POST" class="space-y-4"><?=CSRF::field()?>
<div><label class="text-xs font-bold text-emerald-900">Username</label><input name="username" required autocomplete="username" class="w-full p-2.5 mt-1" placeholder="Username"></div>
<div><label class="text-xs font-bold text-emerald-900">Password</label><div class="relative mt-1"><input id="pwd" type="password" name="password" required autocomplete="current-password" class="w-full p-2.5 pr-11" placeholder="Password"><button type="button" onclick="togglePwd()" title="Tampil/Sembunyi" class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-700"><i id="pwdEye" class="fa-solid fa-eye"></i></button></div></div>
<button class="w-full bg-emerald-700 text-white rounded-xl p-2.5 font-bold hover:bg-emerald-800">Masuk</button></form></div></div>
<script>function togglePwd(){const i=document.getElementById('pwd'),e=document.getElementById('pwdEye');if(!i)return;const show=i.type==='password';i.type=show?'text':'password';if(e)e.className=show?'fa-solid fa-eye-slash':'fa-solid fa-eye';}</script>
<?php if($err):?><script>Swal.fire({icon:'error',title:'Login gagal',text:<?=json_encode($err)?>});</script><?php endif;?>
</body></html>
