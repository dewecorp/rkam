<?php
$m=Helper::madrasah($pdo);
$app=Helper::setting($pdo,'app_name','SIRKAM');$km=Helper::setting($pdo,'kode_madrasah','MI-SF');
$guru=$pdo->query("SELECT g.id,g.nama,g.nuptk,j.nama AS jabatan_nama FROM guru g LEFT JOIN jabatan j ON j.id=g.jabatan_id WHERE g.status='aktif' ORDER BY g.nama")->fetchAll();
function lab($t,$n,$v,$ph=''){return '<div><label class="text-xs font-bold text-emerald-900">'.$t.'</label><input name="'.$n.'" value="'.Security::e($v??'').'" placeholder="'.$ph.'" class="w-full border rounded p-2 mt-1"></div>';}
function guruSel($t,$n,$v,$guru){$h='<div><label class="text-xs font-bold text-emerald-900">'.$t.'</label><select name="'.$n.'" onchange="syncGuru(this)" data-sync="'.$n.'" class="w-full border rounded p-2 mt-1"><option value="">- Pilih Guru -</option>';foreach($guru as $g){$sel=((string)($v??'')!=='')&&((string)$v===(string)$g['id'])?' selected':'';$lbl=$g['nama'].($g['jabatan_nama']?' ('.$g['jabatan_nama'].')':'');$h.='<option value="'.$g['id'].'" data-nama="'.Security::e($g['nama']).'" data-nip="'.Security::e($g['nuptk']??'').'"'.$sel.'>'.Security::e($lbl).'</option>';}return $h.'</select></div>';}
?>
<div class="grid lg:grid-cols-2 gap-3">
<div class="bg-white p-4 rounded shadow text-sm"><b>Identitas Madrasah</b>
<form onsubmit="saveSet(event)" class="grid gap-2 mt-2">
<?=lab('Nama Madrasah','nama_madrasah',$m['nama_madrasah']??'','MI Salafiyah')?>
<div class="grid grid-cols-2 gap-2"><?=lab('NSM','nsm',$m['nsm']??'','111234560001')?><?=lab('NPSN','npsn',$m['npsn']??'','60700001')?></div>
<div><label class="text-xs font-bold text-emerald-900">Alamat</label><textarea name="alamat" placeholder="Jl. Pendidikan No. 10" class="w-full border rounded p-2 mt-1"><?=Security::e($m['alamat']??'')?></textarea></div>
<div class="grid grid-cols-2 gap-2"><?=lab('Desa','desa',$m['desa']??'')?><?=lab('Kecamatan','kecamatan',$m['kecamatan']??'')?><?=lab('Kabupaten','kabupaten',$m['kabupaten']??'')?><?=lab('Provinsi','provinsi',$m['provinsi']??'')?></div>
<div class="grid grid-cols-2 gap-2"><?=lab('Kode Pos','kode_pos',$m['kode_pos']??'','44191')?><?=lab('Telepon','telepon',$m['telepon']??'')?><?=lab('Email','email',$m['email']??'')?><?=lab('Nama Aplikasi','app_name',$app)?></div>
<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3"><div class="font-bold text-emerald-900 text-[13px]">Pimpinan (Sinkron Data Guru)</div><div class="text-[11px] text-gray-500 mb-2">Pilih guru — nama + NIP/NUPTK terisi otomatis dari Master Guru. Tersimpan sebagai relasi, ikut berubah bila data guru diubah.</div>
<div class="grid grid-cols-2 gap-2"><?=guruSel('Kepala Madrasah','kepala_guru_id',$m['kepala_guru_id']??'',$guru)?><?=guruSel('Bendahara','bendahara_guru_id',$m['bendahara_guru_id']??'',$guru)?></div>
<div class="grid grid-cols-2 gap-2 mt-2">
<div><label class="text-xs font-bold text-emerald-900">Nama Kepala (Otomatis)</label><input name="nama_kepala" id="nama_kepala" readonly value="<?=Security::e($m['nama_kepala']??'')?>" class="w-full border rounded p-2 mt-1 bg-gray-50"></div>
<div><label class="text-xs font-bold text-emerald-900">NIP/NUPTK Kepala (Otomatis)</label><input name="nip_kepala" id="nip_kepala" readonly value="<?=Security::e($m['nip_kepala']??'')?>" class="w-full border rounded p-2 mt-1 bg-gray-50"></div>
<div><label class="text-xs font-bold text-emerald-900">Nama Bendahara (Otomatis)</label><input name="nama_bendahara" id="nama_bendahara" readonly value="<?=Security::e($m['nama_bendahara']??'')?>" class="w-full border rounded p-2 mt-1 bg-gray-50"></div>
<div><label class="text-xs font-bold text-emerald-900">NIP/NUPTK Bendahara (Otomatis)</label><input name="nip_bendahara" id="nip_bendahara" readonly value="<?=Security::e($m['nip_bendahara']??'')?>" class="w-full border rounded p-2 mt-1 bg-gray-50"></div>
</div></div>
<div class="grid grid-cols-2 gap-2"><?=lab('Kode Madrasah','kode_madrasah',$km,'MI-SF')?></div>
<div><label class="text-xs font-bold text-emerald-900">Logo Sekolah (JPG/PNG/WebP, max 2 MB)</label>
<div class="flex items-center gap-3 mt-1">
<?php $logoOk=!empty($m['logo'])&&is_file(__DIR__.'/../../uploads/logo/'.$m['logo']); $logoV=$logoOk?filemtime(__DIR__.'/../../uploads/logo/'.$m['logo']):0; ?>
<div id="logoBox"><?php if($logoOk):?><img id="logoPrev" src="<?=BASE_URL?>uploads/logo/<?=Security::e($m['logo'])?>?v=<?=$logoV?>" class="w-16 h-16 rounded-2xl object-contain bg-emerald-50 border border-emerald-200 p-1"><?php else:?><div id="logoPrev" class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-400 text-2xl"><i class="fa-solid fa-image"></i></div><?php endif;?></div>
<div class="flex-1"><input id="logoFile" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" onchange="previewLogo(this)" class="w-full border rounded p-2">
<div class="flex gap-2 mt-2"><span class="text-[11px] text-gray-500">Pilih file lalu klik Simpan. Preview langsung tampil.</span>
<?php if(!empty($m['logo'])):?><button type="button" onclick="delLogo()" class="bg-red-100 text-red-700 border border-red-200 rounded px-3 py-1.5 text-xs font-bold ml-auto"><i class="fa-solid fa-trash mr-1"></i> Hapus</button><?php endif;?></div></div></div></div>
<button class="bg-emerald-700 text-white rounded p-2 font-bold">Simpan</button></form></div>
<div class="bg-white p-4 rounded shadow text-sm"><b>Ganti Password</b>
<form onsubmit="chPass(event)" class="grid gap-2 mt-2"><div><label class="text-xs font-bold text-emerald-900">Password Lama</label><input type="password" name="old" required placeholder="••••••" class="w-full border rounded p-2 mt-1"></div><div><label class="text-xs font-bold text-emerald-900">Password Baru (min 6)</label><input type="password" name="new" required minlength="6" placeholder="••••••" class="w-full border rounded p-2 mt-1"></div><button class="bg-slate-700 text-white rounded p-2 font-bold">Ganti</button></form></div>
<div class="bg-white p-4 rounded shadow text-sm lg:col-span-2"><b>Petunjuk Penggunaan Aplikasi (Urutan Lengkap)</b>
<div class="mt-2 grid md:grid-cols-2 gap-2 text-[13px]">
<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3"><b>0 — Siapkan Pondasi</b><div class="text-gray-600">Pengaturan: identitas + logo. Master: 1 Tahun aktif, Bidang, Sumber Dana, Satuan, Rekening, Kegiatan (kode auto). Login sesuai peran.</div></div>
<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3"><b>1 — Susun RKAM</b><div class="text-gray-600">1 Tahun → 2 Kegiatan Master (data ikut otomatis) → 3 Item (volume bulat × harga) → 4 Sumber wajib balance (Samakan) → Simpan Draft. 1 kegiatan 1 tahun tidak ganda.</div></div>
<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3"><b>2 — Verifikasi</b><div class="text-gray-600">Ajukan → Kepala Verifikasi → Setujui → Kunci. Tolak/Revisi wajib alasan; banner merah jelas di detail; Ajukan Ulang kirim notif ke kepala.</div></div>
<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3"><b>3 — Realisasi (Setelah Disetujui)</b><div class="text-gray-600">Wajib SETELAH RKAM Disetujui (draft/diajukan/ditolak ditolak server). 1 Kegiatan RKAM → 2 Tanggal → 3 Uraian+Volume+Harga → 4 Bukti JPG/PNG/PDF. Nomor bukti & rekening otomatis ikut kegiatan — tidak ketik bebas. Sisa terlihat di modal; overbudget wajib izin + alasan.</div></div>
<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3"><b>4 — Pantau & Lapor</b><div class="text-gray-600">Monitoring: sisa, %, bar 80/90/100%. Laporan 6 anak: cetak kop+logo+TTD, export CSV. Dashboard: kartu + grafik + timeline aktivitas.</div></div>
<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3"><b>5 — Admin</b><div class="text-gray-600">User: superadmin kelola peran. Backup: konfirmasi → progress → hasil; tabel Unduh/Hapus; restore allowlist. Notif bell: klik → detail; 24 jam auto-hapus. Audit ikut timeline dashboard.</div></div></div></div></div>
<script>
function syncGuru(sel){const o=sel.options[sel.selectedIndex];const nm=o?o.dataset.nama||'':'';const nip=o?o.dataset.nip||'':'';
if(sel.name==='kepala_guru_id'){document.getElementById('nama_kepala').value=nm;document.getElementById('nip_kepala').value=nip;}
if(sel.name==='bendahara_guru_id'){document.getElementById('nama_bendahara').value=nm;document.getElementById('nip_bendahara').value=nip;}}
function previewLogo(inp){if(!inp.files||!inp.files[0])return;const f=inp.files[0];if(f.size>2*1024*1024){err('Logo > 2 MB');inp.value='';return;}const box=document.getElementById('logoBox');if(!box)return;const url=URL.createObjectURL(f);box.innerHTML=`<img id="logoPrev" src="${url}" class="w-16 h-16 rounded-2xl object-contain bg-emerald-50 border border-emerald-200 p-1">`;}
async function saveSet(e){e.preventDefault();const f=new FormData(e.target);f.append('csrf_token',window.__CSRF||CSRF);f.append('act','setting_save');Swal.fire({title:'Menyimpan...',didOpen:()=>Swal.showLoading(),allowOutsideClick:false});try{const r=await fetch((window.__BASE||BASE)+'api/x',{method:'POST',body:f});const j=await r.json();Swal.close();if(j.ok){ok(j.logo?'Tersimpan + logo terupload':'Tersimpan');setTimeout(()=>location.reload(),900);}else err(j.msg||'Gagal');}catch(ex){Swal.close();err('Gagal: '+ex.message);}}
async function chPass(e){e.preventDefault();const f=new FormData(e.target);const j=await api('x',{act:'change_pass',old:f.get('old'),new:f.get('new')});if(j.ok){ok('Password diganti');e.target.reset();}else err(j.msg);}
async function delLogo(){if(!await ask('Hapus logo sekolah?','','Ya, Hapus'))return;const j=await api('x',{act:'logo_delete'});if(j.ok){ok('Logo dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg||'Gagal');}
</script>
