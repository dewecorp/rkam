<?php
$th=Helper::tahunAktif($pdo);
$f_tahun=(int)($_GET['tahun']??($th['id']??0));
$f_bidang=$_GET['bidang']??'';$f_sumber=$_GET['sumber']??'';$f_status=$_GET['status']??'';$q=trim($_GET['q']??'');
$w=['1=1'];$p=[];
if($f_tahun){$w[]='k.tahun_id=?';$p[]=$f_tahun;}
if($f_bidang!==''){$w[]='k.bidang_id=?';$p[]=$f_bidang;}
if($f_status!==''){$w[]='k.status=?';$p[]=$f_status;}
if($q!==''){$w[]='(k.nama_kegiatan LIKE ? OR k.kode_kegiatan LIKE ? OR k.nomor_dokumen LIKE ?)';$p[]="%$q%";$p[]="%$q%";$p[]="%$q%";}
if($f_sumber!==''){$w[]='EXISTS(SELECT 1 FROM rkam_sumber_dana rs WHERE rs.rkam_id=k.id AND rs.sumber_dana_id=?)';$p[]=$f_sumber;}
$s=$pdo->prepare("SELECT k.*,b.nama_bidang,(SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=k.id) AS jml_real FROM rkam k LEFT JOIN bidang b ON b.id=k.bidang_id WHERE ".implode(' AND ',$w)." ORDER BY k.id DESC LIMIT 200");
$s->execute($p);$rows=$s->fetchAll();
$tahun=$pdo->query("SELECT * FROM tahun_anggaran ORDER BY tahun DESC")->fetchAll();
$bid=$pdo->query("SELECT * FROM bidang WHERE status='aktif'")->fetchAll();
$sum=$pdo->query("SELECT * FROM sumber_dana WHERE status='aktif'")->fetchAll();
$sat=$pdo->query("SELECT * FROM satuan WHERE status='aktif' ORDER BY nama_satuan")->fetchAll();
$canAdd=in_array(Auth::role(),['superadmin','bendahara','operator']);
?>
<div class="bg-white p-3 rounded shadow mb-3 no-print">
<form class="flex flex-wrap gap-2 text-sm" method="GET" action="<?=BASE_URL?>rkam">
<input type="hidden" name="url" value="rkam">
<select name="tahun" class="border rounded p-1.5"><?php foreach($tahun as $t):?><option value="<?=$t['id']?>" <?=($f_tahun==$t['id']?'selected':'')?>><?=$t['tahun']?> (<?=$t['status']?>)</option><?php endforeach;?></select>
<select name="bidang" class="border rounded p-1.5"><option value="">Semua bidang</option><?php foreach($bid as $b):?><option value="<?=$b['id']?>" <?=($f_bidang==$b['id']?'selected':'')?>><?=Security::e($b['nama_bidang'])?></option><?php endforeach;?></select>
<select name="sumber" class="border rounded p-1.5"><option value="">Semua sumber</option><?php foreach($sum as $x):?><option value="<?=$x['id']?>" <?=($f_sumber==$x['id']?'selected':'')?>><?=Security::e($x['nama_sumber_dana'])?></option><?php endforeach;?></select>
<select name="status" class="border rounded p-1.5"><option value="">Semua status</option><?php foreach(['draft','diajukan','diverifikasi','disetujui','ditolak','direvisi','dikunci'] as $st):?><option <?=($f_status===$st?'selected':'')?>><?=$st?></option><?php endforeach;?></select>
<input name="q" value="<?=Security::e($q)?>" placeholder="Cari..." class="border rounded p-1.5">
<button class="bg-slate-700 text-white px-3 rounded">Filter</button>
<?php if($canAdd):?><button type="button" id="btnAddRkam" onclick="window.rkamForm(0)" title="Tambah RKAM" class="bg-emerald-700 hover:bg-emerald-800 text-white w-10 h-10 rounded-2xl ml-auto shadow no-print"><i class="fa-solid fa-plus"></i></button><?php endif;?>
</form></div>
<div class="bg-white rounded shadow overflow-auto">
<?php if(!$rows):?><div class="p-10 text-center text-gray-500">Belum ada data RKAM.<br><?php if($canAdd):?><button type="button" id="btnAddRkam2" onclick="window.rkamForm(0)" title="Tambah RKAM" class="mt-2 bg-emerald-700 hover:bg-emerald-800 text-white w-11 h-11 rounded-2xl shadow"><i class="fa-solid fa-plus"></i></button><?php endif;?></div>
<?php else:?><table class="w-full text-sm min-w-[900px]"><thead><tr class="bg-gray-50 border-b text-left"><th class="p-2">Nomor / Kegiatan</th><th class="p-2">Bidang</th><th class="p-2 text-right">Anggaran</th><th class="p-2 text-right">Realisasi</th><th class="p-2 text-right">Sisa</th><th class="p-2">%</th><th class="p-2">Status</th><th class="p-2 no-print">Aksi</th></tr></thead><tbody>
<?php foreach($rows as $r):$sisa=(float)$r['total_anggaran']-(float)$r['jml_real'];$pers=Helper::persen($r['jml_real'],$r['total_anggaran']);?>
<tr class="border-b hover:bg-gray-50"><td class="p-2"><div class="text-xs text-gray-500"><?=Security::e($r['nomor_dokumen']??'')?> • <?=Security::e($r['kode_kegiatan']??'')?></div><a href="<?=BASE_URL?>rkam/detail/<?=$r['id']?>" class="font-semibold text-emerald-800"><?=Security::e($r['nama_kegiatan'])?></a></td>
<td class="p-2"><?=Security::e($r['nama_bidang']??'-')?></td><td class="p-2 text-right"><?=Security::rupiah($r['total_anggaran'])?></td><td class="p-2 text-right"><?=Security::rupiah($r['jml_real'])?></td><td class="p-2 text-right"><?=Security::rupiah($sisa)?></td><td class="p-2"><?=$pers?>%</td><td class="p-2"><?=Helper::badgeStatus($r['status'])?></td>
<td class="p-2 whitespace-nowrap no-print"><a href="<?=BASE_URL?>rkam/detail/<?=$r['id']?>" title="Detail" class="btn-ic btn-view"><i class="fa-solid fa-eye"></i></a><?php if($canAdd):?> <button onclick="rkamForm(<?=$r['id']?>)" title="Edit" class="btn-ic btn-edit"><i class="fa-solid fa-pen-to-square"></i></button> <button onclick="rkamDel(<?=$r['id']?>)" title="Hapus" class="btn-ic btn-del"><i class="fa-solid fa-trash-can"></i></button><?php endif;?></td></tr>
<?php endforeach;?></tbody></table><?php endif;?></div>
<script>
let REK=[],SAT=<?=json_encode($sat)?>,SUM=<?=json_encode($sum)?>,KEG=[];
fetch(BASE+'api/master_opt',{method:'POST',body:new URLSearchParams({csrf_token:CSRF,act:'master_opt'})}).then(r=>r.json()).then(j=>{if(j.ok){if(j.data.sumber&&j.data.sumber.length)SUM=j.data.sumber;if(j.data.satuan&&j.data.satuan.length)SAT=j.data.satuan;}});
async function rkamForm(id){
let d={tahun_id:'<?=$f_tahun?>',nama_kegiatan:'',items:[{uraian:'',volume:1,satuan:'',harga_satuan:0}],sumber:[]};
if(id){const r=await fetch(BASE+'api/rkam_get',{method:'POST',body:new URLSearchParams({csrf_token:CSRF,act:'rkam_get',id})}).then(r=>r.json());if(!r.ok){err(r.msg);return;}d=r.data;}
const bid=<?=json_encode($bid)?>,tah=<?=json_encode($tahun)?>;
let h=`<div class="flex justify-between items-center mb-3"><b>${id?'Edit':'Tambah'} RKAM</b><button type="button" onclick="closeModal()" title="Tutup" class="w-8 h-8 rounded-full bg-red-50 border border-red-200 text-red-600 hover:bg-red-100 flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button></div>
<form onsubmit="rkamSave(event,${id})" class="text-sm space-y-3">
<div class="grid md:grid-cols-2 gap-2">
<div><label class="text-xs font-bold">Tahun</label><select name="tahun_id" class="w-full border rounded p-2">${tah.map(t=>`<option value="${t.id}" ${d.tahun_id==t.id?'selected':''}>${t.tahun}</option>`).join('')}</select></div>
<div><label class="text-xs font-bold">Bidang</label><select name="bidang_id" class="w-full border rounded p-2"><option value="">-</option>${bid.map(b=>`<option value="${b.id}" ${d.bidang_id==b.id?'selected':''}>${b.nama_bidang}</option>`).join('')}</select></div></div>
<div class="grid md:grid-cols-2 gap-2">
<div><label class="text-xs font-bold">Kode Kegiatan</label><input name="kode_kegiatan" value="${d.kode_kegiatan||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Nama Kegiatan *</label><input name="nama_kegiatan" required value="${(d.nama_kegiatan||'').replaceAll('"','')}" class="w-full border rounded p-2"></div></div>
<div class="grid md:grid-cols-3 gap-2">
<div><label class="text-xs font-bold">Penanggung Jawab</label><input name="penanggung_jawab" placeholder="Penanggung Jawab" value="${d.penanggung_jawab||''}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Waktu Pelaksanaan</label><input type="date" name="waktu_pelaksanaan" value="${(d.waktu_pelaksanaan||'').substring(0,10)}" class="w-full border rounded p-2"></div>
<div><label class="text-xs font-bold">Prioritas</label><select name="prioritas" class="w-full border rounded p-2">${['rendah','sedang','tinggi','mendesak'].map(p=>`<option value="${p}" ${d.prioritas===p?'selected':''}>${p.charAt(0).toUpperCase()+p.slice(1)}</option>`).join('')}</select></div></div>
<div><label class="text-xs font-bold">Tujuan</label><textarea name="tujuan" placeholder="Tujuan" class="w-full border rounded p-2">${d.tujuan||''}</textarea></div>
<div class="font-bold">Item Anggaran <button type="button" onclick="addItem()" title="Tambah Item" class="ml-2 bg-blue-600 hover:bg-blue-700 text-white w-7 h-7 rounded-xl text-xs shadow"><i class="fa-solid fa-plus"></i></button></div>
<div id="items" class="space-y-2"></div>
<div class="text-right font-bold">Total: <span id="grand">Rp 0</span></div>
<div class="font-bold">Sumber Dana <button type="button" onclick="addSumber()" title="Tambah Sumber" class="ml-2 bg-blue-600 hover:bg-blue-700 text-white w-7 h-7 rounded-xl text-xs shadow"><i class="fa-solid fa-plus"></i></button></div>
<div id="sums" class="space-y-2"></div>
<div class="text-right text-xs">Total sumber: <span id="gsum">Rp 0</span> • <span id="bdiff"></span> <button type="button" onclick="autoBalance()" class="ml-2 bg-emerald-600 text-white px-2 py-0.5 rounded">Samakan</button></div>
<button id="btnSave" type="submit" class="w-full bg-emerald-700 text-white rounded p-2 font-bold disabled:opacity-50">Simpan RKAM</button></form>`;
openModal(h,'lg');renderItems(d.items&&d.items.length?d.items:[{uraian:'',volume:1,satuan:'',harga_satuan:0}]);renderSums(d.sumber&&d.sumber.length?d.sumber:[]);}
function satOpts(cur){const v=(cur||'').toString().toLowerCase();return SAT.map(s=>`<option value="${s.nama_satuan}" ${v===s.nama_satuan.toLowerCase()?'selected':''}>${s.nama_satuan}</option>`).join('');}
function itemRow(it){it=it||{};const cur=it.satuan||it.satuan_text||'';const inList=SAT.some(s=>s.nama_satuan.toLowerCase()===(cur||'').toString().toLowerCase());return `<div class="grid grid-cols-12 gap-2 irow bg-gray-50 p-2 rounded-xl border border-emerald-100">
<div class="col-span-4"><label class="text-[11px] font-bold">Uraian *</label><input class="w-full border rounded-xl p-1.5 iu" placeholder="Uraian *" value="${(it.uraian||'').replaceAll('"','')}"></div>
<div class="col-span-2"><label class="text-[11px] font-bold">Volume</label><input type="number" min="0" step="1" class="w-full border rounded-xl p-1.5 iv" value="${Math.round(it.volume??1)}" oninput="calc()"></div>
<div class="col-span-2"><label class="text-[11px] font-bold">Satuan</label><select class="w-full border rounded-xl p-1.5 is bg-white">${satOpts(inList?cur:'')}${(!inList&&cur)?`<option value="${cur.replaceAll('"','')}" selected>${cur.replaceAll('<','&lt;')}</option>`:''}</select></div>
<div class="col-span-3"><label class="text-[11px] font-bold">Harga Satuan</label><input type="number" min="0" step="0.01" class="w-full border rounded-xl p-1.5 ih" value="${it.harga_satuan??0}" oninput="calc()"></div>
<div class="col-span-1 flex items-end"><button type="button" onclick="this.closest('.irow').remove();calc()" title="Hapus Item" class="text-red-600 w-full">x</button></div>
<div class="col-span-12 text-right text-xs">Jumlah: <b class="ij">${rp((it.volume||0)*(it.harga_satuan||0))}</b></div></div>`;}
function renderItems(a){document.getElementById('items').innerHTML=(a||[]).map(itemRow).join('');calc();if(typeof ddify==='function')setTimeout(()=>ddify(document.getElementById('modalBox')),0);}
function addItem(){document.getElementById('items').insertAdjacentHTML('beforeend',itemRow({}));calc();if(typeof ddify==='function')setTimeout(()=>ddify(document.getElementById('modalBox')),0);}
function renderSums(a){const opts=SUM.map(s=>({id:s.id,nama:s.nama_sumber_dana}));document.getElementById('sums').innerHTML=(a||[]).map(s=>{const cur=s.sumber_dana_id||s.id||'';return `<div class="flex gap-2 srow items-end bg-gray-50 p-2 rounded-xl border border-emerald-100"><div class="flex-1"><label class="text-[11px] font-bold">Sumber Dana</label><select class="sid w-full border rounded-xl p-1.5 bg-white">${opts.map(o=>`<option value="${o.id}" ${String(cur)===String(o.id)?'selected':''}>${o.nama}</option>`).join('')}</select></div><div class="w-40"><label class="text-[11px] font-bold">Jumlah (Rp)</label><input type="number" min="0" step="0.01" class="w-full border rounded-xl p-1.5 sj" value="${s.jumlah||0}" oninput="calcSum()"></div><button type="button" onclick="this.closest('.srow').remove();calcSum()" title="Hapus Sumber" class="text-red-600 px-1 pb-1.5">x</button></div>`;}).join('');if(!a||!a.length)addSumberRow();calcSum();if(typeof ddify==='function')setTimeout(()=>ddify(document.getElementById('modalBox')),0);}
function addSumberRow(){const opts=SUM.map(s=>`<option value="${s.id}">${s.nama_sumber_dana}</option>`).join('');document.getElementById('sums').insertAdjacentHTML('beforeend',`<div class="flex gap-2 srow items-end bg-gray-50 p-2 rounded-xl border border-emerald-100"><div class="flex-1"><label class="text-[11px] font-bold">Sumber Dana</label><select class="sid w-full border rounded-xl p-1.5 bg-white">${opts}</select></div><div class="w-40"><label class="text-[11px] font-bold">Jumlah (Rp)</label><input type="number" min="0" step="0.01" class="w-full border rounded-xl p-1.5 sj" value="0" oninput="calcSum()"></div><button type="button" onclick="this.closest('.srow').remove();calcSum()" title="Hapus Sumber" class="text-red-600 px-1 pb-1.5">x</button></div>`);calcSum();if(typeof ddify==='function')setTimeout(()=>ddify(document.getElementById('modalBox')),0);}
function addSumber(){addSumberRow();}
function getItems(){return [...document.querySelectorAll('.irow')].map(r=>{const s=r.querySelector('select.is');return {uraian:r.querySelector('.iu').value,volume:Math.max(0,Math.round(parseFloat(r.querySelector('.iv').value)||0)),satuan:s?s.value:'',harga_satuan:parseFloat(r.querySelector('.ih').value)||0};});}
function getSums(){return [...document.querySelectorAll('.srow')].map(r=>{const s=r.querySelector('select.sid');return {id:s?s.value:'',jumlah:parseFloat(r.querySelector('.sj').value)||0};});}
function calc(){let t=0;document.querySelectorAll('.irow').forEach(r=>{const v=parseFloat(r.querySelector('.iv').value)||0,h=parseFloat(r.querySelector('.ih').value)||0;r.querySelector('.ij').innerText=rp(v*h);t+=v*h;});document.getElementById('grand').innerText=rp(t);updateDiff();}
function calcSum(){let t=0;document.querySelectorAll('.sj').forEach(i=>t+=parseFloat(i.value)||0);document.getElementById('gsum').innerText=rp(t);updateDiff();}
function sumItems(){let t=0;document.querySelectorAll('.irow').forEach(r=>{t+=(parseFloat(r.querySelector('.iv').value)||0)*(parseFloat(r.querySelector('.ih').value)||0);});return Math.round(t*100)/100;}
function sumSumber(){let t=0;document.querySelectorAll('.sj').forEach(i=>t+=parseFloat(i.value)||0);return Math.round(t*100)/100;}
function updateDiff(){const el=document.getElementById('bdiff');if(!el)return;const t=sumItems(),s=sumSumber(),d=Math.round((s-t)*100)/100;if(Math.abs(d)<0.01){el.innerHTML='<b class="text-emerald-600">Balance</b>';}else{el.innerHTML='Selisih: <b class="text-red-600">'+rp(Math.abs(d))+'</b>'+(s>t?' (sumber lebih besar)':' (sumber kurang)');}}
function autoBalance(){const t=sumItems();const rows=[...document.querySelectorAll('.srow')];if(!rows.length){err('Belum ada baris sumber dana');return;}const vals=rows.map(r=>parseFloat(r.querySelector('.sj').value)||0);let idx=vals.findIndex(v=>v>0);if(vals.filter(v=>v>0).length!==1){idx=0;rows.forEach((r,i)=>{if(i>0)r.querySelector('.sj').value=0;});}rows[idx].querySelector('.sj').value=t;calcSum();}
async function rkamSave(e,id){e.preventDefault();const btn=document.getElementById('btnSave');if(btn){btn.disabled=true;btn.innerText='Menyimpan...';}try{const sums=getSums();if(!sums.length){err('Minimal 1 sumber dana');return;}const ids=sums.map(s=>String(s.id));if(ids.some(v=>!v||v==='0')){err('Pilih sumber dana dulu');return;}if(new Set(ids).size!==ids.length){err('Sumber dana ganda. Gabung jadi 1 baris.');return;}const items=getItems();if(!items.length||!items.some(it=>it.uraian.trim()!=='')){err('Minimal 1 item anggaran dengan uraian');return;}const t=sumItems(),s=sumSumber();if(t<=0){err('Total anggaran harus > 0');return;}if(Math.abs(s-t)>0.01){err('Total sumber dana ('+s.toLocaleString('id-ID')+') belum sama total anggaran ('+t.toLocaleString('id-ID')+'). Klik tombol Samakan atau sesuaikan manual.');return;}const f=new FormData(e.target);const d={act:'rkam_save',id,tahun_id:f.get('tahun_id'),bidang_id:f.get('bidang_id'),kode_kegiatan:f.get('kode_kegiatan'),nama_kegiatan:f.get('nama_kegiatan'),tujuan:f.get('tujuan'),penanggung_jawab:f.get('penanggung_jawab'),waktu_pelaksanaan:f.get('waktu_pelaksanaan'),prioritas:f.get('prioritas'),items:JSON.stringify(items),sumber:JSON.stringify(sums)};const j=await api('x',d);if(j.ok){ok('Tersimpan');setTimeout(()=>location.reload(),800);}else err(j.msg||'Gagal');}finally{if(btn){btn.disabled=false;btn.innerText='Simpan RKAM';}}}
async function rkamDel(id){if(!await ask('Hapus RKAM ini?','Item + sumber ikut terhapus.','Ya, Hapus'))return;const j=await api('x',{act:'rkam_delete',id});if(j.ok){ok('Dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg);}
window.rkamForm=rkamForm;window.rkamSave=rkamSave;window.rkamDel=rkamDel;window.addItem=addItem;window.addSumber=addSumber;window.getItems=getItems;window.getSums=getSums;window.calc=calc;window.calcSum=calcSum;window.sumItems=sumItems;window.sumSumber=sumSumber;window.updateDiff=updateDiff;window.autoBalance=autoBalance;
['btnAddRkam','btnAddRkam2'].forEach(b=>{const el=document.getElementById(b);if(el)el.addEventListener('click',ev=>{ev.preventDefault();window.rkamForm(0);});});
</script>
