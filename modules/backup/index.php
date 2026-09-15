<?php
$last=Helper::setting($pdo,'last_backup_at','-');
$bdir=__DIR__.'/../../db_backups/';
$files=is_dir($bdir)?glob($bdir.'*.sql'):[];
rsort($files);
function fsize($b){if($b<1024)return $b.' B';if($b<1048576)return round($b/1024,1).' KB';return round($b/1048576,2).' MB';}
$total=array_sum(array_map(fn($f)=>is_file($f)?filesize($f):0,(array)$files));
?>
<div class="grid lg:grid-cols-2 gap-4 mb-4">
<div class="bg-white p-5 rounded-2xl shadow border border-emerald-100">
<div class="flex items-center gap-3 mb-2"><div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white flex items-center justify-center text-xl"><i class="fa-solid fa-floppy-disk"></i></div>
<div><div class="font-extrabold">Backup Database</div><div class="text-xs text-gray-500">Simpan salinan seluruh data RKAM</div></div></div>
<div class="grid grid-cols-3 gap-2 text-center text-sm my-3">
<div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2"><div class="text-[11px] text-gray-500">Terakhir</div><div class="font-bold text-xs"><?=Security::e($last)?></div></div>
<div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2"><div class="text-[11px] text-gray-500">File</div><div class="font-bold"><?=count($files)?></div></div>
<div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2"><div class="text-[11px] text-gray-500">Total</div><div class="font-bold"><?=fsize($total)?></div></div></div>
<button id="btnBackup" onclick="runBackup()" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl p-2.5 font-bold"><i class="fa-solid fa-download mr-1"></i> Backup Sekarang</button></div>
<div class="bg-white p-5 rounded-2xl shadow border border-amber-100">
<div class="flex items-center gap-3 mb-2"><div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center text-xl"><i class="fa-solid fa-upload"></i></div>
<div><div class="font-extrabold">Restore Database</div><div class="text-xs text-gray-500">Kembalikan data dari file .sql</div></div></div>
<div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-2 mb-3"><b>Peringatan:</b> restore menimpa seluruh data saat ini. Buat backup dulu sebelum restore.</div>
<form onsubmit="runRestore(event)" class="space-y-2 text-sm">
<div><label class="text-xs font-bold text-emerald-900">File Backup (.sql, max 20 MB)</label><input id="restoreFile" type="file" name="file" accept=".sql" required class="w-full border rounded-xl p-2 mt-1"></div>
<button class="w-full bg-amber-600 hover:bg-amber-700 text-white rounded-xl p-2.5 font-bold"><i class="fa-solid fa-rotate-left mr-1"></i> Restore Sekarang</button></form></div></div>
<div class="bg-white rounded-2xl shadow border border-emerald-100 overflow-hidden">
<div class="p-4 border-b border-emerald-100 font-extrabold">Daftar File Backup</div>
<div class="overflow-auto"><table class="w-full text-sm min-w-[640px]"><thead><tr class="bg-emerald-50 text-left"><th class="p-3 w-12">No</th><th class="p-3">Nama Backup</th><th class="p-3 w-32">Ukuran</th><th class="p-3 w-44">Aksi</th></tr></thead><tbody>
<?php if(!$files):?><tr><td colspan="4" class="p-8 text-center text-gray-500">Belum ada file backup. Klik "Backup Sekarang".</td></tr><?php endif;?>
<?php $no=1;foreach((array)$files as $f):$b=basename($f);$sz=is_file($f)?filesize($f):0;?>
<tr class="border-t hover:bg-emerald-50/50"><td class="p-3"><?=$no++?></td><td class="p-3 font-medium text-emerald-900"><?=Security::e($b)?></td><td class="p-3"><?=fsize($sz)?></td>
<td class="p-3 whitespace-nowrap"><a href="<?=BASE_URL?>backup/<?=Security::e($b)?>" title="Unduh" class="btn-ic btn-dl"><i class="fa-solid fa-download"></i></a> <button onclick="delBackup('<?=Security::e($b)?>')" title="Hapus" class="btn-ic btn-del"><i class="fa-solid fa-trash-can"></i></button></td></tr>
<?php endforeach;?></tbody></table></div></div>
<script>
async function runBackup(){if(!await ask('Buat backup database sekarang?','Salin seluruh struktur + data ke file .sql.','Ya, Backup'))return;const b=document.getElementById('btnBackup');b.disabled=true;b.innerHTML='<i class="fa-solid fa-spinner fa-spin mr-1"></i> Membackup...';let prog=0,timer=null;Swal.fire({title:'Membuat backup...',html:'Menyalin struktur + data. Jangan tutup halaman.<div class="mt-3 h-2.5 bg-emerald-100 rounded-full overflow-hidden"><div id="bpBar" class="h-2.5 rounded-full bg-emerald-600 transition-all" style="width:2%"></div></div><div id="bpTxt" class="mt-1 text-xs text-emerald-800 font-bold">0%</div>',allowOutsideClick:false,allowEscapeKey:false,showConfirmButton:false,didOpen:()=>{Swal.showLoading();timer=setInterval(()=>{prog=Math.min(90,prog+Math.random()*14+4);const e1=document.getElementById('bpBar'),e2=document.getElementById('bpTxt');if(e1)e1.style.width=prog+'%';if(e2)e2.innerText=Math.round(prog)+'%';},220);}});const t0=Date.now();const minWait=new Promise(r=>setTimeout(r,2200));let j=null,jerr='';try{j=await api('x',{act:'backup_run'});}catch(ex){jerr=ex.message;}await minWait;if(timer)clearInterval(timer);const e1=document.getElementById('bpBar'),e2=document.getElementById('bpTxt');if(e1)e1.style.width='100%';if(e2)e2.innerText='100%';await new Promise(r=>setTimeout(r,450));const dt=((Date.now()-t0)/1000).toFixed(1)+' dtk';try{if(j&&j.ok){await Swal.fire({icon:'success',title:'Backup sukses',html:'File: <b>'+j.file+'</b><br>Ukuran: <b>'+(j.size||'-')+'</b> • Waktu: <b>'+dt+'</b>',confirmButtonText:'OK',confirmButtonColor:'#059669'});location.reload();}else{await Swal.fire({icon:'error',title:'Backup gagal',text:(j&&j.msg)||jerr||'Gagal',confirmButtonColor:'#dc2626'});}}finally{b.disabled=false;b.innerHTML='<i class="fa-solid fa-download mr-1"></i> Backup Sekarang';}}
async function delBackup(f){if(!await ask('Hapus file '+f+'?','File tidak bisa dikembalikan.','Ya, Hapus'))return;const j=await api('x',{act:'backup_delete',file:f});if(j.ok){ok('Dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg||'Gagal');}
async function runRestore(e){e.preventDefault();const inp=document.getElementById('restoreFile');if(!inp.files.length){err('Pilih file .sql dulu');return;}if(!await ask('Restore menimpa SEMUA data?','Buat backup dulu sebelum lanjut.','Ya, Restore'))return;const f=new FormData();f.append('csrf_token',window.__CSRF||CSRF);f.append('act','backup_restore');f.append('file',inp.files[0]);Swal.fire({title:'Merestore...',didOpen:()=>Swal.showLoading(),allowOutsideClick:false});try{const r=await fetch((window.__BASE||BASE)+'api/x',{method:'POST',body:f});const j=await r.json();Swal.close();if(j.ok){ok('Restore berhasil');setTimeout(()=>location.reload(),1200);}else err(j.msg||'Gagal');}catch(ex){Swal.close();err('Gagal: '+ex.message);}}
</script>
