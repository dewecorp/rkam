<?php
$tab=$_GET['tab']??'bidang';
$tabs=['bidang'=>'Bidang','sumber_dana'=>'Sumber Dana','jenis_belanja'=>'Jenis Belanja','satuan'=>'Satuan','rekening'=>'Rekening','kegiatan'=>'Kegiatan','tahun_anggaran'=>'Tahun Anggaran'];
if(!isset($tabs[$tab]))$tab='bidang';
$cols=['bidang'=>['kode'=>'Kode','nama_bidang'=>'Nama Bidang','keterangan'=>'Keterangan','status'=>'Status'],'sumber_dana'=>['kode'=>'Kode','nama_sumber_dana'=>'Nama Sumber Dana','keterangan'=>'Keterangan','status'=>'Status'],'jenis_belanja'=>['kode'=>'Kode','nama'=>'Nama','keterangan'=>'Keterangan','status'=>'Status'],'satuan'=>['kode'=>'Kode','nama_satuan'=>'Nama Satuan','keterangan'=>'Keterangan','status'=>'Status'],'rekening'=>['kode'=>'Kode','nama_rekening'=>'Nama Rekening','kelompok'=>'Kelompok','jenis_belanja_id'=>'Jenis Belanja','keterangan'=>'Keterangan','status'=>'Status'],'kegiatan'=>['kode'=>'Kode','nama_kegiatan'=>'Nama Kegiatan','bidang_id'=>'Bidang','prioritas'=>'Prioritas','status'=>'Status'],'tahun_anggaran'=>['tahun'=>'Tahun','tanggal_mulai'=>'Tanggal Mulai','tanggal_selesai'=>'Tanggal Selesai','status'=>'Status','keterangan'=>'Keterangan']];
$rows=$pdo->query("SELECT * FROM `$tab` ORDER BY id DESC LIMIT 200")->fetchAll();
$bid=$pdo->query("SELECT * FROM bidang WHERE status='aktif'")->fetchAll();
$jb=$pdo->query("SELECT * FROM jenis_belanja")->fetchAll();
$bidMap=[];foreach($bid as $b)$bidMap[$b['id']]=$b['nama_bidang'];
$jbMap=[];foreach($jb as $j)$jbMap[$j['id']]=$j['nama'];
function cellVal($tab,$c,$r,$bidMap,$jbMap){$v=$r[$c]??'';if($c==='bidang_id')return $bidMap[$v]??'-';if($c==='jenis_belanja_id')return $jbMap[$v]??'-';return $v;}
?>
<div class="flex flex-wrap items-center gap-2 mb-4 no-print">
<div class="font-extrabold text-emerald-900">Master <?=$tabs[$tab]?></div>
<button onclick="masterForm(0)" title="Tambah <?=$tabs[$tab]?>" class="ml-auto bg-emerald-700 hover:bg-emerald-800 text-white w-10 h-10 rounded-2xl text-sm shadow"><i class="fa-solid fa-plus"></i></button></div>
<div class="bg-white rounded shadow overflow-auto">
<?php if(!$rows):?><div class="p-10 text-center text-gray-500">Belum ada data.<br><button onclick="masterForm(0)" title="Tambah <?=$tabs[$tab]?>" class="mt-2 bg-emerald-700 hover:bg-emerald-800 text-white w-11 h-11 rounded-2xl shadow"><i class="fa-solid fa-plus"></i></button></div>
<?php else:?><table class="w-full text-sm"><thead><tr class="bg-gray-50 border-b"><?php foreach($cols[$tab] as $c=>$lbl):?><th class="p-2 text-left"><?=Security::e($lbl)?></th><?php endforeach;?><th class="p-2 no-print">Aksi</th></tr></thead>
<tbody><?php foreach($rows as $r):?><tr class="border-b hover:bg-gray-50">
<?php foreach($cols[$tab] as $c=>$lbl):?><td class="p-2"><?=Security::e(cellVal($tab,$c,$r,$bidMap,$jbMap))?></td><?php endforeach;?>
<td class="p-2 whitespace-nowrap no-print"><button onclick='masterForm(<?=$r['id']?>,<?=json_encode($r,JSON_HEX_APOS|JSON_HEX_QUOT)?>)' title="Edit" class="btn-ic btn-edit"><i class="fa-solid fa-pen-to-square"></i></button> <button onclick="masterDel(<?=$r['id']?>)" title="Hapus" class="btn-ic btn-del"><i class="fa-solid fa-trash-can"></i></button></td></tr><?php endforeach;?></tbody></table><?php endif;?></div>
<script>
const TAB='<?=$tab?>',BID=<?=json_encode($bid)?>,JB=<?=json_encode($jb)?>;
function fld(n,v,l,type){return `<div class="mb-2"><label class="text-xs font-bold" style="text-transform:capitalize">${l}</label><input type="${type||'text'}" name="${n}" value="${(v??'').toString().replaceAll('"','')}" class="w-full border rounded p-2 mt-1"></div>`;}
function selFld(n,v,l,opts){return `<div class="mb-2"><label class="text-xs font-bold" style="text-transform:capitalize">${l}</label><select name="${n}" class="w-full border rounded p-2 mt-1">${opts}</select></div>`;}
function masterForm(id,d){d=d||{};let h=`<div class="flex justify-between items-center mb-3"><b>${id?'Edit':'Tambah'} <?=$tabs[$tab]?></b><button type="button" onclick="closeModal()" title="Tutup" class="w-8 h-8 rounded-full bg-red-50 border border-red-200 text-red-600 hover:bg-red-100 flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button></div><form onsubmit="masterSave(event,${id})">`;
const LBL={kode:'Kode',nama_bidang:'Nama Bidang',nama_sumber_dana:'Nama Sumber Dana',nama:'Nama',nama_satuan:'Nama Satuan',nama_rekening:'Nama Rekening',nama_kegiatan:'Nama Kegiatan',tahun:'Tahun',kelompok:'Kelompok',keterangan:'Keterangan',bidang_id:'Bidang',jenis_belanja_id:'Jenis Belanja',status:'Status',tanggal_mulai:'Tanggal Mulai',tanggal_selesai:'Tanggal Selesai',prioritas:'Prioritas'};
const lb=k=>LBL[k]||k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
const c={bidang:['kode','nama_bidang'],sumber_dana:['kode','nama_sumber_dana'],jenis_belanja:['kode','nama'],satuan:['kode','nama_satuan'],rekening:['kode','nama_rekening','kelompok'],kegiatan:['kode','nama_kegiatan'],tahun_anggaran:['tahun']}[TAB]||[];
c.forEach(k=>{h+=fld(k,d[k],lb(k),k==='tahun'?'number':'text');});
if(TAB==='kegiatan'){h+=selFld('bidang_id',d.bidang_id,'Bidang',`<option value="">-</option>${BID.map(b=>`<option value="${b.id}" ${d.bidang_id==b.id?'selected':''}>${b.nama_bidang}</option>`).join('')}`);}
if(TAB==='rekening'){h+=selFld('jenis_belanja_id',d.jenis_belanja_id,'Jenis Belanja',`<option value="">-</option>${JB.map(b=>`<option value="${b.id}" ${d.jenis_belanja_id==b.id?'selected':''}>${b.nama}</option>`).join('')}`);}
if(['bidang','sumber_dana','jenis_belanja','satuan','rekening','kegiatan'].includes(TAB)){h+=fld('keterangan',d.keterangan,'Keterangan');}
if(TAB==='tahun_anggaran'){h+=fld('tanggal_mulai',d.tanggal_mulai,'Tanggal Mulai','date')+fld('tanggal_selesai',d.tanggal_selesai,'Tanggal Selesai','date')+fld('keterangan',d.keterangan,'Keterangan');}
if(['bidang','sumber_dana','jenis_belanja','satuan','rekening','kegiatan','tahun_anggaran'].includes(TAB)){const st=d.status||'aktif';h+=selFld('status',st,'Status',`<option ${st==='aktif'?'selected':''}>aktif</option><option ${st==='nonaktif'?'selected':''}>nonaktif</option>${TAB==='tahun_anggaran'?'<option '+(st==='draft'?'selected':'')+'>draft</option><option '+(st==='selesai'?'selected':'')+'>selesai</option><option '+(st==='dikunci'?'selected':'')+'>dikunci</option>':''}`);}
if(TAB==='kegiatan'){h+=selFld('prioritas',d.prioritas||'sedang','Prioritas',['rendah','sedang','tinggi','mendesak'].map(p=>`<option ${d.prioritas===p?'selected':''}>${p}</option>`).join(''));}
h+=`<button class="w-full bg-emerald-700 text-white rounded p-2 font-bold">Simpan</button></form>`;openModal(h);}
async function masterSave(e,id){e.preventDefault();const f=new FormData(e.target);const d={act:'master_save',table:TAB,id};f.forEach((v,k)=>d[k]=v);const j=await api('x',d);if(j.ok){ok('Tersimpan');setTimeout(()=>location.reload(),800);}else err(j.msg||'Gagal');}
async function masterDel(id){if(!await ask('Hapus data ini?','Batal bila dipakai relasi lain.','Ya, Hapus'))return;const j=await api('x',{act:'master_delete',table:TAB,id});if(j.ok){ok('Dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg||'Gagal');}
</script>
