<?php
$jenis=$arg??($_GET['jenis']??'rkam'); $tid=(int)($_GET['tahun']??(Helper::tahunAktif($pdo)['id']??0));
$m=Helper::madrasah($pdo); $th=$pdo->prepare("SELECT * FROM tahun_anggaran WHERE id=?");$th->execute([$tid]);$th=$th->fetch();
$rk=$pdo->prepare("SELECT k.*,b.nama_bidang,(SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=k.id) AS jml_real FROM rkam k LEFT JOIN bidang b ON b.id=k.bidang_id WHERE k.tahun_id=? ORDER BY k.id");$rk->execute([$tid]);$rk=$rk->fetchAll();
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Cetak <?=Security::e($jenis)?></title>
<script src="https://cdn.tailwindcss.com"></script><style>@media print{.no-print{display:none}}</style></head>
<body class="p-8 max-w-4xl mx-auto text-sm" onload="window.print()">
<div class="text-center border-b-2 border-black pb-3 mb-4">
<div class="flex items-center justify-center gap-3">
<?php if(!empty($m['logo'])&&is_file(__DIR__.'/../../uploads/logo/'.$m['logo'])):?><img src="<?=BASE_URL?>uploads/logo/<?=Security::e($m['logo'])?>" style="width:64px;height:64px;object-fit:contain"><?php endif;?>
<div><div class="font-bold text-xl"><?=Security::e($m['nama_madrasah']??'Madrasah')?></div>
<div class="text-xs"><?=Security::e(trim(($m['alamat']??'').' '.($m['desa']??'').' '.($m['kecamatan']??'').' '.($m['kabupaten']??'')))?> • NSM <?=Security::e($m['nsm']??'-')?> • NPSN <?=Security::e($m['npsn']??'-')?></div></div></div>
<div class="font-bold mt-2 uppercase">Laporan <?=Security::e($jenis)?> Tahun <?=Security::e($th['tahun']??'')?></div></div>
<table class="w-full border-collapse border border-black"><thead><tr class="bg-gray-100"><th class="border border-black p-1 text-left">Kegiatan</th><th class="border border-black p-1">Anggaran</th><th class="border border-black p-1">Realisasi</th><th class="border border-black p-1">Sisa</th><th class="border border-black p-1">%</th></tr></thead><tbody>
<?php $ta=0;$tr=0;foreach($rk as $r):$ta+=$r['total_anggaran'];$tr+=$r['jml_real'];?><tr><td class="border border-black p-1"><?=Security::e($r['nama_kegiatan'])?></td><td class="border border-black p-1 text-right"><?=number_format($r['total_anggaran'],0,',','.')?></td><td class="border border-black p-1 text-right"><?=number_format($r['jml_real'],0,',','.')?></td><td class="border border-black p-1 text-right"><?=number_format($r['total_anggaran']-$r['jml_real'],0,',','.')?></td><td class="border border-black p-1 text-center"><?=Helper::persen($r['jml_real'],$r['total_anggaran'])?>%</td></tr><?php endforeach;?>
<tr class="font-bold"><td class="border border-black p-1">TOTAL</td><td class="border border-black p-1 text-right"><?=number_format($ta,0,',','.')?></td><td class="border border-black p-1 text-right"><?=number_format($tr,0,',','.')?></td><td class="border border-black p-1 text-right"><?=number_format($ta-$tr,0,',','.')?></td><td class="border border-black p-1 text-center"><?=Helper::persen($tr,$ta)?>%</td></tr></tbody></table>
<div class="flex justify-between mt-10 text-center"><div>Mengetahui,<br>Kepala Madrasah<br><br><br><br><b><u><?=Security::e($m['nama_kepala']??'...')?></u></b><br>NIP. <?=Security::e($m['nip_kepala']??'-')?></div>
<div><?=Security::e($m['kabupaten']??'')?>, <?php $hb=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; echo date('j').' '.$hb[(int)date('n')].' '.date('Y'); ?><br>Bendahara<br><br><br><br><b><u><?=Security::e($m['nama_bendahara']??'...')?></u></b><br>NIP. <?=Security::e($m['nip_bendahara']??'-')?></div></div>
<div class="no-print mt-6 text-center"><button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded">Cetak</button></div>
</body></html>
