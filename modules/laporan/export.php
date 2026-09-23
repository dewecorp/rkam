<?php
$jenis=$_GET['jenis']??'rkam'; $tid=(int)($_GET['tahun']??(Helper::tahunAktif($pdo)['id']??0));
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_'.$jenis.'_'.$tid.'.csv"');
$out=fopen('php://output','w');
fputs($out,"\xEF\xBB\xBF");
$rk=$pdo->prepare("SELECT k.*,b.nama_bidang,(SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=k.id) AS jml_real FROM rkam k LEFT JOIN bidang b ON b.id=k.bidang_id WHERE k.tahun_id=? ORDER BY k.id");$rk->execute([$tid]);
fputcsv($out,['No','Kegiatan','Bidang','Anggaran','Realisasi','Sisa','Persen','Status']);
$no=1;foreach($rk->fetchAll() as $r){fputcsv($out,[$no++,$r['nama_kegiatan'],$r['nama_bidang']??'',$r['total_anggaran'],$r['jml_real'],$r['total_anggaran']-$r['jml_real'],Helper::persen($r['jml_real'],$r['total_anggaran']),$r['status']]);}
fclose($out);exit;
