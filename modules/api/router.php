<?php
Security::onlyPost();
CSRF::check();
$act=$_POST['act']??''; if(!$act)$act=$arg??'';
switch($act){
case 'dashboard_data': {
Rbac::check('dashboard');
$th=Helper::tahunAktif($pdo);$tid=(int)($th['id']??0);
$bl=['labels'=>[],'data'=>[]];
for($m=1;$m<=12;$m++){$bl['labels'][]=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][$m-1];$s=$pdo->prepare("SELECT COALESCE(SUM(r.jumlah),0) FROM realisasi r JOIN rkam k ON k.id=r.rkam_id WHERE k.tahun_id=? AND MONTH(r.tanggal_transaksi)=?");$s->execute([$tid,$m]);$bl['data'][]=(float)$s->fetchColumn();}
$s=$pdo->query("SELECT s.nama_sumber_dana,COALESCE(SUM(rs.jumlah),0) t FROM sumber_dana s LEFT JOIN rkam_sumber_dana rs ON rs.sumber_dana_id=s.id LEFT JOIN rkam k ON k.id=rs.rkam_id AND k.tahun_id=".(int)$tid." GROUP BY s.id");
$sm=['labels'=>[],'data'=>[]];foreach($s->fetchAll() as $r){$sm['labels'][]=$r['nama_sumber_dana'];$sm['data'][]=(float)$r['t'];}
$b=$pdo->query("SELECT b.nama_bidang,COALESCE(SUM(k.total_anggaran),0) t FROM bidang b LEFT JOIN rkam k ON k.bidang_id=b.id AND k.tahun_id=".(int)$tid." GROUP BY b.id ORDER BY t DESC LIMIT 10");
$bd=['labels'=>[],'data'=>[]];foreach($b->fetchAll() as $r){$bd['labels'][]=$r['nama_bidang'];$bd['data'][]=(float)$r['t'];}
Security::json(['ok'=>true,'data'=>['bulan'=>$bl,'sumber'=>$sm,'bidang'=>$bd]]);
}
case 'notif_list': {
if(Auth::role()==='superadmin'){$l=$pdo->query("SELECT * FROM notifications ORDER BY is_read ASC, id DESC LIMIT 20");}
else{$l=$pdo->prepare("SELECT * FROM notifications WHERE (role_target=? OR role_target IS NULL OR role_target='' OR user_id=?) ORDER BY is_read ASC, id DESC LIMIT 20");$l->execute([Auth::role(),Auth::id()]);}
Security::json(['ok'=>true,'data'=>$l->fetchAll()]);
}
case 'notif_read': {
$id=(int)($_POST['id']??0);
if(Auth::role()==='superadmin'){$s=$pdo->prepare("SELECT * FROM notifications WHERE id=?");$s->execute([$id]);}
else{$s=$pdo->prepare("SELECT * FROM notifications WHERE id=? AND (role_target=? OR role_target IS NULL OR role_target='' OR user_id=?)");$s->execute([$id,Auth::role(),Auth::id()]);}
$n=$s->fetch();
if($n){$pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);}
$target=BASE_URL.'rkam';
if($n&&$n['modul']==='rkam'&&$n['record_id'])$target=BASE_URL.'rkam/detail/'.$n['record_id'];
Security::json(['ok'=>true,'target'=>$target]);
}
case 'notif_read_all': {
if(Auth::role()==='superadmin'){$pdo->query("UPDATE notifications SET is_read=1");}
else{$pdo->prepare("UPDATE notifications SET is_read=1 WHERE role_target=? OR role_target IS NULL OR role_target='' OR user_id=?")->execute([Auth::role(),Auth::id()]);}
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
 $allow=['bidang'=>['kode','nama_bidang','keterangan','status'],'sumber_dana'=>['kode','nama_sumber_dana','jumlah','keterangan','status'],'jenis_belanja'=>['kode','nama','keterangan','status'],'satuan'=>['kode','nama_satuan','keterangan','status'],'rekening'=>['kode','nama_rekening','kelompok','jenis_belanja_id','keterangan','status'],'kegiatan'=>['kode','nama_kegiatan','bidang_id','indikator','tujuan','sasaran','volume','satuan_id','waktu_pelaksanaan','guru_id','penanggung_jawab','prioritas','status','keterangan'],'guru'=>['kode','nama','nuptk','jabatan_id','jabatan','status'],'jabatan'=>['kode','nama','status'],'tahun_anggaran'=>['tahun','tanggal_mulai','tanggal_selesai','status','keterangan']];
if(!isset($allow[$t]))Security::json(['ok'=>false,'msg'=>'Tabel invalid'],400);
if(!in_array(Auth::role(),['superadmin','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$f=$allow[$t];$d=[];foreach($f as $c){$v=trim($_POST[$c]??'');$d[$c]=($v===''||strtolower($v)==='-'?null:$v);}
// normalisasi angka & FK kosong -> NULL
foreach(['bidang_id','satuan_id','jenis_belanja_id','guru_id','jabatan_id'] as $k){if(array_key_exists($k,$d)){$d[$k]=($d[$k]===null||$d[$k]==='')?null:(int)$d[$k];}}
if(array_key_exists('volume',$d)){$d['volume']=($d['volume']===null||$d['volume']==='')?1:(float)$d['volume'];}
if(array_key_exists('jumlah',$d)){$d['jumlah']=($d['jumlah']===null||$d['jumlah']==='')?0:(float)$d['jumlah'];}
if(array_key_exists('tahun',$d)){if($d['tahun']===null||$d['tahun']==='')Security::json(['ok'=>false,'msg'=>'Tahun wajib diisi']);$d['tahun']=(int)$d['tahun'];}
foreach(['tanggal_mulai','tanggal_selesai'] as $k){if(array_key_exists($k,$d)&&($d[$k]===null||$d[$k]===''))$d[$k]=null;}
foreach(['status','prioritas'] as $k){if(array_key_exists($k,$d)&&$d[$k]!==null)$d[$k]=strtolower(trim($d[$k]));}
// validasi wajib
if($t==='tahun_anggaran'&&$d['status']==='aktif'){$pdo->query("UPDATE tahun_anggaran SET status='selesai' WHERE status='aktif'");}
// kode otomatis bila kosong (sebelum validasi wajib)
if(in_array($t,['bidang','sumber_dana','jenis_belanja','satuan','rekening','kegiatan','guru','jabatan'])&&$d['kode']===null){
if($id>0){$o=$pdo->prepare("SELECT kode FROM `$t` WHERE id=?");$o->execute([$id]);$d['kode']=$o->fetchColumn()?:Helper::autoKode($pdo,$t,'');}
else{
$nm=$t==='bidang'?($d['nama_bidang']??''):($t==='sumber_dana'?($d['nama_sumber_dana']??''):($t==='satuan'?($d['nama_satuan']??''):($t==='rekening'?($d['nama_rekening']??''):($t==='kegiatan'?($d['nama_kegiatan']??''):($d['nama']??'')))));
$d['kode']=Helper::autoKode($pdo,$t,$nm);
}
}
if($t==='kegiatan'&&($d['kode']===null||$d['nama_kegiatan']===null))Security::json(['ok'=>false,'msg'=>'Kode & Nama Kegiatan wajib']);
if($t==='kegiatan'&&!empty($d['guru_id'])){$g=$pdo->prepare("SELECT nama FROM guru WHERE id=? AND status='aktif'");$g->execute([(int)$d['guru_id']]);$gn=$g->fetchColumn();if($gn)$d['penanggung_jawab']=$gn;}
if($t==='bidang'&&$d['nama_bidang']===null)Security::json(['ok'=>false,'msg'=>'Nama Bidang wajib']);
if($t==='sumber_dana'&&$d['nama_sumber_dana']===null)Security::json(['ok'=>false,'msg'=>'Nama Sumber Dana wajib']);
if($t==='jenis_belanja'&&$d['nama']===null)Security::json(['ok'=>false,'msg'=>'Nama wajib']);
if($t==='satuan'&&$d['nama_satuan']===null)Security::json(['ok'=>false,'msg'=>'Nama Satuan wajib']);
if($t==='rekening'&&($d['kode']===null||$d['nama_rekening']===null))Security::json(['ok'=>false,'msg'=>'Kode & Nama Rekening wajib']);
if($t==='guru'&&($d['kode']===null||$d['nama']===null))Security::json(['ok'=>false,'msg'=>'Kode & Nama Guru wajib']);
if($t==='guru'&&!empty($d['jabatan_id'])){$jj=$pdo->prepare("SELECT nama FROM jabatan WHERE id=? AND status='aktif'");$jj->execute([(int)$d['jabatan_id']]);$jn=$jj->fetchColumn();if($jn)$d['jabatan']=$jn;else Security::json(['ok'=>false,'msg'=>'Jabatan tidak valid']);}elseif($t==='guru'){$d['jabatan']=$d['jabatan']??null;}
if($t==='jabatan'&&$d['nama']===null)Security::json(['ok'=>false,'msg'=>'Nama Jabatan wajib']);
try{
if($id>0){$set=implode(',',array_map(fn($c)=>"$c=?",array_keys($d)));$s=$pdo->prepare("UPDATE $t SET $set WHERE id=?");$s->execute([...array_values($d),$id]);Logger::log($pdo,'edit',$t,$id,null,$d);}
else{$s=$pdo->prepare("INSERT INTO $t(".implode(',',array_keys($d)).") VALUES(".rtrim(str_repeat('?,',count($d)),',').")");$s->execute(array_values($d));$id=(int)$pdo->lastInsertId();Logger::log($pdo,'tambah',$t,$id,null,$d);}
if($t==='kegiatan'){$n=Helper::syncKegiatanToRkam($pdo,$id);Security::json(['ok'=>true,'msg'=>$n>0?"Tersimpan + sinkron $n RKAM draft":"Tersimpan"]);}
if($t==='guru'){try{$g=$pdo->prepare("SELECT nama,nuptk FROM guru WHERE id=?");$g->execute([$id]);if($gr=$g->fetch()){$pdo->prepare("UPDATE madrasah SET nama_kepala=?,nip_kepala=? WHERE kepala_guru_id=?")->execute([$gr['nama'],$gr['nuptk']??'',$id]);$pdo->prepare("UPDATE madrasah SET nama_bendahara=?,nip_bendahara=? WHERE bendahara_guru_id=?")->execute([$gr['nama'],$gr['nuptk']??'',$id]);}}catch(Exception $e){error_log('sync pimpinan: '.$e->getMessage());}}
}catch(Exception $ex){error_log('master_save: '.$ex->getMessage());$msg='Gagal simpan';if(stripos($ex->getMessage(),'Duplicate')!==false)$msg='Kode sudah dipakai';Security::json(['ok'=>false,'msg'=>$msg]);}
Security::json(['ok'=>true,'msg'=>'Tersimpan']);
}
case 'master_delete': {
if(!in_array(Auth::role(),['superadmin','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$t=$_POST['table']??'';$id=(int)($_POST['id']??0);
if(!in_array($t,['bidang','sumber_dana','jenis_belanja','satuan','rekening','kegiatan','guru','jabatan','tahun_anggaran']))Security::json(['ok'=>false,'msg'=>'Invalid'],400);
try{$pdo->prepare("DELETE FROM $t WHERE id=?")->execute([$id]);Logger::log($pdo,'hapus',$t,$id);}catch(Exception $ex){Security::json(['ok'=>false,'msg'=>'Data dipakai relasi lain']);}
Security::json(['ok'=>true]);
}
case 'guru_import': {
if(!in_array(Auth::role(),['superadmin','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$rows=json_decode($_POST['rows']??'[]',true);
if(!is_array($rows)||count($rows)<2)Security::json(['ok'=>false,'msg'=>'File kosong. Gunakan template .xlsx (baris 1 header, isi dari baris 2).']);
$head=array_map(fn($x)=>strtolower(trim((string)$x)),$rows[0]);
$ci=['kode'=>-1,'nama'=>-1,'nuptk'=>-1,'jabatan'=>-1,'jabatan_id'=>-1,'status'=>-1];
foreach($head as $i=>$h){
if(in_array($h,['kode']))$ci['kode']=$i;
elseif(in_array($h,['nama','nama*','nama guru','nama lengkap']))$ci['nama']=$i;
elseif(in_array($h,['nuptk','nip']))$ci['nuptk']=$i;
elseif(in_array($h,['jabatan','jabatan_id','nama jabatan']))$ci['jabatan']=$i;
elseif(in_array($h,['status']))$ci['status']=$i;
}
if($ci['nama']<0)Security::json(['ok'=>false,'msg'=>'Kolom Nama tidak ditemukan. Gunakan template.']);
$jabCache=[];
$jabByName=function($nm) use ($pdo,&$jabCache){
$nm=trim((string)$nm);if($nm==='')return [null,null];
$key=strtolower($nm);if(isset($jabCache[$key]))return $jabCache[$key];
$s=$pdo->prepare("SELECT id,nama FROM jabatan WHERE LOWER(nama)=? LIMIT 1");$s->execute([$key]);$r=$s->fetch();
if($r){$jabCache[$key]=[(int)$r['id'],$r['nama']];return $jabCache[$key];}
$kd=Helper::autoKode($pdo,'jabatan',$nm);
$pdo->prepare("INSERT INTO jabatan(kode,nama,status) VALUES(?,?,'aktif')")->execute([$kd,$nm]);
$id=(int)$pdo->lastInsertId();$jabCache[$key]=[$id,$nm];return $jabCache[$key];
};
$nuptkSeen=[];foreach($pdo->query("SELECT nuptk FROM guru WHERE nuptk IS NOT NULL AND nuptk<>''")->fetchAll(PDO::FETCH_COLUMN) as $x)$nuptkSeen[trim((string)$x)]=1;
$ins=0;$skip=0;$errs=[];
for($ri=1;$ri<count($rows);$ri++){
if(count($errs)>=20){$skip+=count($rows)-$ri;break;}
$r=$rows[$ri];
if(!is_array($r)){$skip++;continue;}
$nama=trim((string)($r[$ci['nama']]??''));
if($nama===''){$skip++;continue;}
$kode=trim((string)($ci['kode']>=0?($r[$ci['kode']]??''):''));if($kode===''||$kode==='-')$kode=null;
$nuptk=trim((string)($ci['nuptk']>=0?($r[$ci['nuptk']]??''):''));if($nuptk===''||$nuptk==='-')$nuptk=null;
if($nuptk!==null&&isset($nuptkSeen[$nuptk])){$skip++;$errs[]='Baris '.($ri+1).': NUPTK '.$nuptk.' sudah ada — dilewati';continue;}
$jabRaw=trim((string)($ci['jabatan']>=0?($r[$ci['jabatan']]??''):''));$jid=null;$jnm=null;
if($jabRaw!==''){
if(ctype_digit($jabRaw)){$s=$pdo->prepare("SELECT id,nama FROM jabatan WHERE id=? AND status='aktif'");$s->execute([(int)$jabRaw]);$jr=$s->fetch();if($jr){$jid=(int)$jr['id'];$jnm=$jr['nama'];}else{$skip++;$errs[]='Baris '.($ri+1).': jabatan ID '.$jabRaw.' tidak valid';continue;}}
else{try{[$jid,$jnm]=$jabByName($jabRaw);}catch(Exception $ex){$skip++;$errs[]='Baris '.($ri+1).': jabatan gagal ('.$jabRaw.')';continue;}}
}
$st=strtolower(trim((string)($ci['status']>=0?($r[$ci['status']]??'aktif'):'aktif')));if(!in_array($st,['aktif','nonaktif']))$st='aktif';
try{
if($kode!==null){$c=$pdo->prepare("SELECT id FROM guru WHERE kode=?");$c->execute([$kode]);if($c->fetch()){$skip++;$errs[]='Baris '.($ri+1).': kode '.$kode.' sudah dipakai';continue;}}
else{$kode=Helper::autoKode($pdo,'guru',$nama);}
$pdo->prepare("INSERT INTO guru(kode,nama,nuptk,jabatan_id,jabatan,status) VALUES(?,?,?,?,?,?)")->execute([$kode,$nama,$nuptk,$jid,$jnm,$st]);
if($nuptk!==null)$nuptkSeen[$nuptk]=1;
$ins++;
}catch(Exception $ex){$skip++;$errs[]='Baris '.($ri+1).': gagal simpan ('.$nama.')';}
}
Logger::log($pdo,'impor','guru',null,null,['inserted'=>$ins,'skipped'=>$skip]);
Security::json(['ok'=>true,'inserted'=>$ins,'skipped'=>$skip,'errors'=>$errs]);
}
case 'rkam_save': {
if(!Rbac::can('rkam.create')&&!Rbac::can('rkam.view'))Security::json(['ok'=>false,'msg'=>'No permission'],403);
if(!in_array(Auth::role(),['superadmin','bendahara','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);
$tahun_id=(int)($_POST['tahun_id']??0);$kegId=(int)($_POST['kegiatan_id']??0);
if(!$tahun_id)Security::json(['ok'=>false,'msg'=>'Langkah 1 belum: pilih Tahun Anggaran']);
if(!$kegId)Security::json(['ok'=>false,'msg'=>'Langkah 2 belum: pilih Kegiatan Master']);
$km=$pdo->prepare("SELECT k.*,b.nama_bidang FROM kegiatan k LEFT JOIN bidang b ON b.id=k.bidang_id WHERE k.id=? AND k.status='aktif'");$km->execute([$kegId]);$keg=$km->fetch();
if(!$keg)Security::json(['ok'=>false,'msg'=>'Kegiatan Master tidak valid / nonaktif']);
$km2=$pdo->prepare("SELECT k.*,g.nama AS guru_nama FROM kegiatan k LEFT JOIN guru g ON g.id=k.guru_id WHERE k.id=?");$km2->execute([$kegId]);$keg=$km2->fetch()?:$keg;
$pjNow=trim(($keg['guru_nama']??'')!==''?($keg['guru_nama']??''):($keg['penanggung_jawab']??''));
if($pjNow==='')Security::json(['ok'=>false,'msg'=>'Penanggung jawab kosong di Master ('.$keg['nama_kegiatan'].'). Pilih Guru di Master → Kegiatan dulu.']);
$bidang_id=$keg['bidang_id']?:null;
$nama=$keg['nama_kegiatan'];$kode=$keg['kode'];
if($id>0){$c=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$c->execute([$id]);$old=$c->fetch();if(!$old)Security::json(['ok'=>false,'msg'=>'Not found'],404);
if(in_array($old['status'],['dikunci'])&&!in_array(Auth::role(),['superadmin','kepala_madrasah']))Security::json(['ok'=>false,'msg'=>'RKAM telah dikunci dan tidak dapat diubah']);
if(in_array($old['status'],['diajukan','diverifikasi','disetujui'])&&!in_array(Auth::role(),['superadmin','kepala_madrasah']))Security::json(['ok'=>false,'msg'=>'Status '.$old['status'].' hanya bisa diubah kepala/superadmin. Minta revisi dulu.']); }
$items=json_decode($_POST['items']??'[]',true)?:[];$sumber=json_decode($_POST['sumber']??'[]',true)?:[];
if(count($items)==0)Security::json(['ok'=>false,'msg'=>'Minimal 1 item anggaran']);
$kmS=$pdo->prepare("SELECT k.*,s.nama_satuan AS satuan_nama FROM kegiatan k LEFT JOIN satuan s ON s.id=k.satuan_id WHERE k.id=?");$kmS->execute([$kegId]);$kegS=$kmS->fetch()?:$keg;
$expU=trim($kegS['nama_kegiatan']??'');$expV=(int)round((float)($kegS['volume']??1));$expS=trim($kegS['satuan_nama']??'');
$total=0;$clean=[];
foreach($items as $it){$vol=Security::vol($it['volume']??1);$hr=Security::money($it['harga_satuan']??0);$j=round($vol*$hr,2);$total=round($total+$j,2);
$uU=trim($it['uraian']??'');$uV=(int)$vol;$uS=trim($it['satuan']??'');
if($uU==='')Security::json(['ok'=>false,'msg'=>'Uraian item wajib diisi']);
if($uV!==$expV)Security::json(['ok'=>false,'msg'=>'Volume wajib sama dengan master ('.$expV.')']);
if($expS!==''&&$uS!==$expS)Security::json(['ok'=>false,'msg'=>'Satuan wajib sama dengan master ('.$expS.')']);
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
$base=['tahun_id'=>$tahun_id,'bidang_id'=>$bidang_id,'kegiatan_id'=>$kegId,'kode_kegiatan'=>substr($kode,0,50),'nama_kegiatan'=>$nama,'tujuan'=>$keg['tujuan']??null,'sasaran'=>$keg['sasaran']??null,'indikator'=>$keg['indikator']??null,'penanggung_jawab'=>$pjNow,'waktu_pelaksanaan'=>($keg['waktu_pelaksanaan']??null)?substr($keg['waktu_pelaksanaan'],0,10):null,'prioritas'=>($keg['prioritas']??'sedang'),'keterangan'=>$_POST['keterangan']??null,'total_anggaran'=>$total,'updated_by'=>Auth::id()];
if(!$id){$dup=$pdo->prepare("SELECT COUNT(*) FROM rkam WHERE tahun_id=? AND kegiatan_id=?");$dup->execute([$tahun_id,$kegId]);if($dup->fetchColumn())Security::json(['ok'=>false,'msg'=>'Kegiatan ini sudah ada di RKAM tahun ini. Edit data yang ada, jangan tambah ganda.']);}
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
Logger::log($pdo,$to,'rkam',$id,['status'=>$r['status']],['status'=>$to]);
if($to==='diajukan'){$jd=in_array($r['status'],['ditolak','direvisi'])?'RKAM Diajukan Kembali':'RKAM Diajukan';Logger::notify($pdo,$jd,$r['nama_kegiatan'].($cat?' : '.$cat:''),'kepala_madrasah',null,'rkam',$id);}
elseif(in_array($to,['ditolak','direvisi','diverifikasi','disetujui','dikunci'])){Logger::notify($pdo,'RKAM '.$to,$r['nama_kegiatan'].($cat?' : '.$cat:''),'bendahara',null,'rkam',$id);}
$pdo->commit();Security::json(['ok'=>true]);
}catch(Exception $ex){$pdo->rollBack();Security::json(['ok'=>false,'msg'=>'Gagal']);}
}
case 'realisasi_save': {
if(!in_array(Auth::role(),['superadmin','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$id=(int)($_POST['id']??0);$rkam_id=(int)($_POST['rkam_id']??0);
$s=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$s->execute([$rkam_id]);$rk=$s->fetch();if(!$rk)Security::json(['ok'=>false,'msg'=>'RKAM invalid']);
if(in_array($rk['status'],['draft','diajukan','diverifikasi','ditolak','direvisi','dikunci'])&&Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'Realisasi wajib setelah RKAM Disetujui (status kini: '.$rk['status'].'). Ajukan & setujui dulu.']);
$allow=!empty($_POST['allow_over'])?1:0;$reason=$_POST['over_reason']??null;
$bf=null;$fn=null;
if(!empty($_FILES['bukti']['name'])){$mx=MAX_UPLOAD_MB*1024*1024;if($_FILES['bukti']['size']>$mx)Security::json(['ok'=>false,'msg'=>'File > '.MAX_UPLOAD_MB.'MB']);
$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($_FILES['bukti']['tmp_name']);$okm=['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
if(!isset($okm[$mime]))Security::json(['ok'=>false,'msg'=>'File harus JPG/PNG/PDF']);
$ext=$okm[$mime];if(!is_dir(UPLOAD_DIR))mkdir(UPLOAD_DIR,0755,true);$fn=date('YmdHis').'_'.bin2hex(random_bytes(8)).'.'.$ext;
if(!move_uploaded_file($_FILES['bukti']['tmp_name'],UPLOAD_DIR.$fn))Security::json(['ok'=>false,'msg'=>'Upload gagal']);}
$d=['rkam_id'=>$rkam_id,'tahun_id'=>$rk['tahun_id'],'tanggal_transaksi'=>$_POST['tanggal_transaksi']??date('Y-m-d'),'nomor_bukti'=>substr($_POST['nomor_bukti']??'',0,100),'sumber_dana_id'=>(int)($_POST['sumber_dana_id']??0)?:null,'kode_rekening'=>substr($_POST['kode_rekening']??'',0,50),'uraian'=>'','volume'=>0,'satuan'=>'','harga'=>0,'jumlah'=>0,'penerima_vendor'=>substr($_POST['penerima_vendor']??'',0,150),'nomor_nota'=>substr($_POST['nomor_nota']??'',0,100),'keterangan'=>$_POST['keterangan']??null,'allow_overbudget'=>$allow?1:0,'overbudget_reason'=>$reason,'created_by'=>Auth::id()];
if(!$d['tanggal_transaksi'])Security::json(['ok'=>false,'msg'=>'Tanggal wajib']);
if($d['sumber_dana_id']){$ckS=$pdo->prepare("SELECT COUNT(*) FROM rkam_sumber_dana WHERE rkam_id=? AND sumber_dana_id=?");$ckS->execute([$rkam_id,$d['sumber_dana_id']]);if(!$ckS->fetchColumn())Security::json(['ok'=>false,'msg'=>'Sumber dana tidak ada di RKAM terpilih. Pilih sesuai RKAM.']);}

$items=$pdo->prepare("SELECT id,kode_rekening,uraian,volume,satuan_text,harga_satuan,jumlah FROM rkam_items WHERE rkam_id=? ORDER BY id");$items->execute([$rkam_id]);$rkItems=$items->fetchAll();
if(!$rkItems)Security::json(['ok'=>false,'msg'=>'Item RKAM kosong. Lengkapi RKAM dulu.']);
if($d['nomor_bukti']){$okB=in_array($d['nomor_bukti'],[$rk['kode_kegiatan']??'', $rk['nomor_dokumen']??''],true);if(!$okB)Security::json(['ok'=>false,'msg'=>'Nomor bukti wajib kode/nomor dokumen RKAM terpilih.']);}
$found=null;
if(str_starts_with($d['kode_rekening'],'ITEM-')){$iid=(int)substr($d['kode_rekening'],5);foreach($rkItems as $it){if((int)$it['id']===$iid){$found=$it;break;}}}
if(!$found&&$d['kode_rekening']){foreach($rkItems as $it){if(trim($it['kode_rekening']??'')===$d['kode_rekening']){$found=$it;break;}}}
if(!$found)$found=$rkItems[0];
$d['kode_rekening']=substr(trim($found['kode_rekening']??''),0,50);
$d['uraian']=substr(trim($found['uraian']??''),0,255);
$d['volume']=Security::vol($found['volume']??1);
$d['satuan']=substr(trim($found['satuan_text']??''),0,50);
$d['harga']=Security::money($found['harga_satuan']??0);
$d['jumlah']=round($d['volume']*$d['harga'],2);
if(!$d['uraian'])Security::json(['ok'=>false,'msg'=>'Uraian RKAM kosong. Perbaiki item RKAM dulu.']);
$tot=$pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM realisasi WHERE rkam_id=?".($id?" AND id<>$id":''));$tot->execute([$rkam_id]);$used=(float)$tot->fetchColumn();
if($used+$d['jumlah']>(float)$rk['total_anggaran']+0.01){$allow=($_POST['allow_over']??'')==='1';$reason=trim($_POST['over_reason']??'');
if(!$allow||!$reason)Security::json(['ok'=>false,'msg'=>'Realisasi melebihi anggaran sebesar Rp '.number_format($used+$d['jumlah']-$rk['total_anggaran'],0,',','.'),'over'=>true,'lebih'=>$used+$d['jumlah']-$rk['total_anggaran']]);
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission overbudget'],403);
$d['allow_overbudget']=1;$d['overbudget_reason']=$reason;}
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
$m=['nama_madrasah','nsm','npsn','alamat','desa','kecamatan','kabupaten','provinsi','kode_pos','email','telepon'];
$d=[];foreach($m as $k)$d[$k]=trim($_POST[$k]??'');
$kid=trim($_POST['kepala_guru_id']??'');$bid2=trim($_POST['bendahara_guru_id']??'');
$kid=$kid===''||$kid==='-'?null:(int)$kid;$bid2=$bid2===''||$bid2==='-'?null:(int)$bid2;
$knama='';$knip='';$bnama='';$bnip='';
if($kid){$s=$pdo->prepare("SELECT nama,nuptk FROM guru WHERE id=? AND status='aktif'");$s->execute([$kid]);$g=$s->fetch();if(!$g)Security::json(['ok'=>false,'msg'=>'Kepala Madrasah tidak valid']);$knama=$g['nama'];$knip=$g['nuptk']??'';}
if($bid2){$s=$pdo->prepare("SELECT nama,nuptk FROM guru WHERE id=? AND status='aktif'");$s->execute([$bid2]);$g=$s->fetch();if(!$g)Security::json(['ok'=>false,'msg'=>'Bendahara tidak valid']);$bnama=$g['nama'];$bnip=$g['nuptk']??'';}
$d['nama_kepala']=$knama;$d['nip_kepala']=$knip;$d['kepala_guru_id']=$kid;$d['nama_bendahara']=$bnama;$d['nip_bendahara']=$bnip;$d['bendahara_guru_id']=$bid2;
$pdo->prepare("UPDATE madrasah SET nama_madrasah=?,nsm=?,npsn=?,alamat=?,desa=?,kecamatan=?,kabupaten=?,provinsi=?,kode_pos=?,email=?,telepon=?,nama_kepala=?,nip_kepala=?,kepala_guru_id=?,nama_bendahara=?,nip_bendahara=?,bendahara_guru_id=? WHERE id=1")->execute(array_values($d));
foreach(['app_name','kode_madrasah','max_upload_mb'] as $k){if(isset($_POST[$k]))$pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES(?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$k,trim($_POST[$k])]);}
$logoFn=null;
if(!empty($_FILES['logo']['tmp_name'])&&is_uploaded_file($_FILES['logo']['tmp_name'])){
if($_FILES['logo']['size']>2*1024*1024)Security::json(['ok'=>false,'msg'=>'Logo > 2 MB']);
$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($_FILES['logo']['tmp_name']);
$okm=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
if(!isset($okm[$mime]))Security::json(['ok'=>false,'msg'=>'Logo harus JPG/PNG/WebP']);
if(!is_dir(LOGO_DIR))mkdir(LOGO_DIR,0755,true);
$logoFn='logo_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$okm[$mime];
if(!move_uploaded_file($_FILES['logo']['tmp_name'],LOGO_DIR.$logoFn))Security::json(['ok'=>false,'msg'=>'Upload logo gagal. Cek izin folder uploads/logo.']);
$old=$pdo->query("SELECT logo FROM madrasah WHERE id=1")->fetchColumn();
$pdo->prepare("UPDATE madrasah SET logo=? WHERE id=1")->execute([$logoFn]);
if($old&&is_file(LOGO_DIR.$old))@unlink(LOGO_DIR.$old);
Logger::log($pdo,'edit','logo',1,['logo'=>$old],['logo'=>$logoFn]);
}
Logger::log($pdo,'edit','pengaturan',1);Security::json(['ok'=>true,'logo'=>$logoFn]);
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
$allow=['users','madrasah','tahun_anggaran','bidang','satuan','sumber_dana','jenis_belanja','rekening','kegiatan','rkam','rkam_items','rkam_sumber_dana','rkam_status_history','realisasi','activity_logs','notifications','app_settings','login_attempts','guru','jabatan'];
foreach($stmts as $st){$st=trim($st);if($st===''||str_starts_with($st,'--'))continue;
if(preg_match('/^(INSERT\s+INTO|CREATE\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE|TRUNCATE\s+TABLE)\s+`?(\w+)/i',$st,$mm)){if(!in_array(strtolower($mm[2]),$allow))continue;}
else continue;
$pdo->exec($st);$ok++;}
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
}catch(Exception $ex){$pdo->exec("SET FOREIGN_KEY_CHECKS=1");error_log('restore: '.$ex->getMessage());Security::json(['ok'=>false,'msg'=>'Restore gagal: '.$ex->getMessage()]);}
Logger::log($pdo,'restore','database');Security::json(['ok'=>true,'statements'=>$ok]);
}
case 'master_opt': {
Security::json(['ok'=>true,'data'=>['rekening'=>$pdo->query("SELECT * FROM rekening WHERE status='aktif' LIMIT 200")->fetchAll(),'satuan'=>$pdo->query("SELECT * FROM satuan WHERE status='aktif'")->fetchAll(),'sumber'=>$pdo->query("SELECT * FROM sumber_dana WHERE status='aktif'")->fetchAll(),'kegiatan'=>$pdo->query("SELECT k.*,b.nama_bidang,s.nama_satuan AS satuan_nama,g.nama AS guru_nama FROM kegiatan k LEFT JOIN bidang b ON b.id=k.bidang_id LEFT JOIN satuan s ON s.id=k.satuan_id LEFT JOIN guru g ON g.id=k.guru_id WHERE k.status='aktif' ORDER BY k.nama_kegiatan LIMIT 200")->fetchAll()]]);
}
case 'kode_preview': {
if(!in_array(Auth::role(),['superadmin','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$t=$_POST['table']??'';$nama=trim($_POST['nama']??'');
if(!in_array($t,['bidang','sumber_dana','jenis_belanja','satuan','rekening','kegiatan','jabatan']))Security::json(['ok'=>false,'msg'=>'Invalid'],400);
Security::json(['ok'=>true,'kode'=>Helper::autoKode($pdo,$t,$nama)]);
}
case 'rkam_get': {
$id=(int)($_POST['id']??0);$s=$pdo->prepare("SELECT * FROM rkam WHERE id=?");$s->execute([$id]);$r=$s->fetch();if(!$r)Security::json(['ok'=>false,'msg'=>'Not found'],404);
$i=$pdo->prepare("SELECT * FROM rkam_items WHERE rkam_id=?");$i->execute([$id]);
$sm=$pdo->prepare("SELECT rs.*,s.nama_sumber_dana nama FROM rkam_sumber_dana rs JOIN sumber_dana s ON s.id=rs.sumber_dana_id WHERE rs.rkam_id=?");$sm->execute([$id]);
$r['items']=$i->fetchAll();$r['sumber']=$sm->fetchAll();Security::json(['ok'=>true,'data'=>$r]);
}
case 'rkam_items': {
$id=(int)($_POST['rkam_id']??0);
if(!$id)Security::json(['ok'=>false,'msg'=>'Kegiatan wajib dipilih'],400);
$i=$pdo->prepare("SELECT ri.id,ri.kode_rekening,ri.uraian,ri.volume,ri.satuan_text,ri.harga_satuan,r.nama_rekening FROM rkam_items ri LEFT JOIN rekening r ON r.kode=ri.kode_rekening WHERE ri.rkam_id=? ORDER BY ri.id");$i->execute([$id]);
Security::json(['ok'=>true,'data'=>$i->fetchAll()]);
}
case 'rkam_sumber': {
$id=(int)($_POST['rkam_id']??0);
if(!$id)Security::json(['ok'=>false,'msg'=>'Kegiatan wajib dipilih'],400);
$i=$pdo->prepare("SELECT rs.sumber_dana_id AS id,s.nama_sumber_dana,rs.jumlah FROM rkam_sumber_dana rs JOIN sumber_dana s ON s.id=rs.sumber_dana_id WHERE rs.rkam_id=? ORDER BY s.nama_sumber_dana");$i->execute([$id]);
Security::json(['ok'=>true,'data'=>$i->fetchAll()]);
}
case 'endpoint_save': {
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
foreach(['endpoint_simad_guru','endpoint_simad_key','api_secret_key'] as $k){
if(isset($_POST[$k]))$pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES(?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$k,trim($_POST[$k])]);
}
Logger::log($pdo,'edit','pengaturan_endpoint',1);Security::json(['ok'=>true,'msg'=>'Pengaturan Endpoint berhasil disimpan']);
}
case 'endpoint_gen_key': {
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$nk='rkam_sec_'.bin2hex(random_bytes(16));
$pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES('api_secret_key',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$nk]);
Logger::log($pdo,'edit','api_key',1);Security::json(['ok'=>true,'key'=>$nk,'msg'=>'API Key berhasil diperbarui']);
}
case 'sync_simad_guru': {
if(!in_array(Auth::role(),['superadmin','kepala_madrasah','bendahara','operator']))Security::json(['ok'=>false,'msg'=>'No permission'],403);
$url=Helper::setting($pdo,'endpoint_simad_guru');$key=Helper::setting($pdo,'endpoint_simad_key');
if(!$url)Security::json(['ok'=>false,'msg'=>'URL Endpoint SIMAD belum diisi']);
$opts=['http'=>['method'=>'GET','header'=>"User-Agent: RKAM-Sync/1.0\r\n".($key?"X-API-KEY: $key\r\nAuthorization: Bearer $key\r\n":''),'timeout'=>15]];
$json=@file_get_contents($url,false,stream_context_create($opts));
if($json===false)Security::json(['ok'=>false,'msg'=>'Gagal menghubungi Endpoint SIMAD. Cek URL & jaringan.']);
$res=json_decode($json,true);if(!$res)Security::json(['ok'=>false,'msg'=>'Respon SIMAD bukan JSON valid.']);
$teachers=[];
if(isset($res['data'])&&is_array($res['data']))$teachers=$res['data'];
elseif(isset($res['guru'])&&is_array($res['guru']))$teachers=$res['guru'];
elseif(isset($res['teachers'])&&is_array($res['teachers']))$teachers=$res['teachers'];
elseif(isset($res[0])&&is_array($res[0]))$teachers=$res;
if(!$teachers)Security::json(['ok'=>false,'msg'=>'Data guru dari SIMAD kosong.']);
$ins=0;$upd=0;$jabCache=[];
$getJab=function($nm) use ($pdo,&$jabCache){
$nm=trim((string)$nm);if(!$nm)return [null,null];$k=strtolower($nm);if(isset($jabCache[$k]))return $jabCache[$k];
$s=$pdo->prepare("SELECT id,nama FROM jabatan WHERE LOWER(nama)=? LIMIT 1");$s->execute([$k]);$r=$s->fetch();
if($r){$jabCache[$k]=[(int)$r['id'],$r['nama']];return $jabCache[$k];}
$kd=Helper::autoKode($pdo,'jabatan',$nm);
$pdo->prepare("INSERT INTO jabatan(kode,nama,status) VALUES(?,?,'aktif')")->execute([$kd,$nm]);
$id=(int)$pdo->lastInsertId();$jabCache[$k]=[$id,$nm];return $jabCache[$k];
};
foreach($teachers as $t){
$nama=trim($t['nama']??$t['nama_guru']??$t['name']??'');if(!$nama)continue;
$nuptk=trim($t['nuptk']??$t['nip']??'');if(!$nuptk)$nuptk=null;
$kode=trim($t['kode']??'');if(!$kode)$kode=null;
$jabRaw=trim($t['jabatan']??$t['nama_jabatan']??'');[$jid,$jnm]=$getJab($jabRaw);
$st=strtolower(trim($t['status']??'aktif'));if(!in_array($st,['aktif','nonaktif']))$st='aktif';
$ex=null;
if($nuptk){$s=$pdo->prepare("SELECT id FROM guru WHERE nuptk=?");$s->execute([$nuptk]);$ex=$s->fetchColumn();}
if(!$ex&&$kode){$s=$pdo->prepare("SELECT id FROM guru WHERE kode=?");$s->execute([$kode]);$ex=$s->fetchColumn();}
if(!$ex){$s=$pdo->prepare("SELECT id FROM guru WHERE LOWER(nama)=?");$s->execute([strtolower($nama)]);$ex=$s->fetchColumn();}
if($ex){
$pdo->prepare("UPDATE guru SET nama=?,nuptk=COALESCE(?,nuptk),jabatan_id=COALESCE(?,jabatan_id),jabatan=COALESCE(?,jabatan),status=? WHERE id=?")->execute([$nama,$nuptk,$jid,$jnm,$st,$ex]);
$upd++;
}else{
if(!$kode)$kode=Helper::autoKode($pdo,'guru',$nama);
$pdo->prepare("INSERT INTO guru(kode,nama,nuptk,jabatan_id,jabatan,status) VALUES(?,?,?,?,?,?)")->execute([$kode,$nama,$nuptk,$jid,$jnm?:'Guru',$st]);
$ins++;
}
}
$at=date('Y-m-d H:i:s');
$pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES('last_sync_simad',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$at]);
Logger::log($pdo,'impor','guru',null,null,['source'=>'SIMAD','inserted'=>$ins,'updated'=>$upd]);
Security::json(['ok'=>true,'msg'=>"Sinkronisasi SIMAD berhasil: $ins data ditambahkan, $upd data diperbarui.",'inserted'=>$ins,'updated'=>$upd,'sync_at'=>$at]);
}
case 'sys_update': {
if(Auth::role()!=='superadmin')Security::json(['ok'=>false,'msg'=>'No permission'],403);
@set_time_limit(120);
$clean=function($s){$s=(string)($s??'');$s=str_ireplace(['github.com','github','dewecorp/rkam','dewecorp'],'server pusat',$s);return trim(mb_substr($s,0,800));};
$root=realpath(__DIR__.'/../..');if(!$root||!is_dir($root.'/.git'))Security::json(['ok'=>false,'msg'=>'Folder sistem bukan salinan versi resmi']);
$git='git -C '.escapeshellarg($root).' ';
$run=function($args) use ($git){$out=@shell_exec($git.$args.' 2>&1');return trim((string)($out??''));};
$remote=$run('remote get-url origin');
$okRemote=in_array(strtolower(trim($remote)),['https://github.com/dewecorp/rkam.git','https://github.com/dewecorp/rkam','git@github.com:dewecorp/rkam.git'],true);
if(!$okRemote)Security::json(['ok'=>false,'msg'=>'Sumber pembaruan tidak valid. URL remote diubah, pembaruan dibatalkan demi keamanan.']);
$branch=trim($run('rev-parse --abbrev-ref HEAD'));
if($branch!=='main')Security::json(['ok'=>false,'msg'=>'Cabang aktif bukan versi resmi (main). Pembaruan dibatalkan.']);
$protected=['config/database.php','uploads/bukti/.htaccess','uploads/logo/.htaccess'];
$saved=[];foreach($protected as $rel){$p=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$rel);if(is_file($p))$saved[$rel]=file_get_contents($p);}
$porcelain=$run('status --porcelain');
$dirtyOther=[];if($porcelain!==''){foreach(explode("\n",$porcelain) as $ln){$ln=rtrim($ln);if($ln==='')continue;$path=trim(substr($ln,3));$path=str_replace('\\','/',$path);if(!in_array($path,$protected,true)&&strpos($path,'sys_backups/')!==0)$dirtyOther[]=$path;}}
if(count($dirtyOther)>8)Security::json(['ok'=>false,'msg'=>'Ada '.count($dirtyOther).' berkas lokal yang belum disimpan. Simpan dulu sebelum perbarui.','details'=>array_slice($dirtyOther,0,10)]);
if($dirtyOther)Security::json(['ok'=>false,'msg'=>'Ada perubahan lokal ('.implode(', ',array_slice($dirtyOther,0,5)).'). Simpan dulu sebelum perbarui.','details'=>array_slice($dirtyOther,0,10)]);
$before=trim($run('rev-parse HEAD'));
$fetch=$run('fetch --prune origin main');
$afterRemote=trim($run('rev-parse origin/main'));
if(!preg_match('/^[0-9a-f]{40}$/',$afterRemote))Security::json(['ok'=>false,'msg'=>'Gagal mengambil versi terbaru. Coba lagi nanti.','details'=>[$clean($fetch)]]);
if($afterRemote===$before){$curVer=Helper::setting($pdo,'sys_version',APP_VERSION);if(!preg_match('/^\d+\.\d+\.\d+$/',$curVer))$curVer=APP_VERSION;Logger::log($pdo,'update','sistem',null,['from'=>substr($before,0,7)],['to'=>$curVer,'files'=>0,'note'=>'sudah terbaru']);Security::json(['ok'=>true,'msg'=>'Sudah versi terbaru','before'=>substr($before,0,7),'after'=>substr($afterRemote,0,7),'files'=>0,'details'=>[],'version'=>$curVer]);}
$diffList=$run('diff --name-only '.escapeshellarg($before).' '.escapeshellarg($afterRemote));
$files=$diffList==='' ? [] : explode("\n",$diffList);
$files=array_values(array_filter(array_map('trim',$files)));
$badExt=['.exe','.bat','.cmd','.ps1','.sh','.dll','.so','.dylib','.bin','.msi'];
foreach($files as $f){$fl=strtolower($f);foreach($badExt as $e){if(substr($fl,-strlen($e))===$e)Security::json(['ok'=>false,'msg'=>'Paket pembaruan ditolak: berisi berkas terlarang ('.$e.').']);}if(strpos($fl,'..')!==false||$fl[0]==='/')Security::json(['ok'=>false,'msg'=>'Paket pembaruan ditolak: jalur berkas tidak valid.']);}
foreach($protected as $rel){$run('checkout -- '.escapeshellarg($rel));}
$bkDir=$root.DIRECTORY_SEPARATOR.'sys_backups'.DIRECTORY_SEPARATOR.date('Ymd_His');
@mkdir($bkDir,0755,true);
@file_put_contents($bkDir.DIRECTORY_SEPARATOR.'version.txt',"before: $before\nafter: $afterRemote\ndate: ".date('Y-m-d H:i:s')."\n");
foreach($saved as $rel=>$content){$bp=$bkDir.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$rel);@mkdir(dirname($bp),0755,true);@file_put_contents($bp,$content);}
$merge=$run('merge --ff-only origin/main');
$headNow=trim($run('rev-parse HEAD'));
if($headNow!==$afterRemote)Security::json(['ok'=>false,'msg'=>'Pembaruan dibatalkan: riwayat versi bercabang. Hubungi pengembang.','details'=>[$clean($merge)]]);
foreach($saved as $rel=>$content){$p=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$rel);@mkdir(dirname($p),0755,true);@file_put_contents($p,$content);}
$changed=$run('diff --name-only '.escapeshellarg($before).' '.escapeshellarg($afterRemote));
$changedFiles=$changed==='' ? [] : array_values(array_filter(array_map('trim',explode("\n",$changed))));
$phpFiles=array_values(array_filter($changedFiles,fn($f)=>substr(strtolower($f),-4)==='.php'));
$phpBin=(PHP_BINARY&&is_file(PHP_BINARY))?PHP_BINARY:'php';
$badPhp=[];
foreach(array_slice($phpFiles,0,40) as $f){$p=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$f);if(!is_file($p))continue;$chk=@shell_exec(escapeshellarg($phpBin).' -l '.escapeshellarg($p).' 2>&1');if($chk&&stripos($chk,'no syntax errors')===false)$badPhp[]=$f;}
if($badPhp){$run('reset --hard '.escapeshellarg($before));foreach($saved as $rel=>$content){$p=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$rel);@file_put_contents($p,$content);}Security::json(['ok'=>false,'msg'=>'Paket pembaruan ditolak: verifikasi kode gagal. Sistem dikembalikan.','details'=>array_slice($badPhp,0,10)]);}
if(function_exists('opcache_reset')){@opcache_reset();}
$cur=Helper::setting($pdo,'sys_version',APP_VERSION);if(!preg_match('/^\d+\.\d+\.\d+$/',$cur))$cur=APP_VERSION;$p=array_map('intval',explode('.',$cur));$p[2]++;$newVer=$p[0].'.'.$p[1].'.'.$p[2];
try{$pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES('sys_version',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$newVer]);}catch(Exception $e){error_log('sys_version: '.$e->getMessage());}
Logger::log($pdo,'update','sistem',null,['from'=>$cur],['to'=>$newVer,'files'=>count($changedFiles),'commit'=>substr($afterRemote,0,7)]);
Security::json(['ok'=>true,'msg'=>'OK','before'=>substr($before,0,7),'after'=>substr($afterRemote,0,7),'files'=>count($changedFiles),'details'=>array_slice($changedFiles,0,30),'backup'=>basename($bkDir),'version'=>$newVer]);
}
default: Security::json(['ok'=>false,'msg'=>'Unknown act'],400);
}
