<?php
$th=Helper::tahunAktif($pdo); $tid=$th['id']??0;
$tot=$pdo->query("SELECT COALESCE(SUM(total_anggaran),0) t FROM rkam WHERE tahun_id=".(int)$tid)->fetchColumn();
$rea=$pdo->query("SELECT COALESCE(SUM(r.jumlah),0) FROM realisasi r JOIN rkam k ON k.id=r.rkam_id WHERE k.tahun_id=".(int)$tid)->fetchColumn();
$sisa=(float)$tot-(float)$rea;
$pers=Helper::persen($rea,$tot);
$jk=$pdo->query("SELECT COUNT(*) FROM rkam WHERE tahun_id=".(int)$tid)->fetchColumn();
$st=$pdo->query("SELECT status,COUNT(*) c FROM rkam WHERE tahun_id=".(int)$tid." GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$approved=(int)($st['disetujui']??0)+(int)($st['diverifikasi']??0)+(int)($st['dikunci']??0);
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
<?php foreach([['Total Anggaran',$tot,'from-emerald-500 to-teal-600','fa-wallet',Security::rupiah($tot)],['Total Realisasi',$rea,'from-blue-500 to-indigo-600','fa-money-bill-wave',Security::rupiah($rea)],['Sisa Anggaran',$sisa,'from-amber-500 to-orange-600','fa-piggy-bank',Security::rupiah($sisa)],['Serapan',$pers.'%','from-violet-500 to-purple-600','fa-gauge-high',$pers.'%']] as $c):?>
<div class="relative overflow-hidden rounded-2xl shadow-lg p-4 text-white bg-gradient-to-br <?=$c[2]?>">
<div class="text-xs font-medium text-white/80"><?=$c[0]?></div><div class="text-xl font-extrabold mt-1"><?=$c[4]?></div>
<div class="absolute -right-2 -bottom-3 w-20 h-20 rounded-full bg-white/15"></div><div class="absolute right-4 bottom-3 w-11 h-11 rounded-2xl bg-white/20 flex items-center justify-center text-lg"><i class="fa-solid <?=$c[3]?>"></i></div></div>
<?php endforeach;?></div>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4 text-sm">
<?php foreach([['Kegiatan',$jk,'emerald','fa-clipboard-list'],['Disetujui',$approved,'blue','fa-circle-check'],['Draft',($st['draft']??0),'amber','fa-pen-to-square'],['Tahun Aktif',($th['tahun']??'-'),'violet','fa-calendar-days']] as $s):?>
<div class="relative overflow-hidden bg-white p-3 rounded-2xl shadow border-l-4 border-<?=$s[2]?>-500"><div class="text-xs text-gray-500 flex items-center gap-1"><i class="fa-solid <?=$s[3]?> text-<?=$s[2]?>-500"></i> <?=$s[0]?></div><div class="text-lg font-extrabold text-slate-800"><?=$s[1]?></div></div>
<?php endforeach;?></div>
<div class="grid lg:grid-cols-5 gap-4">
<div class="bg-white p-4 rounded shadow lg:col-span-3"><b>Anggaran vs Realisasi / bulan</b><div class="h-64"><canvas id="chBulan"></canvas></div></div>
<div class="bg-white p-4 rounded shadow lg:col-span-2"><b>Per Sumber Dana</b><div class="h-64 flex items-center justify-center"><canvas id="chSumber"></canvas></div></div></div>
<?php
$logs=$pdo->query("SELECT l.*,u.nama FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 30")->fetchAll();
?>
<div class="grid lg:grid-cols-3 gap-4 mt-4">
<div class="bg-white p-4 rounded shadow lg:col-span-2"><b>Per Bidang</b><div class="h-72"><canvas id="chBidang"></canvas></div></div>
<div class="bg-white p-4 rounded shadow"><div class="flex items-center justify-between mb-3"><b>Aktivitas Terakhir</b><span class="text-[10px] text-gray-400">Hapus Otomatis 24 Jam</span></div>
<div class="max-h-72 overflow-y-auto pr-1 space-y-0">
<?php if(!$logs):?><div class="p-6 text-center text-sm text-gray-400">Belum ada aktivitas.</div><?php endif;?>
<?php foreach($logs as $i=>$g):$st2=Helper::logStyle($g['aktivitas']);$da=!empty($g['data_after'])?json_decode($g['data_after'],true):null;$lbl=$g['aktivitas'];if($g['aktivitas']==='update'&&is_array($da)&&isset($da['to']))$lbl='update ke '.($da['to']??'');elseif($g['aktivitas']==='impor'&&is_array($da)&&isset($da['inserted']))$lbl='impor '.$da['inserted'].' data';?>
<div class="relative flex gap-3 pb-4 <?=($i<count($logs)-1?'':'pb-0')?>">
<?php if($i<count($logs)-1):?><span class="absolute left-4 top-9 bottom-0 w-px bg-emerald-100"></span><?php endif;?>
<div class="w-8 h-8 rounded-full <?=$st2['bg']?> <?=$st2['tx']?> flex items-center justify-center text-xs shrink-0 shadow-sm"><i class="fa-solid <?=$st2['icon']?>"></i></div>
<div class="min-w-0 flex-1 bg-gray-50 hover:bg-emerald-50/60 border border-gray-100 rounded-xl px-3 py-2">
<div class="text-[13px] text-slate-800"><b><?=Security::e($g['nama']??'Sistem')?></b> <?=Security::e($lbl)?> <span class="text-gray-400">• <?=Security::e($g['modul'])?><?=($g['record_id']?' #'.$g['record_id']:'')?></span></div>
<div class="flex items-center gap-1.5 mt-1 text-[11px] text-gray-500"><i class="fa-solid fa-calendar-day text-emerald-500"></i><span class="font-semibold"><?=Security::e(Helper::tglWib($g['created_at']))?></span><span class="text-gray-300">•</span><span><?=Security::e(Helper::timeAgo($g['created_at']))?></span></div></div></div>
<?php endforeach;?></div></div></div>
<script>
fetch(BASE+'api/dashboard_data',{method:'POST',body:new URLSearchParams({csrf_token:CSRF})}).then(r=>r.json()).then(j=>{
if(!j.ok)return;const d=j.data;
new Chart(document.getElementById('chBulan'),{type:'bar',data:{labels:d.bulan.labels,datasets:[{label:'Realisasi',data:d.bulan.data,backgroundColor:'#10b981',borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}});
new Chart(document.getElementById('chSumber'),{type:'doughnut',data:{labels:d.sumber.labels,datasets:[{data:d.sumber.data,backgroundColor:['#10b981','#3b82f6','#f59e0b','#8b5cf6','#ef4444','#14b8a6','#f97316','#64748b'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,cutout:'62%',plugins:{legend:{position:'bottom',labels:{boxWidth:10,boxHeight:10,padding:8,usePointStyle:true,pointStyle:'circle',font:{size:10}}}}}});
new Chart(document.getElementById('chBidang'),{type:'bar',data:{labels:d.bidang.labels,datasets:[{label:'Anggaran',data:d.bidang.data,backgroundColor:'#059669',borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{display:false}}}});
});
</script>
