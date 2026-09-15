<?php
class Helper{
public static function setting($pdo,$k,$d=''){$s=$pdo->prepare("SELECT svalue FROM app_settings WHERE skey=?");$s->execute([$k]);$r=$s->fetchColumn();return $r!==false?$r:$d;}
public static function tahunAktif($pdo){$s=$pdo->query("SELECT * FROM tahun_anggaran WHERE status='aktif' LIMIT 1");$r=$s->fetch();if(!$r){$s=$pdo->query("SELECT * FROM tahun_anggaran ORDER BY tahun DESC LIMIT 1");$r=$s->fetch();}return $r;}
public static function madrasah($pdo){return $pdo->query("SELECT * FROM madrasah WHERE id=1")->fetch()?:[];}
public static function nomorDok($no,$kode='MI-SF'){ $b=(int)date('n');$y=date('Y');return sprintf('%03d',$no).'/RKAM/'.$kode.'/'.Security::romawi($b).'/'.$y;}
public static function persen($a,$b){$b=(float)$b;if($b<=0)return 0;return round(((float)$a/$b)*100,2);}
public static function badgeStatus($s){$m=['draft'=>'bg-gray-200 text-gray-700','diajukan'=>'bg-yellow-100 text-yellow-800','diverifikasi'=>'bg-blue-100 text-blue-800','disetujui'=>'bg-green-100 text-green-800','ditolak'=>'bg-red-100 text-red-800','direvisi'=>'bg-orange-100 text-orange-800','dikunci'=>'bg-slate-800 text-white','aktif'=>'bg-green-100 text-green-800','nonaktif'=>'bg-gray-200 text-gray-600','selesai'=>'bg-slate-200 text-slate-700'];$c=$m[$s]??'bg-gray-100';return '<span class="px-2 py-1 rounded text-xs font-semibold '.$c.'">'.htmlspecialchars($s).'</span>';}
}
