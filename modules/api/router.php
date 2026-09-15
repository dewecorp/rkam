<?php
Security::onlyPost();
CSRF::check();
$act=$_POST['act']??''; if(!$act)$act=$arg??'';
switch($act){
case 'dashboard_data': {
Rbac::check('dashboard');
$th=Helper::tahunAktif($pdo);$tid=(int)($th['id']??0);
$bl=['labels'=>[],'data'=>[]];
for($m=1;$m<=12;$m++){$bl['labels'][]=date('M',mktime(0,0,0,$m,1));$s=$pdo->prepare("SELECT COALESCE(SUM(r.jumlah),0) FROM realisasi r JOIN rkam k ON k.id=r.rkam_id WHERE k.tahun_id=? AND MONTH(r.tanggal_transaksi)=?");$s->execute([$tid,$m]);$bl['data'][]=(float)$s->fetchColumn();}
$s=$pdo->query("SELECT s.nama_sumber_dana,COALESCE(SUM(rs.jumlah),0) t FROM sumber_dana s LEFT JOIN rkam_sumber_dana rs ON rs.sumber_dana_id=s.id LEFT JOIN rkam k ON k.id=rs.rkam_id AND k.tahun_id=".(int)$tid." GROUP BY s.id");
$sm=['labels'=>[],'data'=>[]];foreach($s->fetchAll() as $r){$sm['labels'][]=$r['nama_sumber_dana'];$sm['data'][]=(float)$r['t'];}
$b=$pdo->query("SELECT b.nama_bidang,COALESCE(SUM(k.total_anggaran),0) t FROM bidang b LEFT JOIN rkam k ON k.bidang_id=b.id AND k.tahun_id=".(int)$tid." GROUP BY b.id ORDER BY t DESC LIMIT 10");
$bd=['labels'=>[],'data'=>[]];foreach($b->fetchAll() as $r){$bd['labels'][]=$r['nama_bidang'];$bd['data'][]=(float)$r['t'];}
Security::json(['ok'=>true,'data'=>['bulan'=>$bl,'sumber'=>$sm,'bidang'=>$bd]]);
}
case 'notif_list': {
$l=$pdo->prepare("SELECT * FROM notifications WHERE (role_target=? OR user_id=?) ORDER BY is_read ASC, id DESC LIMIT 20");$l->execute([Auth::role(),Auth::id()]);
Security::json(['ok'=>true,'data'=>$l->fetchAll()]);
}
case 'notif_read': {
$id=(int)($_POST['id']??0);
$s=$pdo->prepare("SELECT * FROM notifications WHERE id=? AND (role_target=? OR user_id=? OR role_target IS NULL)");$s->execute([$id,Auth::role(),Auth::id()]);$n=$s->fetch();
if($n){$pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);}
$target=BASE_URL.'rkam';
if($n&&$n['modul']==='rkam'&&$n['record_id'])$target=BASE_URL.'rkam/detail/'.$n['record_id'];
Security::json(['ok'=>true,'target'=>$target]);
}
case 'notif_read_all': {
$pdo->prepare("UPDATE notifications SET is_read=1 WHERE role_target=? OR user_id=?")->execute([Auth::role(),Auth::id()]);
Security::json(['ok'=>true]);
}
case 'tahun_aktif': {
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);
$s=$pdo->prepare("SELECT * FROM tahun_anggaran WHERE id=?");$s->execute([$id]);$t=$s->fetch();
if(!$t)Security::json(['ok'=>false,'msg'=>'Tahun tidak ada'],404);
if($t['status']==='dikunci'&&Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'Tahun dikunci'],403);
$pdo->beginTransaction();try{
$pdo->query("UPDATE tahun_anggaran SET status='selesai' WHERE status='aktif'");
$pdo->prepare("UPDATE tahun_anggaran SET status='aktif' WHERE id=?")->execute([$id]);
Logger::log($pdo,'ganti_tahun','tahun_anggaran',$id);
$pdo->commit();Security::json(['ok'=>true,'tahun'=>$t['tahun']]);
}catch(Exception $ex){$pdo->rollBack();Security::json(['ok'=>false,'msg'=>'Gagal ganti tahun']);}
}
case 'master_save': {
Rbac::check('dashboard');
$t=$_POST['table']??'';$id=(int)($_POST['id']??0);
$allow=['bidang'=>['kode','nama_bidang','keterangan','status'],'sumber_dana'=>['kode','nama_sumber_dana','keterangan','status'],'jenis_belanja'=>['kode','nama','keterangan','status'],'satuan'=>['kode','nama_satuan','keterangan','status'],'rekening'=>['kode','nama_rekening','kelompok','jenis_belanja_id','keterangan','status'],'kegiatan'=>['kode','nama_kegiatan','bidang_id','indikator','tujuan','sasaran','volume','satuan_id','waktu_pelaksanaan','penanggung_jawab','prioritas','status','keterangan'],'tahun_anggaran'=>['tahun','tanggal_mulai','tanggal_selesai','status','keterangan']];
if(!isset($allow[$t]))Security::json(['ok'=>false,'msg'=>'Tabel invalid'],400);
if(!in_array(Auth::role(),['superadmin','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$f=$allow[$t];$d=[];foreach($f as $c){$d[$c]=trim($_POST[$c]??'');}
if($t==='tahun_anggaran'&&$d['status']==='aktif'){$pdo->query("UPDATE tahun_anggaran SET status='selesai' WHERE status='aktif'");}
if($id>0){$set=implode(',',array_map(fn($c)=>"$c=?",array_keys($d)));$s=$pdo->prepare("UPDATE $t SET $set WHERE id=?");$s->execute([...array_values($d),$id]);Logger::log($pdo,'edit',$t,$id,null,$d);}
else{$s=$pdo->prepare("INSERT INTO $t(".implode(',',array_keys($d)).") VALUES(".rtrim(str_repeat('?,',count($d)),',').")");$s->execute(array_values($d));Logger::log($pdo,'tambah',$t,$pdo->lastInsertId(),null,$d);}
Security::json(['ok'=>true,'msg'=>'Tersimpan']);
}
case 'master_delete': {
if(!in_array(Auth::role(),['superadmin','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$t=$_POST['table']??'';$id=(int)($_POST['id']??0);
if(!in_array($t,['bidang','sumber_dana','jenis_belanja','satuan','rekening','kegiatan','tahun_anggaran']))Security::json(['ok'=>false,'msg'=>'Invalid'],400);
try{$pdo->prepare("DELETE FROM $t WHERE id=?")->execute([$id]);Logger::log($pdo,'hapus',$t,$id);}catch(Exception $ex){Security::json(['ok'=>false,'msg'=>'Data dipakai relasi lain']);}
Security::json(['ok'=>true]);
}
case 'rkam_save': {
if(!Rbac::can('rkam.create')&&!Rbac::can('rkam.view'))Security::json(['ok'=>false,'msg'=>'No permission'],403);
if(!in_array(Auth::role(),['superadmin','bendahara','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);
$tahun_id=(int)($_POST['tahun_id']??0);$bidang_id=(int)($_POST['bidang_id']??0)?:null;
$nama=trim($_POST['nama_kegiatan']??'');if(!$tahun_id||!$nama)Security::json(['ok'=>false,'msg'=>'Tahun & nama wajib']);
if($id>0){$c=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$c->execute([$id]);$old=$c->fetch();if(!$old)Security::json(['ok'=>false,'msg'=>'Not found'],404);
if(in_array($old['status'],['dikunci'])&&!in_array(Auth::role(),['superadmin','kepala_madrasah']))Security::json(['ok'=>false,'msg'=>'RKAM telah dikunci dan tidak dapat diubah']); }
$items=json_decode($_POST['items']??'[]',true)?:[];$sumber=json_decode($_POST['sumber']??'[]',true)?:[];
if(count($items)==0)Security::json(['ok'=>false,'msg'=>'Minimal 1 item anggaran']);
$total=0;$clean=[];
foreach($items as $it){$vol=Security::money($it['volume']??1);$hr=Security::money($it['harga_satuan']??0);$j=round($vol*$hr,2);$total=round($total+$j,2);
$clean[]= ['rekening_id'=>(int)($it['rekening_id']??0)?:null,'kode_rekening'=>substr(trim($it['kode_rekening']??''),0,50),'uraian'=>substr(trim($it['uraian']??''),0,255),'volume'=>$vol,'satuan_text'=>substr(trim($it['satuan']??''),0,50),'harga_satuan'=>$hr,'jumlah'=>$j,'keterangan'=>''];}
if($total<=0)Security::json(['ok'=>false,'msg'=>'Total harus > 0']);
$tsum=0;$validS=[];$seen=[];
foreach($sumber as $s2){$sid=(int)($s2['id']??0);$j=Security::money($s2['jumlah']??0);
if($sid<=0||$j<=0)continue;
if(isset($seen[$sid]))Security::json(['ok'=>false,'msg'=>'Sumber dana ganda. Gabung jadi 1 baris.']);
$seen[$sid]=1;$tsum=round($tsum+$j,2);$validS[]=['id'=>$sid,'jumlah'=>$j];}
if(!$validS)Security::json(['ok'=>false,'msg'=>'Minimal 1 sumber dana dengan jumlah > 0']);
$chk=$pdo->prepare("SELECT COUNT(*) FROM sumber_dana WHERE id=? AND status='aktif'");
foreach($validS as $vs){$chk->execute([$vs['id']]);if(!$chk->fetchColumn())Security::json(['ok'=>false,'msg'=>'Sumber dana tidak valid']);}
if(abs($tsum-$total)>0.01)Security::json(['ok'=>false,'msg'=>'Total sumber dana ('.number_format($tsum,0).') != total anggaran ('.number_format($total,0).')']);
$pdo->beginTransaction();try{
$base=['tahun_id'=>$tahun_id,'bidang_id'=>$bidang_id,'kegiatan_id'=>!empty($_POST['kegiatan_id'])?(int)$_POST['kegiatan_id']:null,'kode_kegiatan'=>substr(trim($_POST['kode_kegiatan']??''),0,50),'nama_kegiatan'=>$nama,'tujuan'=>$_POST['tujuan']??null,'sasaran'=>$_POST['sasaran']??null,'indikator'=>$_POST['indikator']??null,'penanggung_jawab'=>$_POST['penanggung_jawab']??null,'waktu_pelaksanaan'=>$_POST['waktu_pelaksanaan']??null,'prioritas'=>in_array($_POST['prioritas']??'', ['rendah','sedang','tinggi','mendesak'])?$_POST['prioritas']:'sedang','keterangan'=>$_POST['keterangan']??null,'total_anggaran'=>$total,'updated_by'=>Auth::id()];
if($id>0){$set=implode(',',array_map(fn($k)=>"$k=?",array_keys($base)));$pdo->prepare("UPDATE rkam SET $set WHERE id=?")->execute([...array_values($base),$id]);$rid=$id;$pdo->prepare("DELETE FROM rkam_items WHERE rkam_id=?")->execute([$rid]);$pdo->prepare("DELETE FROM rkam_sumber_dana WHERE rkam_id=?")->execute([$rid]);Logger::log($pdo,'edit','rkam',$rid,$old,$base);}
else{$base['created_by']=Auth::id();$base['status']='draft';$pdo->prepare("INSERT INTO rkam(".implode(',',array_keys($base)).") VALUES(".rtrim(str_repeat('?,',count($base)),',').")")->execute(array_values($base));$rid=(int)$pdo->lastInsertId();
$km=Helper::setting($pdo,'kode_madrasah','MI-SF');
$seq=(int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM rkam")->fetchColumn();
$ndok=Helper::nomorDok($seq,$km);$try=0;
while($try<10){try{$pdo->prepare("UPDATE rkam SET nomor_dokumen=? WHERE id=?")->execute([$ndok,$rid]);break;}catch(Exception $dup){$try++;$ndok=Helper::nomorDok($seq,$km).'-'.$try;if($try>=10)throw $dup;}}
Logger::log($pdo,'tambah','rkam',$rid,null,$base);Logger::notify($pdo,'RKAM baru diajukan','Kegiatan: '.$nama,'kepala_madrasah',null,'rkam',$rid);}
foreach($clean as $ci){$pdo->prepare("INSERT INTO rkam_items(rkam_id,rekening_id,kode_rekening,uraian,volume,satuan_text,harga_satuan,jumlah,keterangan) VALUES(?,?,?,?,?,?,?,?,?)")->execute([$rid,$ci['rekening_id'],$ci['kode_rekening'],$ci['uraian'],$ci['volume'],$ci['satuan_text'],$ci['harga_satuan'],$ci['jumlah'],$ci['keterangan']]);}
foreach($validS as $s2){$pdo->prepare("INSERT INTO rkam_sumber_dana(rkam_id,sumber_dana_id,jumlah) VALUES(?,?,?)")->execute([$rid,(int)$s2['id'],Security::money($s2['jumlah'])]);}
$pdo->commit();Security::json(['ok'=>true,'id'=>$rid]);
}catch(Exception $ex){$pdo->rollBack();error_log('rkam_save: '.$ex->getMessage());Security::json(['ok'=>false,'msg'=>'Gagal simpan: '.$ex->getMessage()]);}
}
case 'rkam_delete': {
if(!in_array(Auth::role(),['superadmin','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);$s=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$s->execute([$id]);$r=$s->fetch();if(!$r)Security::json(['ok'=>false,'msg'=>'Not found'],404);
if(in_array($r['status'],['disetujui','dikunci','diverifikasi'])&&Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'Status '.$r['status'].' tidak boleh dihapus']);
$pdo->prepare("DELETE FROM rkam WHERE id=?")->execute([$id]);Logger::log($pdo,'hapus','rkam',$id,$r);Security::json(['ok'=>true]);
}
case 'rkam_status': {
$id=(int)($_POST['id']??0);$to=$_POST['to']??'';$cat=trim($_POST['catatan']??'');
$s=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$s->execute([$id]);$r=$s->fetch();if(!$r)Security::json(['ok'=>false,'msg'=>'Not found'],404);
$role=Auth::role();
$rules=['diajukan'=>['superadmin','bendahara','operator'],'diverifikasi'=>['superadmin','kepala_madrasah'],'disetujui'=>['superadmin','kepala_madrasah'],'ditolak'=>['superadmin','kepala_madrasah'],'direvisi'=>['superadmin','kepala_madrasah'],'dikunci'=>['superadmin','kepala_madrasah'],'draft'=>['superadmin','kepala_madrasah']];
if(!isset($rules[$to]))Security::json(['ok'=>false,'msg'=>'Status invalid'],400);
if(!in_array($role,$rules[$to]))Security::json(['ok'=>false,'msg'=>'No permission'],403);
if(in_array($to,['ditolak','direvisi'])&&!$cat)Security::json(['ok'=>false,'msg'=>'Alasan wajib diisi']);
$pdo->beginTransaction();try{
$pdo->prepare("UPDATE rkam SET status=? WHERE id=?")->execute([$to,$id]);
$pdo->prepare("INSERT INTO rkam_status_history(rkam_id,status_from,status_to,catatan,created_by) VALUES(?,?,?,?,?)")->execute([$id,$r['status'],$to,$cat,Auth::id()]);
Logger::log($pdo,$to,'rkam',$id,['status'=>$r['status']],['status'=>$to]);Logger::notify($pdo,'RKAM '.$to,$r['nama_kegiatan'].' : '.$cat,null,null,'rkam',$id);
$pdo->commit();Security::json(['ok'=>true]);
}catch(Exception $ex){$pdo->rollBack();Security::json(['ok'=>false,'msg'=>'Gagal']);}
}
case 'realisasi_save': {
if(!in_array(Auth::role(),['superadmin','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);$rkam_id=(int)($_POST['rkam_id']??0);
$s=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$s->execute([$rkam_id]);$rk=$s->fetch();if(!$rk)Security::json(['ok'=>false,'msg'=>'RKAM invalid']);
if(in_array($rk['status'],['draft','dikunci'])&&Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'RKAM status '.$rk['status']]);
$vol=Security::money($_POST['volume']??1);$hr=Security::money($_POST['harga']??0);$jum=round($vol*$hr,2);
$tot=$pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=?".($id?" AND id<>$id":''));$tot->execute([$rkam_id]);$used=(float)$tot->fetchColumn();
if($used+$jum>(float)$rk['total_anggaran']+0.01){$allow=($_POST['allow_over']??'')==='1';$reason=trim($_POST['over_reason']??'');
if(!$allow||!$reason)Security::json(['ok'=>false,'msg'=>'Realisasi melebihi anggaran sebesar Rp '.number_format($used+$jum-$rk['total_anggaran'],0,',','.'),'over'=>true,'lebih'=>$used+$jum-$rk['total_anggaran']]);
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission overbudget'],403);}
else{$allow=!empty($_POST['allow_over'])?1:0;$reason=$_POST['over_reason']??null;}
$bf=null;$fn=null;
if(!empty($_FILES['bukti']['name'])){$mx=MAX_UPLOAD_MB*1024*1024;if($_FILES['bukti']['size']>$mx)Security::json(['ok'=>false,'msg'=>'File > '.MAX_UPLOAD_MB.'MB']);
$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($_FILES['bukti']['tmp_name']);$okm=['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
if(!isset($okm[$mime]))Security::json(['ok'=>false,'msg'=>'File harus JPG/PNG/PDF']);
$ext=$okm[$mime];if(!is_dir(UPLOAD_DIR))mkdir(UPLOAD_DIR,0755,true);$fn=date('YmdHis').'_'.bin2hex(random_bytes(8)).'.'.$ext;
if(!move_uploaded_file($_FILES['bukti']['tmp_name'],UPLOAD_DIR.$fn))Security::json(['ok'=>false,'msg'=>'Upload gagal']);}
$d=['rkam_id'=>$rkam_id,'tahun_id'=>$rk['tahun_id'],'tanggal_transaksi'=>$_POST['tanggal_transaksi']??date('Y-m-d'),'nomor_bukti'=>substr($_POST['nomor_bukti']??'',0,100),'sumber_dana_id'=>(int)($_POST['sumber_dana_id']??0)?:null,'kode_rekening'=>substr($_POST['kode_rekening']??'',0,50),'uraian'=>substr(trim($_POST['uraian']??''),0,255),'volume'=>$vol,'satuan'=>substr($_POST['satuan']??'',0,50),'harga'=>$hr,'jumlah'=>$jum,'penerima_vendor'=>substr($_POST['penerima_vendor']??'',0,150),'nomor_nota'=>substr($_POST['nomor_nota']??'',0,100),'keterangan'=>$_POST['keterangan']??null,'allow_overbudget'=>$allow?1:0,'overbudget_reason'=>$reason,'created_by'=>Auth::id()];
if(!$d['uraian']||!$d['tanggal_transaksi'])Security::json(['ok'=>false,'msg'=>'Uraian & tanggal wajib']);
$pdo->beginTransaction();try{
if($id>0){if($fn)$d['bukti_file']=$fn;$set=implode(',',array_map(fn($k)=>"$k=?",array_keys($d)));$pdo->prepare("UPDATE realisasi SET $set WHERE id=?")->execute([...array_values($d),$id]);Logger::log($pdo,'edit','realisasi',$id);}
else{$d['bukti_file']=$fn;$pdo->prepare("INSERT INTO realisasi(".implode(',',array_keys($d)).") VALUES(".rtrim(str_repeat('?,',count($d)),',').")")->execute(array_values($d));Logger::log($pdo,'tambah','realisasi',$pdo->lastInsertId(),null,$d);}
$pdo->commit();Security::json(['ok'=>true]);
}catch(Exception $ex){$pdo->rollBack();error_log($ex->getMessage());Security::json(['ok'=>false,'msg'=>'Gagal simpan']);}
}
case 'realisasi_delete': {
if(!in_array(Auth::role(),['superadmin','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);$pdo->prepare("DELETE FROM realisasi WHERE id=?")->execute([$id]);Logger::log($pdo,'hapus','realisasi',$id);Security::json(['ok'=>true]);
}
case 'user_save': {
if(Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);$nama=trim($_POST['nama']??'');$un=trim($_POST['username']??'');$role=trim($_POST['role']??'');$st=trim($_POST['status']??'aktif');
if(!$nama||!$un||!in_array($role,['superadmin','kepala_madrasah','bendahara','operator','viewer']))Security::json(['ok'=>false,'msg'=>'Data invalid']);
if($id>0){$pdo->prepare("UPDATE users SET nama=?,username=?,role=?,email=?,no_hp=?,status=? WHERE id=?")->execute([$nama,$un,$role,$_POST['email']??null,$_POST['no_hp']??null,$st,$id]);Logger::log($pdo,'edit','users',$id);}
else{if(strlen($_POST['password']??'')<6)Security::json(['ok'=>false,'msg'=>'Password min 6']);$h=password_hash($_POST['password'],PASSWORD_DEFAULT);$pdo->prepare("INSERT INTO users(nama,username,password,role,email,no_hp,status) VALUES(?,?,?,?,?,?,?)")->execute([$nama,$un,$h,$role,$_POST['email']??null,$_POST['no_hp']??null,$st]);Logger::log($pdo,'tambah','users',$pdo->lastInsertId());}
Security::json(['ok'=>true]);
}
case 'user_reset': {if(Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'No permission'],403);$id=(int)($_POST['id']??0);$h=password_hash('admin123',PASSWORD_DEFAULT);$pdo->prepare("UPDATE users SET password=?,must_change_password=1 WHERE id=?")->execute([$h,$id]);Logger::log($pdo,'reset_password','users',$id);Security::json(['ok'=>true,'msg'=>'Password direset ke admin123']);}
case 'user_delete': {if(Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'No permission'],403);$id=(int)($_POST['id']??0);if($id===Auth::id())Security::json(['ok'=>false,'msg'=>'Tidak bisa hapus diri']);$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);Logger::log($pdo,'hapus','users',$id);Security::json(['ok'=>true]);}
case 'setting_save': {
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$m=['nama_madrasah','nsm','npsn','alamat','desa','kecamatan','kabupaten','provinsi','kode_pos','email','telepon','nama_kepala','nip_kepala','nama_bendahara','nip_bendahara'];
$d=[];foreach($m as $k)$d[$k]=trim($_POST[$k]??'');
$pdo->prepare("UPDATE madrasah SET nama_madrasah=?,nsm=?,npsn=?,alamat=?,desa=?,kecamatan=?,kabupaten=?,provinsi=?,kode_pos=?,email=?,telepon=?,nama_kepala=?,nip_kepala=?,nama_bendahara=?,nip_bendahara=? WHERE id=1")->execute(array_values($d));
foreach(['app_name','kode_madrasah','max_upload_mb'] as $k){if(isset($_POST[$k]))$pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES(?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$k,trim($_POST[$k])]);}
Logger::log($pdo,'edit','pengaturan',1);Security::json(['ok'=>true,'logo'=>$d['logo']??null]);
}
case 'logo_upload': {
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
if(empty($_FILES['logo']['tmp_name']))Security::json(['ok'=>false,'msg'=>'File kosong']);
if($_FILES['logo']['size']>2*1024*1024)Security::json(['ok'=>false,'msg'=>'Logo > 2 MB']);
$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($_FILES['logo']['tmp_name']);
$okm=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
if(!isset($okm[$mime]))Security::json(['ok'=>false,'msg'=>'Logo harus JPG/PNG/WebP']);
if(!is_dir(LOGO_DIR))mkdir(LOGO_DIR,0755,true);
$fn='logo_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$okm[$mime];
if(!move_uploaded_file($_FILES['logo']['tmp_name'],LOGO_DIR.$fn))Security::json(['ok'=>false,'msg'=>'Upload gagal']);
$old=$pdo->query("SELECT logo FROM madrasah WHERE id=1")->fetchColumn();
$pdo->prepare("UPDATE madrasah SET logo=? WHERE id=1")->execute([$fn]);
if($old&&is_file(LOGO_DIR.$old))@unlink(LOGO_DIR.$old);
Logger::log($pdo,'edit','logo',1,['logo'=>$old],['logo'=>$fn]);Security::json(['ok'=>true,'logo'=>$fn]);
}
case 'logo_delete': {
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$old=$pdo->query("SELECT logo FROM madrasah WHERE id=1")->fetchColumn();
$pdo->prepare("UPDATE madrasah SET logo=NULL WHERE id=1")->execute();
if($old&&is_file(LOGO_DIR.$old))@unlink(LOGO_DIR.$old);
Logger::log($pdo,'hapus','logo',1,['logo'=>$old]);Security::json(['ok'=>true]);
}
case 'change_pass': {$id=Auth::id();$old=$_POST['old']??'';$new=$_POST['new']??'';if(strlen($new)<6)Security::json(['ok'=>false,'msg'=>'Min 6 karakter']);
$s=$pdo->prepare("SELECT password FROM users WHERE id=?");$s->execute([$id]);if(!password_verify($old,$s->fetchColumn()))Security::json(['ok'=>false,'msg'=>'Password lama salah']);
$pdo->prepare("UPDATE users SET password=?,must_change_password=0 WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$id]);Security::json(['ok'=>true]);}
case 'backup_run': {
if(Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'No permission'],403);
try{
$tables=$pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);$out="-- Backup RKAM ".date('Y-m-d H:i:s')."\n";
foreach($tables as $t){$c=$pdo->query("SHOW CREATE TABLE `$t`")->fetch();$out.="\n".$c['Create Table'].";\n";$rows=$pdo->query("SELECT * FROM `$t`")->fetchAll();foreach($rows as $r){$v=array_map(fn($x)=>$x===null?'NULL':$pdo->quote($x),$r);$out.="INSERT INTO `$t` VALUES(".implode(',',$v).");\n";}}
$d=__DIR__.'/../../db_backups/';if(!is_dir($d))mkdir($d,0755,true);$f='rkam_'.date('Ymd_His').'.sql';$w=file_put_contents($d.$f,$out);
if($w===false||!is_file($d.$f)||filesize($d.$f)===0)Security::json(['ok'=>false,'msg'=>'Gagal tulis file backup. Cek izin folder db_backups.']);
$at=date('Y-m-d H:i:s');
$pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES('last_backup_at',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$at]);
Logger::log($pdo,'backup','database');
$sz=filesize($d.$f);$szF=$sz<1024?$sz.' B':($sz<1048576?round($sz/1024,1).' KB':round($sz/1048576,2).' MB');
Security::json(['ok'=>true,'file'=>$f,'size'=>$szF,'at'=>$at]);
}catch(Exception $ex){error_log('backup_run: '.$ex->getMessage());Security::json(['ok'=>false,'msg'=>'Backup gagal: '.$ex->getMessage()]);}
}
case 'backup_delete': {
if(Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'No permission'],403);
$f=basename($_POST['file']??'');
if(!preg_match('/^rkam_[\d_]+\.sql$/',$f))Security::json(['ok'=>false,'msg'=>'Nama file invalid']);
$p=__DIR__.'/../../db_backups/'.$f;
if(!is_file($p))Security::json(['ok'=>false,'msg'=>'File tidak ada'],404);
unlink($p);Logger::log($pdo,'hapus','backup',null,['file'=>$f]);Security::json(['ok'=>true]);
}
case 'backup_restore': {
if(Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'No permission'],403);
if(empty($_FILES['file']['tmp_name']))Security::json(['ok'=>false,'msg'=>'File kosong']);
if($_FILES['file']['size']>20*1024*1024)Security::json(['ok'=>false,'msg'=>'File > 20 MB']);
$ext=strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
if($ext!=='sql')Security::json(['ok'=>false,'msg'=>'File harus .sql']);
$sql=file_get_contents($_FILES['file']['tmp_name']);
if(stripos($sql,'CREATE TABLE')===false&&stripos($sql,'INSERT INTO')===false)Security::json(['ok'=>false,'msg'=>'File SQL tidak valid']);
if(preg_match('/\b(DROP\s+DATABASE|GRANT\s|CREATE\s+USER|LOAD\s+DATA|INTO\s+(OUTFILE|DUMPFILE))\b/i',$sql))Security::json(['ok'=>false,'msg'=>'Perintah berbahaya ditolak']);
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$stmts=array_filter(array_map('trim',preg_split('/;\s*\n/',str_replace("\r\n","\n",$sql))));
$ok=0;try{
$allow=['users','madrasah','tahun_anggaran','bidang','satuan','sumber_dana','jenis_belanja','rekening','kegiatan','rkam','rkam_items','rkam_sumber_dana','rkam_status_history','realisasi','activity_logs','notifications','app_settings','login_attempts'];
foreach($stmts as $st){$st=trim($st);if($st===''||str_starts_with($st,'--'))continue;
if(preg_match('/^(INSERT\s+INTO|CREATE\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE|TRUNCATE\s+TABLE)\s+`?(\w+)/i',$st,$mm)){if(!in_array(strtolower($mm[2]),$allow))continue;}
else continue;
$pdo->exec($st);$ok++;}
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
}catch(Exception $ex){$pdo->exec("SET FOREIGN_KEY_CHECKS=1");error_log('restore: '.$ex->getMessage());Security::json(['ok'=>false,'msg'=>'Restore gagal: '.$ex->getMessage()]);}
Logger::log($pdo,'restore','database');Security::json(['ok'=>true,'statements'=>$ok]);
}
case 'master_opt': {
Security::json(['ok'=>true,'data'=>['rekening'=>$pdo->query("SELECT * FROM rekening WHERE status='aktif' LIMIT 200")->fetchAll(),'satuan'=>$pdo->query("SELECT * FROM satuan WHERE status='aktif'")->fetchAll(),'sumber'=>$pdo->query("SELECT * FROM sumber_dana WHERE status='aktif'")->fetchAll(),'kegiatan'=>$pdo->query("SELECT * FROM kegiatan WHERE status='aktif' LIMIT 200")->fetchAll()]]);
}
case 'rkam_get': {
$id=(int)($_POST['id']??0);$s=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$s->execute([$id]);$r=$s->fetch();if(!$r)Security::json(['ok'=>false,'msg'=>'Not found'],404);
$i=$pdo->prepare("SELECT * FROM rkam_items WHERE rkam_id=?");$i->execute([$id]);
$sm=$pdo->prepare("SELECT rs.*,s.nama_sumber_dana nama FROM rkam_sumber_dana rs JOIN sumber_dana s ON s.id=rs.sumber_dana_id WHERE rs.rkam_id=?");$sm->execute([$id]);
$r['items']=$i->fetchAll();$r['sumber']=$sm->fetchAll();Security::json(['ok'=>true,'data'=>$r]);
}
default: Security::json(['ok'=>false,'msg'=>'Unknown act'],400);
}
