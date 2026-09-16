<?php
$rk=$pdo->query("SELECT k.id,k.kode_kegiatan,k.nomor_dokumen,k.nama_kegiatan,k.total_anggaran,k.status,(SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=k.id) AS jml_real FROM rkam k ORDER BY k.id DESC LIMIT 200")->fetchAll();
$sum=$pdo->query("SELECT * FROM sumber_dana WHERE status='aktif'")->fetchAll();
$s=$pdo->query("SELECT r.*,k.nama_kegiatan FROM realisasi r JOIN rkam k ON k.id=r.rkam_id ORDER BY r.tanggal_transaksi DESC, r.id DESC LIMIT 200")->fetchAll();
$can=in_array(Auth::role(),['superadmin','bendahara']);
?>
<?php if($can):?><button id="btnAddReal" onclick="window.realForm(0)" title="Tambah Realisasi" class="bg-emerald-700 hover:bg-emerald-800 text-white w-10 h-10 rounded-2xl text-sm mb-3 no-print shadow"><i class="fa-solid fa-plus"></i></button><?php endif;?>
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
let rkamItemsMap=new Map();
function realForm(id,d){d=d||{tanggal_transaksi:'<?=date('Y-m-d')?>',volume:1,harga:0};
let h=`<div class="flex justify-between items-center mb-3"><b>${id?'Edit':'Tambah'} Realisasi</b><button type="button" onclick="closeModal()" title="Tutup" class="w-8 h-8 rounded-full bg-red-50 border border-red-200 text-red-600 hover:bg-red-100 flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button></div>
<form onsubmit="realSave(event,${id})" class="text-sm grid md:grid-cols-2 gap-2" enctype="multipart/form-data" id="fReal">
<div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/70 p-2.5 text-[11px] text-slate-600">Alur: <b>1 Kegiatan RKAM</b> → <b>2 Tanggal</b> → <b>3 Uraian+Volume+Harga</b> → <b>4 Bukti</b> → Simpan. Realisasi wajib setelah RKAM disetujui. Nomor bukti & rekening otomatis ikut kegiatan terpilih.</div>
<div class="md:col-span-2"><label class="text-xs font-bold">1. Kegiatan RKAM *</label><select name="rkam_id" id="rlKeg" onchange="chgKeg()" class="w-full border rounded p-2">${RK.map(k=>{const sisa=(k.total_anggaran||0)-(k.jml_real||0);return `<option value="${k.id}" ${d.rkam_id==k.id?'selected':''}>${k.kode_kegiatan?k.kode_kegiatan+' — ':''}${k.nama_kegiatan} (${k.status}, sisa ${rp(sisa)})</option>`;}).join('')}</select></div>
<div id="rkInfo" class="md:col-span-2 rounded-xl border border-emerald-100 bg-emerald-50/60 p-2.5 text-xs"></div>
<div><label class="text-xs font-bold">Tanggal *</label><input type="date" name="tanggal_transaksi" value="${d.tanggal_transaksi||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Nomor Bukti (Kode Kegiatan)</label><select name="nomor_bukti" id="rlBukti" onchange="syncRekByBukti()" class="w-full border rounded p-2"></select></div>
<div><label class="text-xs font-bold">Sumber Dana</label><select name="sumber_dana_id" class="w-full border rounded p-2"><option value="">-</option>${SUM.map(s=>`<option value="${s.id}" ${d.sumber_dana_id==s.id?'selected':''}>${s.nama_sumber_dana}</option>`).join('')}</select></div>
<div><label class="text-xs font-bold">Kode Rekening (Ikut Kegiatan)</label><select name="kode_rekening" id="rlRek" class="w-full border rounded p-2"></select></div>
<div id="rekInfo" class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/60 p-2.5 text-xs"></div>
<div class="md:col-span-2"><label class="text-xs font-bold">Uraian *</label><input name="uraian" required value="${(d.uraian||'').replaceAll('"','')}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Volume</label><input type="number" min="0" step="1" name="volume" id="rv" value="${Math.round(d.volume??1)}" oninput="rcalc()" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Harga Satuan (Rp)</label><input type="text" inputmode="numeric" data-money data-dec="0" name="harga" id="rh" value="${d.harga??0}" oninput="rcalc()" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Vendor / Penerima</label><input name="penerima_vendor" value="${d.penerima_vendor||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Nomor Nota</label><input name="nomor_nota" value="${d.nomor_nota||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Bukti (JPG/PNG/PDF Max <?=MAX_UPLOAD_MB?>MB)</label><input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Keterangan</label><input name="keterangan" value="${d.keterangan||''}" class="w-full border rounded p-2"></div>
<div class="md:col-span-2 text-right font-bold">Jumlah: <span id="rtot">Rp 0</span></div>
<div class="md:col-span-2"><label class="text-xs"><input type="checkbox" name="allow_over" value="1"> Izinkan melebihi anggaran (wajib alasan)</label><input name="over_reason" placeholder="Alasan overbudget" class="w-full border rounded p-2 mt-1"></div>
<div class="md:col-span-2"><button class="w-full bg-emerald-700 text-white rounded p-2 font-bold">Simpan</button></div></form>`;
openModal(h,'md');fillSumber(d.rkam_id,d.sumber_dana_id);fillKeg(d.rkam_id,d.nomor_bukti,d.kode_rekening);rcalc();}
async function fillSumber(rid,cur){const sel=document.querySelector('#fReal [name=sumber_dana_id]');if(!sel)return;try{const j=await api('x',{act:'rkam_sumber',rkam_id:rid||document.getElementById('rlKeg').value});if(j.ok&&j.data&&j.data.length){sel.innerHTML=j.data.map(s=>`<option value="${s.id}" ${String(cur||'')===String(s.id)?'selected':''}>${s.nama_sumber_dana}</option>`).join('');}else{sel.innerHTML='<option value="">- RKAM Belum Ada Sumber Dana -</option>';}}catch(e){}}
async function fillKeg(rid,curBukti,curRek){
const ks=document.getElementById('rlKeg'),bs=document.getElementById('rlBukti'),rs=document.getElementById('rlRek'),info=document.getElementById('rkInfo');
if(!ks||!bs||!rs)return;
const k=RK.find(x=>String(x.id)===String(ks.value));
const kode=k?(k.kode_kegiatan||''):'';
const dok=k?(k.nomor_dokumen||''):'';
const agg=k?parseFloat(k.total_anggaran||0):0, used=k?parseFloat(k.jml_real||0):0, sisa=agg-used;
if(info&&k){info.innerHTML=`<div class=\"grid grid-cols-3 gap-2 text-center\"><div>Anggaran<br><b>${rp(agg)}</b></div><div>Terealisasi<br><b>${rp(used)}</b></div><div>Sisa<br><b class=\"${sisa<0?'text-red-600':'text-emerald-700'}\">${rp(sisa)}</b></div></div>${k.status==='draft'?'<div class=\"mt-1 text-red-600 font-bold\">RKAM masih Draft — realisasi ditolak server. Ajukan & setujui dulu.</div>':(k.status==='dikunci'?'<div class=\"mt-1 text-amber-700\">RKAM dikunci — hanya superadmin bisa realisasi.</div>':'')}`;}
let bOpts=[];
if(kode)bOpts.push(kode);
if(dok&&dok!==kode)bOpts.push(dok);
bs.innerHTML=bOpts.map(b=>`<option value=\"${b.replaceAll('\"','')}\" ${(curBukti||'')===b?'selected':''}>${b.replaceAll('<','&lt;')}</option>`).join('')||'<option value=\"\">- Belum Ada Kode -</option>';
if(!curBukti&&bOpts.length)bs.value=bOpts[0];
rs.innerHTML='<option value="">- Memuat... -</option>';
let list=[];
try{
const j=await api('x',{act:'rkam_items',rkam_id:ks.value});
list=(j.ok&&j.data)?j.data:[];
rkamItemsMap.set(String(ks.value),list);
}catch(e){err('Gagal muat rekening: '+(e.message||e));}
if(!list.length){rs.innerHTML=`<option value="">- Item RKAM Kosong -</option>`;}
else{rs.innerHTML=list.map((it,i)=>{const kr=(it.kode_rekening||'').trim();const val=kr||('ITEM-'+it.id);const lb=(kr?kr+' — ':'')+(it.nama_rekening||it.uraian||'Item RKAM');const sel=curRek?(String(curRek)===String(val)||String(curRek)===String(kr)):(!curRek&&i===0);return `<option value="${String(val).replaceAll('"','')}" ${sel?'selected':''}>${String(lb).replace(/</g,'&lt;')}</option>`;}).join('');}
syncRekByBukti(curRek);
if(typeof ddify==='function')ddify(document.getElementById('modalBox'));
if(typeof bindMoney==='function')bindMoney(document.getElementById('modalBox'));
rcalc();
}
function syncRekByBukti(curRek){
const ks=document.getElementById('rlKeg'),bs=document.getElementById('rlBukti'),rs=document.getElementById('rlRek');
if(!ks||!bs||!rs)return;
if(curRek){rs.value=curRek;if(typeof ddRefresh==='function')ddRefresh(rs);return;}
const items=rkamItemsMap.get(String(ks.value))||[];
if(!items.length)return;
const k=RK.find(x=>String(x.id)===String(ks.value));
let item=null;
if(k&&bs.value===k.nomor_dokumen)item=items.find(it=>String(it.id)===String(items[0].id));
if(!item)item=items.find(it=>String((it.kode_rekening||'').trim())===String(bs.value));
if(!item)item=items[0];
const kr=(item.kode_rekening||'').trim();
rs.value=kr||('ITEM-'+item.id);
if(typeof ddRefresh==='function')ddRefresh(rs);
}
async function chgKeg(){
const ks=document.getElementById('rlKeg');
if(!ks)return;
try{
const j=await api('x',{act:'rkam_sumber',rkam_id:ks.value});
if(j.ok&&j.data){
const sel=document.querySelector('#fReal [name=sumber_dana_id]');
if(sel){
sel.innerHTML=j.data.map(s=>`<option value="${s.id}">${s.nama_sumber_dana} (${s.jumlah})</option>`).join('');
if(typeof ddify==='function'){
const w=sel.closest('.dd');
if(w){w.parentNode.insertBefore(sel,w);w.remove();delete sel.dataset.dd;}
setTimeout(()=>ddify(document.getElementById('modalBox')),0);
}
}
}
}catch(e){err('Gagal muat sumber: '+e.message);}
fillKeg(ks.value,'','');
}
function rcalc(){const rv=document.getElementById('rv'),rh=document.getElementById('rh'),t=document.getElementById('rtot');if(!rv||!rh||!t)return;let v=parseFloat(rv.value);if(isNaN(v))v=0;v=Math.max(0,Math.round(v));const h=parseID(rh.value);t.innerText=rp(v*h);}
async function realSave(e,id){e.preventDefault();const ks=document.getElementById('rlKeg');const k=ks?RK.find(x=>String(x.id)===String(ks.value)):null;if(!k){err('Langkah 1 belum: pilih Kegiatan RKAM dulu');return;}const ura=document.querySelector('#fReal [name=uraian]');if(!ura||!ura.value.trim()){err('Langkah 3 belum: isi Uraian');return;}const f=new FormData(e.target);f.append('csrf_token',CSRF);f.append('act','realisasi_save');f.append('id',id);
const r=await fetch(BASE+'api/x',{method:'POST',body:f});const j=await r.json();
if(j.ok){ok('Tersimpan');setTimeout(()=>location.reload(),800);return;}
if(!j.over){err(j.msg||'Gagal');return;}
if(!await ask(j.msg,'Butuh izin khusus + alasan.','Tetap simpan'))return;
f.set('allow_over','1');
const rr=await Swal.fire({title:'Alasan overbudget',input:'text',inputPlaceholder:'Wajib isi...',showCancelButton:true,cancelButtonText:'Batal',confirmButtonText:'Simpan',confirmButtonColor:'#059669'});
if(!rr.isConfirmed||!rr.value)return;
f.set('over_reason',rr.value);
const r2=await fetch(BASE+'api/x',{method:'POST',body:f});const j2=await r2.json();
if(j2.ok){ok('Tersimpan');setTimeout(()=>location.reload(),800);}else err(j2.msg||'Gagal');}
async function realDel(id){if(!await ask('Hapus transaksi ini?','Total realisasi ikut berubah.','Ya, Hapus'))return;const j=await api('x',{act:'realisasi_delete',id});if(j.ok){ok('Dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg);}
window.realForm=realForm;window.realSave=realSave;window.realDel=realDel;window.rcalc=rcalc;window.fillKeg=fillKeg;window.fillSumber=fillSumber;window.chgKeg=chgKeg;window.syncRekByBukti=syncRekByBukti;window.parseID=parseID;window.fmtID=fmtID;
(function(){const b=document.getElementById('btnAddReal');if(b)b.addEventListener('click',ev=>{ev.preventDefault();window.realForm(0);});})();
</script>
