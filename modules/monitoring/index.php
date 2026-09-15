<?php
$th=Helper::tahunAktif($pdo);$tid=$th['id']??0;
$rows=$pdo->query("SELECT k.*,b.nama_bidang,(SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=k.id) AS jml_real FROM rkam k LEFT JOIN bidang b ON b.id=k.bidang_id WHERE k.tahun_id=".(int)$tid." ORDER BY k.id DESC")->fetchAll();
function bar($p){$c=$p>=100?'bg-red-600':($p>=90?'bg-orange-500':($p>=80?'bg-yellow-500':'bg-emerald-600'));$lbl=$p>=100?'melebihi anggaran':($p>=90?'perhatian':($p>=80?'peringatan':($p>0?'sedang':'rendah')));return "<div class='h-2 bg-gray-200 rounded'><div class='h-2 rounded $c' style='width:".min(100,$p)."%'></div></div><div class='text-xs text-gray-500'>$lbl • $p%</div>";}
?>
<div class="bg-white rounded shadow overflow-auto"><table class="w-full text-sm min-w-[800px]"><thead><tr class="bg-gray-50 border-b text-left"><th class="p-2">Kegiatan</th><th class="p-2 text-right">Anggaran</th><th class="p-2 text-right">Realisasi</th><th class="p-2 text-right">Sisa</th><th class="p-2 w-48">Serapan</th><th class="p-2">Status</th></tr></thead><tbody>
<?php foreach($rows as $r):$sisa=(float)$r['total_anggaran']-(float)$r['jml_real'];$p=Helper::persen($r['jml_real'],$r['total_anggaran']);?>
<tr class="border-b"><td class="p-2"><a href="<?=BASE_URL?>rkam/detail/<?=$r['id']?>" class="font-semibold text-emerald-800"><?=Security::e($r['nama_kegiatan'])?></a><div class="text-xs text-gray-500"><?=Security::e($r['nama_bidang']??'')?></div></td>
<td class="p-2 text-right"><?=Security::rupiah($r['total_anggaran'])?></td><td class="p-2 text-right"><?=Security::rupiah($r['jml_real'])?></td><td class="p-2 text-right"><?=Security::rupiah($sisa)?></td><td class="p-2"><?=bar($p)?></td><td class="p-2"><?=Helper::badgeStatus($r['status'])?></td></tr>
<?php endforeach;?><?php if(!$rows):?><tr><td colspan="6" class="p-8 text-center text-gray-500">Belum ada data.</td></tr><?php endif;?></tbody></table></div>
