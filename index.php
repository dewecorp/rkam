<?php
require_once __DIR__.'/config/bootstrap.php';
$url=trim($_GET['url']??'',"/");
if($url==='login'){require __DIR__.'/login.php';exit;}
if($url==='logout'){require __DIR__.'/logout.php';exit;}
if($url===''||$url==='index.php'){$url='dashboard';}
Auth::requireLogin();
$p=explode('/',$url);$page=$p[0];$arg=$p[1]??null;$arg2=$p[2]??null;
$content='';$title='Dashboard';
try{
switch($page){
case 'dashboard': Rbac::check('dashboard'); $title='Dashboard'; ob_start(); require __DIR__.'/modules/dashboard/index.php'; $content=ob_get_clean(); break;
case 'rkam':
  if($arg==='detail'&&$arg2){Rbac::check('rkam.view');$title='Detail RKAM';$_GET['id']=$arg2;ob_start();require __DIR__.'/modules/rkam/detail.php';$content=ob_get_clean();}
  else{Rbac::check('rkam.view');$title='RKAM';ob_start();require __DIR__.'/modules/rkam/list.php';$content=ob_get_clean();}
  break;
case 'realisasi': Rbac::check('realisasi.view');$title='Realisasi';ob_start();require __DIR__.'/modules/realisasi/index.php';$content=ob_get_clean();break;
case 'monitoring': Rbac::check('monitoring');$title='Monitoring';ob_start();require __DIR__.'/modules/monitoring/index.php';$content=ob_get_clean();break;
case 'laporan': Rbac::check('laporan');$ljenis=['rkam','realisasi','sumber','bidang','bulanan','transaksi'];$lj=$arg??($_GET['jenis']??'rkam');if(!in_array($lj,$ljenis))$lj='rkam';$_GET['jenis']=$lj;$lt=['rkam'=>'RKAM','realisasi'=>'Realisasi','sumber'=>'Per Sumber Dana','bidang'=>'Per Bidang','bulanan'=>'Bulanan','transaksi'=>'Transaksi'];$title='Laporan '.$lt[$lj];ob_start();require __DIR__.'/modules/laporan/index.php';$content=ob_get_clean();break;
case 'print': Rbac::check('laporan');require __DIR__.'/modules/laporan/print.php';exit;
case 'export': Rbac::check('laporan');require __DIR__.'/modules/laporan/export.php';exit;
case 'master': if(!in_array(Auth::role(),['superadmin','operator']))Rbac::deny();$mtabs=['bidang','sumber_dana','jenis_belanja','satuan','rekening','kegiatan','guru','jabatan','tahun_anggaran'];$mtab=$arg??($_GET['tab']??'bidang');if(!in_array($mtab,$mtabs))$mtab='bidang';$_GET['tab']=$mtab;$title='Master '.ucwords(str_replace('_',' ',$mtab));ob_start();require __DIR__.'/modules/master/page.php';$content=ob_get_clean();break;
case 'users': Rbac::check('dashboard'); if(Auth::role()!=='superadmin')Rbac::deny();$title='Pengguna';ob_start();require __DIR__.'/modules/users/index.php';$content=ob_get_clean();break;
case 'audit': if(!in_array(Auth::role(),['superadmin','kepala_madrasah']))Rbac::deny();$title='Audit Log';ob_start();require __DIR__.'/modules/audit/index.php';$content=ob_get_clean();break;
case 'pengaturan': if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Rbac::deny();$title='Pengaturan';ob_start();require __DIR__.'/modules/pengaturan/index.php';$content=ob_get_clean();break;
case 'backup': if(Auth::role()!=='superadmin')Rbac::deny();if($arg){require __DIR__.'/modules/backup/download.php';exit;}$title='Backup';ob_start();require __DIR__.'/modules/backup/index.php';$content=ob_get_clean();break;
case 'api':
  require __DIR__.'/modules/api/router.php';exit;
default: http_response_code(404);$title='404';$content='<div class="bg-white p-10 rounded shadow text-center"><h2 class="text-2xl font-bold">404</h2><p>Halaman tidak ditemukan.</p><a href="'.BASE_URL.'" class="text-blue-600">Kembali</a></div>';
}
}catch(Throwable $e){error_log($e->getMessage());$content='<div class="bg-red-50 border border-red-200 p-4 rounded">Terjadi kesalahan sistem.</div>';}
require __DIR__.'/views/layouts/main.php';
