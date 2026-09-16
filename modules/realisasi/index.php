<?php
$rk=$pdo->query("SELECT k.id,k.nama_kegiatan,k.total_anggaran,k.status FROM rkam k ORDER BY k.id DESC LIMIT 200")->fetchAll();
$sum=$pdo->query("SELECT * FROM sumber_dana WHERE status='aktif'")->fetchAll();
$s=$pdo->query("SELECT r.*,k.nama_kegiatan FROM realisasi r JOIN rkam k ON k.id=r.rkam_id ORDER BY r.tanggal_transaksi DESC, r.id DESC LIMIT 200")->fetchAll();
$can=in_array(Auth::role(),['superadmin','bendahara']);
?>
<?php if($can):?><button onclick="realForm(0)" title="Tambah Realisasi" class="bg-emerald-700 hover:bg-emerald-800 text-white w-10 h-10 rounded-2xl text-sm mb-3 no-print shadow"><i class="fa-solid fa-plus"></i></button><?php endif;?>
<div class="bg-white rounded shadow overflow-auto">
<?php if(!$s):?><div class="p-10 text-center text-gray-500">Belum ada transaksi realisasi.</div>
<?php else:?><table class="w-full text-sm min-w-[900px]"><thead><tr class="bg-gray-50 border-b text-left"><th class="p-2">Tanggal / Bukti</th><th class="p-2">Kegiatan</th><th class="p-2">Uraian</th><th class="p-2 text-right">Jumlah</th><th class="p-2">Bukti</th><th class="p-2 no-print">Aksi</th></tr></thead><tbody>
<?php foreach($s as $r):?><tr class="border-b"><td class="p-2"><?=Security::e($r['tanggal_transaksi'])?><div class="text-xs text-gray-500"><?=Security::e($r['nomor_bukti']??'')?></div></td>
<td class="p-2"><?=Security::e($r['nama_kegiatan'])?></td><td class="p-2"><?=Security::e($r['uraian'])?><div class="text-xs text-gray-500"><?=Security::e($r['penerima_vendor']??'')?></div></td>
<td class="p-2 text-right"><?=Security::rupiah($r['jumlah'])?><?=($r['allow_overbudget']?'<div class="text-[10px] text-red-600">overbudget</div>':'')?></td>
<td class="p-2"><?php if($r['bukti_file']):?><a target="_blank" href="<?=BASE_URL?>uploads/bukti/<?=Security::e($r['bukti_file'])?>" title="Lihat Bukti" class="btn-ic btn-dl"><i class="fa-solid fa-file-lines"></i></a><?php else:?>-<?php endif;?></td>
<td class="p-2 no-print"><?php if($can):?><button onclick='realForm(<?=$r['id']?>,<?=json_encode($r,JSON_HEX_APOS|JSON_HEX_QUOT)?>)' title="Edit" class="btn-ic btn-edit"><i class="fa-solid fa-pen-to-square"></i></button> <button onclick="realDel(<?=$r['id']?>)" title="Hapus" class="btn-ic btn-del"><i class="fa-solid fa-trash-can"></i></button><?php endif;?></td></tr><?php endforeach;?></tbody></table><?php endif;?></div>
<script>
const RK=<?=json_encode($rk)?>,SUM=<?=json_encode($sum)?>;
function realForm(id,d){d=d||{tanggal_transaksi:'<?=date('Y-m-d')?>',volume:1,harga:0};
let h=`<div class="flex justify-between items-center mb-3"><b>${id?'Edit':'Tambah'} Realisasi</b><button type="button" onclick="closeModal()" title="Tutup" class="w-8 h-8 rounded-full bg-red-50 border border-red-200 text-red-600 hover:bg-red-100 flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button></div>
<form onsubmit="realSave(event,${id})" class="text-sm grid md:grid-cols-2 gap-2" enctype="multipart/form-data" id="fReal">
<div class="md:col-span-2"><label class="text-xs font-bold">Kegiatan *</label><select name="rkam_id" class="w-full border rounded p-2">${RK.map(k=>`<option value="${k.id}" ${d.rkam_id==k.id?'selected':''}>${k.nama_kegiatan} (${k.status})</option>`).join('')}</select></div>
<div><label class="text-xs font-bold">Tanggal *</label><input type="date" name="tanggal_transaksi" value="${d.tanggal_transaksi||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Nomor Bukti</label><input name="nomor_bukti" value="${d.nomor_bukti||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Sumber Dana</label><select name="sumber_dana_id" class="w-full border rounded p-2"><option value="">-</option>${SUM.map(s=>`<option value="${s.id}" ${d.sumber_dana_id==s.id?'selected':''}>${s.nama_sumber_dana}</option>`).join('')}</select></div>
<div><label class="text-xs font-bold">Kode Rekening</label><input name="kode_rekening" value="${d.kode_rekening||''}" class="w-full border rounded p-2"></div>
<div class="md:col-span-2"><label class="text-xs font-bold">Uraian *</label><input name="uraian" required value="${(d.uraian||'').replaceAll('"','')}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Volume</label><input type="number" min="0" step="1" name="volume" id="rv" value="${Math.round(d.volume??1)}" oninput="rcalc()" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Harga Satuan</label><input type="number" min="0" step="0.01" name="harga" id="rh" value="${d.harga??0}" oninput="rcalc()" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Vendor / Penerima</label><input name="penerima_vendor" value="${d.penerima_vendor||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Nomor Nota</label><input name="nomor_nota" value="${d.nomor_nota||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Bukti (JPG/PNG/PDF Max <?=MAX_UPLOAD_MB?>MB)</label><input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Keterangan</label><input name="keterangan" value="${d.keterangan||''}" class="w-full border rounded p-2"></div>
<div class="md:col-span-2 text-right font-bold">Jumlah: <span id="rtot">Rp 0</span></div>
<div class="md:col-span-2"><label class="text-xs"><input type="checkbox" name="allow_over" value="1"> Izinkan melebihi anggaran (wajib alasan)</label><input name="over_reason" placeholder="Alasan overbudget" class="w-full border rounded p-2 mt-1"></div>
<div class="md:col-span-2"><button class="w-full bg-emerald-700 text-white rounded p-2 font-bold">Simpan</button></div></form>`;
openModal(h,'md');rcalc();}
function rcalc(){const v=Math.max(0,Math.round(parseFloat(document.getElementById('rv').value)||0)),h=parseFloat(document.getElementById('rh').value)||0;document.getElementById('rtot').innerText=rp(v*h);}
async function realSave(e,id){e.preventDefault();const f=new FormData(e.target);f.append('csrf_token',CSRF);f.append('act','realisasi_save');f.append('id',id);
const r=await fetch(BASE+'api/x',{method:'POST',body:f});const j=await r.json();
if(j.ok){ok('Tersimpan');setTimeout(()=>location.reload(),800);}else{if(j.over){if(!await ask(j.msg,'Butuh izin khusus + alasan.','Tetap simpan'))return;f.set('allow_over','1');const rr=await Swal.fire({title:'Alasan overbudget',input:'text',inputPlaceholder:'Wajib isi...',showCancelButton:true,cancelButtonText:'Batal',confirmButtonText:'Simpan',confirmButtonColor:'#059669'});if(rr.isConfirmed&&rr.value){f.set('over_reason',rr.value);const r2=await fetch(BASE+'api/x',{method:'POST',body:f});const j2=await r2.json();if(j2.ok){ok('Tersimpan');setTimeout(()=>location.reload(),800);}else err(j2.msg);}}}else err(j.msg||'Gagal');}}
async function realDel(id){if(!await ask('Hapus transaksi ini?','Total realisasi ikut berubah.','Ya, Hapus'))return;const j=await api('x',{act:'realisasi_delete',id});if(j.ok){ok('Dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg);}
</script>
