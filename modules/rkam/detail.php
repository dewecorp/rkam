<?php
$id=(int)($_GET['id']??0);
$s=$pdo->prepare("SELECT k.*,b.nama_bidang,t.tahun FROM rkam k LEFT JOIN bidang b ON b.id=k.bidang_id LEFT JOIN tahun_anggaran t ON t.id=k.tahun_id WHERE k.id=?");$s->execute([$id]);$r=$s->fetch();
if(!$r){echo '<div class="bg-white p-8 rounded shadow text-center">Data tidak ditemukan.</div>';return;}
$items=$pdo->prepare("SELECT * FROM rkam_items WHERE rkam_id=?");$items->execute([$id]);$items=$items->fetchAll();
$sm=$pdo->prepare("SELECT rs.*,s.nama_sumber_dana FROM rkam_sumber_dana rs JOIN sumber_dana s ON s.id=rs.sumber_dana_id WHERE rs.rkam_id=?");$sm->execute([$id]);$sm=$sm->fetchAll();
$real=$pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=?");$real->execute([$id]);$treal=(float)$real->fetchColumn();
$hist=$pdo->prepare("SELECT h.*,u.nama FROM rkam_status_history h LEFT JOIN users u ON u.id=h.created_by WHERE h.rkam_id=? ORDER BY h.id DESC");$hist->execute([$id]);$hist=$hist->fetchAll();
$sisa=(float)$r['total_anggaran']-$treal;$pers=Helper::persen($treal,$r['total_anggaran']);
$role=Auth::role();
?>
<div class="bg-white rounded shadow p-4 mb-3">
<div class="flex flex-wrap gap-2 items-start justify-between">
<div><div class="text-xs text-gray-500"><?=Security::e($r['nomor_dokumen'])?> • <?=Security::e($r['kode_kegiatan'])?> • <?=Security::e($r['tahun'])?></div>
<h2 class="text-xl font-bold"><?=Security::e($r['nama_kegiatan'])?></h2>
<div class="text-sm text-gray-600"><?=Security::e($r['nama_bidang']??'-')?> • PJ: <?=Security::e($r['penanggung_jawab']??'-')?> • <?=Security::e($r['waktu_pelaksanaan']??'')?></div></div>
<div><?=Helper::badgeStatus($r['status'])?></div></div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 text-sm">
<div class="bg-gray-50 p-2 rounded">Anggaran<br><b><?=Security::rupiah($r['total_anggaran'])?></b></div>
<div class="bg-gray-50 p-2 rounded">Realisasi<br><b><?=Security::rupiah($treal)?></b></div>
<div class="bg-gray-50 p-2 rounded">Sisa<br><b><?=Security::rupiah($sisa)?></b></div>
<div class="bg-gray-50 p-2 rounded">Serapan<br><b><?=$pers?>%</b></div></div>
<?php if($r['tujuan']):?><div class="mt-2 text-sm"><b>Tujuan:</b> <?=Security::e($r['tujuan'])?></div><?php endif;?>
<div class="mt-3 flex flex-wrap gap-2 no-print">
<a href="<?=BASE_URL?>rkam" title="Kembali" class="btn-ic btn-view" style="width:auto;padding:0 .8rem"><i class="fa-solid fa-arrow-left"></i></a>
<a href="<?=BASE_URL?>print/rkam?id=<?=$id?>" target="_blank" title="Cetak" class="btn-ic btn-view" style="width:auto;padding:0 .8rem"><i class="fa-solid fa-print"></i></a>
<?php if(in_array($role,['superadmin','bendahara','operator'])&&!in_array($r['status'],['dikunci'])):?><button onclick="rkamForm(<?=$id?>)" title="Edit" class="btn-ic btn-edit"><i class="fa-solid fa-pen-to-square"></i></button><?php endif;?>
<?php if(in_array($role,['superadmin','bendahara','operator'])&&$r['status']==='draft'):?><button onclick="setStatus(<?=$id?>,'diajukan')" title="Ajukan" class="btn-ic btn-dl"><i class="fa-solid fa-paper-plane"></i></button><?php endif;?>
<?php if(in_array($role,['superadmin','kepala_madrasah'])):?>
<?php if($r['status']==='diajukan'):?><button onclick="setStatus(<?=$id?>,'diverifikasi')" title="Verifikasi" class="btn-ic btn-view"><i class="fa-solid fa-circle-check"></i></button><?php endif;?>
<?php if($r['status']==='diverifikasi'):?><button onclick="setStatus(<?=$id?>,'disetujui')" title="Setujui" class="btn-ic btn-dl"><i class="fa-solid fa-thumbs-up"></i></button><?php endif;?>
<?php if(in_array($r['status'],['diajukan','diverifikasi'])):?><button onclick="askNote(<?=$id?>,'ditolak')" title="Tolak" class="btn-ic btn-del"><i class="fa-solid fa-thumbs-down"></i></button> <button onclick="askNote(<?=$id?>,'direvisi')" title="Minta Revisi" class="btn-ic btn-edit"><i class="fa-solid fa-rotate-left"></i></button><?php endif;?>
<?php if($r['status']==='disetujui'):?><button onclick="setStatus(<?=$id?>,'dikunci')" title="Kunci" class="btn-ic btn-del"><i class="fa-solid fa-lock"></i></button><?php endif;?>
<?php if($r['status']==='dikunci'&&in_array($role,['superadmin','kepala_madrasah'])):?><button onclick="setStatus(<?=$id?>,'draft')" title="Unlock" class="btn-ic btn-key"><i class="fa-solid fa-lock-open"></i></button><?php endif;?>
<?php if(in_array($r['status'],['ditolak','direvisi'])):?><button onclick="setStatus(<?=$id?>,'diajukan')" title="Ajukan Ulang" class="btn-ic btn-dl"><i class="fa-solid fa-paper-plane"></i></button><?php endif;?>
<?php endif;?></div></div>
<div class="grid lg:grid-cols-2 gap-3">
<div class="bg-white rounded shadow p-4"><b>Item Anggaran</b>
<table class="w-full text-sm mt-2"><thead><tr class="bg-gray-50 border-b text-left"><th class="p-1">Uraian</th><th class="p-1 text-right">Volume</th><th class="p-1 text-right">Harga</th><th class="p-1 text-right">Jumlah</th></tr></thead><tbody>
<?php foreach($items as $it):?><tr class="border-b"><td class="p-1"><?=Security::e($it['uraian'])?><div class="text-xs text-gray-500"><?=Security::e($it['kode_rekening']??'')?></div></td><td class="p-1 text-right"><?=Security::e($it['volume'].' '.($it['satuan_text']??''))?></td><td class="p-1 text-right"><?=Security::rupiah($it['harga_satuan'])?></td><td class="p-1 text-right"><?=Security::rupiah($it['jumlah'])?></td></tr><?php endforeach;?>
</tbody></table></div>
<div><div class="bg-white rounded shadow p-4 mb-3"><b>Sumber Dana</b>
<table class="w-full text-sm mt-2"><?php foreach($sm as $x):?><tr class="border-b"><td class="p-1"><?=Security::e($x['nama_sumber_dana'])?></td><td class="p-1 text-right"><?=Security::rupiah($x['jumlah'])?></td></tr><?php endforeach;?></table></div>
<div class="bg-white rounded shadow p-4"><b>Riwayat Status</b><div class="mt-2 space-y-2 text-sm">
<?php if(!$hist):?><div class="text-gray-500">Draft → belum ada riwayat</div><?php endif;?>
<?php foreach($hist as $h):?><div class="border-l-2 border-emerald-500 pl-2"><div><b><?=Security::e($h['status_from']??'-')?> → <?=Security::e($h['status_to'])?></b> <span class="text-xs text-gray-500"><?=Security::e($h['created_at'])?> • <?=Security::e($h['nama']??'')?></span></div><?php if($h['catatan']):?><div class="text-gray-600"><?=Security::e($h['catatan'])?></div><?php endif;?></div><?php endforeach;?></div></div></div></div>
<script>
async function setStatus(id,to){if(!await ask('Ubah status ke '+to+'?','Riwayat tercatat di audit log.','Ya, Lanjut'))return;const j=await api('x',{act:'rkam_status',id,to});if(j.ok){ok('Status '+to);setTimeout(()=>location.reload(),800);}else err(j.msg);}
async function askNote(id,to){const r=await Swal.fire({title:'Alasan '+to,input:'textarea',inputPlaceholder:'Wajib isi alasan...',showCancelButton:true,cancelButtonText:'Batal',confirmButtonText:'Kirim',confirmButtonColor:'#059669',inputValidator:v=>!v?'Alasan wajib diisi':null});if(!r.isConfirmed)return;const j=await api('x',{act:'rkam_status',id,to,catatan:r.value});if(j.ok){ok('Status '+to);setTimeout(()=>location.reload(),800);}else err(j.msg);}
</script>
