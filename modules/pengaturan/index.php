<?php
$m=Helper::madrasah($pdo);
$app=Helper::setting($pdo,'app_name','RKAM Madrasah');$km=Helper::setting($pdo,'kode_madrasah','MI-SF');
function lab($t,$n,$v,$ph=''){return '<div><label class="text-xs font-bold text-emerald-900">'.$t.'</label><input name="'.$n.'" value="'.Security::e($v??'').'" placeholder="'.$ph.'" class="w-full border rounded p-2 mt-1"></div>';}
?>
<div class="grid lg:grid-cols-2 gap-3">
<div class="bg-white p-4 rounded shadow text-sm"><b>Identitas Madrasah</b>
<form onsubmit="saveSet(event)" class="grid gap-2 mt-2">
<?=lab('Nama Madrasah','nama_madrasah',$m['nama_madrasah']??'','MI Salafiyah')?>
<div class="grid grid-cols-2 gap-2"><?=lab('NSM','nsm',$m['nsm']??'','111234560001')?><?=lab('NPSN','npsn',$m['npsn']??'','60700001')?></div>
<div><label class="text-xs font-bold text-emerald-900">Alamat</label><textarea name="alamat" placeholder="Jl. Pendidikan No. 10" class="w-full border rounded p-2 mt-1"><?=Security::e($m['alamat']??'')?></textarea></div>
<div class="grid grid-cols-2 gap-2"><?=lab('Desa','desa',$m['desa']??'')?><?=lab('Kecamatan','kecamatan',$m['kecamatan']??'')?><?=lab('Kabupaten','kabupaten',$m['kabupaten']??'')?><?=lab('Provinsi','provinsi',$m['provinsi']??'')?></div>
<div class="grid grid-cols-2 gap-2"><?=lab('Kode Pos','kode_pos',$m['kode_pos']??'','44191')?><?=lab('Telepon','telepon',$m['telepon']??'')?><?=lab('Email','email',$m['email']??'')?><?=lab('Nama Aplikasi','app_name',$app)?><?=lab('Nama Kepala','nama_kepala',$m['nama_kepala']??'')?><?=lab('NIP Kepala','nip_kepala',$m['nip_kepala']??'')?><?=lab('Nama Bendahara','nama_bendahara',$m['nama_bendahara']??'')?><?=lab('NIP Bendahara','nip_bendahara',$m['nip_bendahara']??'')?><?=lab('Kode Madrasah','kode_madrasah',$km,'MI-SF')?></div>
<div><label class="text-xs font-bold text-emerald-900">Logo Sekolah (JPG/PNG/WebP, max 2 MB)</label>
<div class="flex items-center gap-3 mt-1">
<?php if(!empty($m['logo'])&&is_file(__DIR__.'/../../uploads/logo/'.$m['logo'])):?><img id="logoPrev" src="<?=BASE_URL?>uploads/logo/<?=Security::e($m['logo'])?>" class="w-16 h-16 rounded-2xl object-contain bg-emerald-50 border border-emerald-200 p-1"><?php else:?><div id="logoPrev" class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-400 text-2xl"><i class="fa-solid fa-image"></i></div><?php endif;?>
<div class="flex-1"><input id="logoFile" type="file" accept=".jpg,.jpeg,.png,.webp" class="w-full border rounded p-2">
<div class="flex gap-2 mt-2"><button type="button" onclick="uploadLogo()" class="bg-emerald-700 text-white rounded px-3 py-1.5 text-xs font-bold"><i class="fa-solid fa-upload mr-1"></i> Upload</button>
<?php if(!empty($m['logo'])):?><button type="button" onclick="delLogo()" class="bg-red-100 text-red-700 border border-red-200 rounded px-3 py-1.5 text-xs font-bold"><i class="fa-solid fa-trash mr-1"></i> Hapus</button><?php endif;?></div></div></div></div>
<button class="bg-emerald-700 text-white rounded p-2 font-bold">Simpan</button></form></div>
<div class="bg-white p-4 rounded shadow text-sm"><b>Ganti Password</b>
<form onsubmit="chPass(event)" class="grid gap-2 mt-2"><div><label class="text-xs font-bold text-emerald-900">Password Lama</label><input type="password" name="old" required placeholder="••••••" class="w-full border rounded p-2 mt-1"></div><div><label class="text-xs font-bold text-emerald-900">Password Baru (min 6)</label><input type="password" name="new" required minlength="6" placeholder="••••••" class="w-full border rounded p-2 mt-1"></div><button class="bg-slate-700 text-white rounded p-2 font-bold">Ganti</button></form></div></div>
<script>
async function saveSet(e){e.preventDefault();const f=new FormData(e.target);const d={act:'setting_save'};f.forEach((v,k)=>d[k]=v);const j=await api('x',d);if(j.ok)ok('Tersimpan');else err(j.msg);}
async function chPass(e){e.preventDefault();const f=new FormData(e.target);const j=await api('x',{act:'change_pass',old:f.get('old'),new:f.get('new')});if(j.ok){ok('Password diganti');e.target.reset();}else err(j.msg);}
async function uploadLogo(){const inp=document.getElementById('logoFile');if(!inp.files.length){err('Pilih file logo dulu');return;}const f=new FormData();f.append('csrf_token',window.__CSRF||CSRF);f.append('act','logo_upload');f.append('logo',inp.files[0]);Swal.fire({title:'Mengupload...',didOpen:()=>Swal.showLoading(),allowOutsideClick:false});try{const r=await fetch((window.__BASE||BASE)+'api/x',{method:'POST',body:f});const j=await r.json();Swal.close();if(j.ok){ok('Logo tersimpan');setTimeout(()=>location.reload(),900);}else err(j.msg||'Gagal');}catch(ex){Swal.close();err('Gagal: '+ex.message);}}
async function delLogo(){if(!await ask('Hapus logo sekolah?','','Ya, Hapus'))return;const j=await api('x',{act:'logo_delete'});if(j.ok){ok('Logo dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg||'Gagal');}
</script>
