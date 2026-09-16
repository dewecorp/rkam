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
<div class="text-sm text-gray-600"><?=Security::e($r['nama_bidang']??'-')?> • PJ: <?=Security::e($r['penanggung_jawab']??'-')?><?php if(!empty($r['waktu_pelaksanaan'])):?> • <?=Security::e(Helper::tglIndo($r['waktu_pelaksanaan']))?><?php endif;?></div></div>
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
<?php foreach($items as $it):?><tr class="border-b"><td class="p-1"><?=Security::e($it['uraian'])?><div class="text-xs text-gray-500"><?=Security::e($it['kode_rekening']??'')?></div></td><td class="p-1 text-right"><?=number_format((float)$it['volume'],0,',','.')?> <?=Security::e($it['satuan_text']??'')?></td><td class="p-1 text-right"><?=Security::rupiah($it['harga_satuan'])?></td><td class="p-1 text-right"><?=Security::rupiah($it['jumlah'])?></td></tr><?php endforeach;?>
</tbody></table></div>
<div><div class="bg-white rounded shadow p-4 mb-3"><b>Sumber Dana</b>
<table class="w-full text-sm mt-2"><?php foreach($sm as $x):?><tr class="border-b"><td class="p-1"><?=Security::e($x['nama_sumber_dana'])?></td><td class="p-1 text-right"><?=Security::rupiah($x['jumlah'])?></td></tr><?php endforeach;?></table></div>
<div class="bg-white rounded-2xl shadow border border-emerald-100 p-4"><div class="flex items-center justify-between"><b>Riwayat Status</b><span class="text-[10px] text-gray-400"><?=count($hist)?> Perubahan</span></div>
<?php $rev=null; foreach($hist as $hh){ if(in_array($hh['status_to']??'', ['ditolak','direvisi']) && trim($hh['catatan']??'')!==''){ $rev=$hh; break; } } ?>
<?php if(in_array($r['status'], ['ditolak','direvisi']) && $rev): ?>
<div class="mt-3 rounded-2xl border-2 <?=($r['status']==='ditolak'?'border-red-300 bg-red-50':'border-amber-300 bg-amber-50')?> p-3 text-sm">
<div class="flex items-center gap-2 font-extrabold <?=($r['status']==='ditolak'?'text-red-700':'text-amber-800')?>"><i class="fa-solid <?=($r['status']==='ditolak'?'fa-circle-exclamation':'fa-pen-to-square')?>"></i> <?=($r['status']==='ditolak'?'Ditolak — Perlu Perbaikan':'Minta Revisi — Segera Tindaklanjuti')?></div>
<div class="mt-1.5 rounded-xl bg-white border <?=($r['status']==='ditolak'?'border-red-200':'border-amber-200')?> p-2.5 text-slate-800">"<?=Security::e($rev['catatan'])?>"</div>
<div class="mt-1.5 text-[11px] text-gray-500">Oleh <b><?=Security::e($rev['nama']??'Verifikator')?></b> • <?=Security::e(Helper::tglWib($rev['created_at']))?> • <?=Security::e(Helper::timeAgo($rev['created_at']))?></div></div>
<?php endif; ?>
<div class="mt-3 max-h-96 overflow-y-auto pr-1 space-y-0 text-sm">
<?php if(!$hist):?><div class="p-4 text-center text-gray-400 text-sm">Draft — belum ada riwayat perubahan.</div><?php endif; ?>
<?php foreach($hist as $i=>$h): $hst=Helper::logStyle($h['status_to']??''); $isNote=in_array($h['status_to']??'', ['ditolak','direvisi']); ?>
<div class="relative flex gap-3 <?=($i<count($hist)-1?'pb-4':'')?>">
<?php if($i<count($hist)-1):?><span class="absolute left-4 top-9 bottom-0 w-px bg-emerald-100"></span><?php endif; ?>
<div class="w-8 h-8 rounded-full <?=$hst['bg']?> <?=$hst['tx']?> flex items-center justify-center text-xs shrink-0 shadow-sm"><i class="fa-solid <?=$hst['icon']?>"></i></div>
<div class="min-w-0 flex-1 rounded-xl border <?=($isNote?'border-red-200 bg-red-50/60':'border-gray-100 bg-gray-50')?> px-3 py-2">
<div class="flex flex-wrap items-center gap-1.5"><?=Helper::badgeStatus($h['status_from']??'draft')?><i class="fa-solid fa-arrow-right text-[10px] text-gray-400"></i><?=Helper::badgeStatus($h['status_to'])?></div>
<div class="mt-1 text-[11px] text-gray-500">Oleh <b class="text-slate-700"><?=Security::e($h['nama']??'Sistem')?></b> • <?=Security::e(Helper::tglWib($h['created_at']))?> • <?=Security::e(Helper::timeAgo($h['created_at']))?></div>
<?php if(trim($h['catatan']??'')!==''): ?>
<div class="mt-1.5 rounded-lg px-2.5 py-2 text-[13px] <?=($h['status_to']==='ditolak'?'bg-red-100 border border-red-200 text-red-800':($h['status_to']==='direvisi'?'bg-amber-100 border border-amber-200 text-amber-900':'bg-white border border-gray-200 text-slate-700'))?>"><i class="fa-solid fa-quote-left mr-1 opacity-60"></i><?=Security::e($h['catatan'])?></div>
<?php endif; ?></div></div>
<?php endforeach; ?></div></div></div></div>
<script>
async function setStatus(id,to){if(!await ask('Ubah status ke '+to+'?','Riwayat tercatat di audit log.','Ya, Lanjut'))return;const j=await api('x',{act:'rkam_status',id,to});if(j.ok){ok('Status '+to);setTimeout(()=>location.reload(),800);}else err(j.msg);}
async function askNote(id,to){const r=await Swal.fire({title:'Alasan '+to,input:'textarea',inputPlaceholder:'Wajib isi alasan...',showCancelButton:true,cancelButtonText:'Batal',confirmButtonText:'Kirim',confirmButtonColor:'#059669',inputValidator:v=>!v?'Alasan wajib diisi':null});if(!r.isConfirmed)return;const j=await api('x',{act:'rkam_status',id,to,catatan:r.value});if(j.ok){ok('Status '+to);setTimeout(()=>location.reload(),800);}else err(j.msg);}
</script>
