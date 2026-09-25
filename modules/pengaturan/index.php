<?php
$m=Helper::madrasah($pdo);
$app=Helper::setting($pdo,'app_name','SIRKAM');$km=Helper::setting($pdo,'kode_madrasah','MI-SF');
$guru=$pdo->query("SELECT g.id,g.nama,g.nuptk,j.nama AS jabatan_nama FROM guru g LEFT JOIN jabatan j ON j.id=g.jabatan_id WHERE g.status='aktif' ORDER BY g.nama")->fetchAll();

$secKey=Helper::setting($pdo,'api_secret_key');
if(!$secKey){
    $secKey='rkam_sec_'.bin2hex(random_bytes(16));
    $pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES('api_secret_key',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$secKey]);
}

$simadUrl=Helper::setting($pdo,'endpoint_simad_guru');
$simadKey=Helper::setting($pdo,'endpoint_simad_key');
$lastSync=Helper::setting($pdo,'last_sync_simad','Belum pernah');

$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
$host=$_SERVER['HTTP_HOST']??'localhost';
$dynBase=$scheme.'://'.$host.BASE_URL;

$tab=$_GET['tab']??'identitas';

function lab($t,$n,$v,$ph=''){return '<div><label class="text-xs font-bold text-emerald-900">'.$t.'</label><input name="'.$n.'" value="'.Security::e($v??'').'" placeholder="'.$ph.'" class="w-full border rounded p-2 mt-1"></div>';}
function guruSel($t,$n,$v,$guru){$h='<div><label class="text-xs font-bold text-emerald-900">'.$t.'</label><select name="'.$n.'" onchange="syncGuru(this)" data-sync="'.$n.'" class="w-full border rounded p-2 mt-1"><option value="">- Pilih Guru -</option>';foreach($guru as $g){$sel=((string)($v??'')!=='')&&((string)$v===(string)$g['id'])?' selected':'';$lbl=$g['nama'].($g['jabatan_nama']?' ('.$g['jabatan_nama'].')':'');$h.='<option value="'.$g['id'].'" data-nama="'.Security::e($g['nama']).'" data-nip="'.Security::e($g['nuptk']??'').'"'.$sel.'>'.Security::e($lbl).'</option>';}return $h.'</select></div>';}
?>

<div class="mb-4 border-b border-emerald-200 flex gap-2">
  <button type="button" onclick="switchTab('identitas')" id="tabBtnIdentitas" class="px-4 py-2 font-bold text-sm border-b-2 transition-all <?=($tab!=='endpoint'?'border-emerald-600 text-emerald-800 bg-white rounded-t-xl':'border-transparent text-gray-500 hover:text-emerald-700')?>"><i class="fa-solid fa-sliders mr-1"></i> Identitas & Password</button>
  <button type="button" onclick="switchTab('endpoint')" id="tabBtnEndpoint" class="px-4 py-2 font-bold text-sm border-b-2 transition-all <?=($tab==='endpoint'?'border-emerald-600 text-emerald-800 bg-white rounded-t-xl':'border-transparent text-gray-500 hover:text-emerald-700')?>"><i class="fa-solid fa-network-wired mr-1"></i> Pengaturan Endpoint (Integrasi Web)</button>
</div>

<!-- TAB 1: IDENTITAS & PASSWORD -->
<div id="tabIdentitas" class="<?=($tab==='endpoint'?'hidden':'')?> grid lg:grid-cols-2 gap-3">
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
<button class="bg-emerald-700 text-white rounded p-2 font-bold">Simpan Identitas</button></form></div>
<div class="bg-white p-4 rounded shadow text-sm"><b>Ganti Password</b>
<form onsubmit="chPass(event)" class="grid gap-2 mt-2"><div><label class="text-xs font-bold text-emerald-900">Password Lama</label><input type="password" name="old" required placeholder="••••••" class="w-full border rounded p-2 mt-1"></div><div><label class="text-xs font-bold text-emerald-900">Password Baru (min 6)</label><input type="password" name="new" required minlength="6" placeholder="••••••" class="w-full border rounded p-2 mt-1"></div><button class="bg-slate-700 text-white rounded p-2 font-bold">Ganti Password</button></form>
<div class="mt-4 pt-4 border-t border-emerald-100">
  <b>Petunjuk Penggunaan Aplikasi</b>
  <div class="mt-2 grid gap-2 text-[12px]">
    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-2.5"><b>0 — Siapkan Pondasi</b><div class="text-gray-600">Pengaturan identitas & Master Data.</div></div>
    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-2.5"><b>1 — Susun RKAM</b><div class="text-gray-600">Pilih tahun, kegiatan, item & sumber dana.</div></div>
    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-2.5"><b>2 — Verifikasi & Realisasi</b><div class="text-gray-600">Disetujui kepala madrasah → catat realisasi.</div></div>
  </div>
</div>
</div>
</div>

<!-- TAB 2: INTEGRASI & ENDPOINT -->
<div id="tabEndpoint" class="<?=($tab!=='endpoint'?'hidden':'')?> space-y-4">
  <div class="bg-white p-5 rounded-2xl shadow text-sm">
    <div class="flex items-center justify-between border-b border-emerald-100 pb-3 mb-4">
      <div>
        <b class="text-base text-emerald-900"><i class="fa-solid fa-cloud-arrow-up text-emerald-600 mr-1.5"></i> Endpoint Keluar (Export Data dari RKAM)</b>
        <p class="text-xs text-gray-500">Salin endpoint di bawah ini untuk digunakan pada aplikasi web lain (SIMAD/Portal). URL terdeteksi otomatis sesuai domain aktif.</p>
      </div>
      <button type="button" onclick="genApiKey()" class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs px-3 py-2 rounded-xl flex items-center gap-1.5 shadow-sm"><i class="fa-solid fa-key"></i> Generate Key Baru</button>
    </div>

    <div class="bg-emerald-50/60 border border-emerald-200 rounded-xl p-3.5 mb-4">
      <label class="text-xs font-bold text-emerald-900">API Secret Key (Authentication Token)</label>
      <div class="flex gap-2 mt-1">
        <input type="text" id="apiSecretKey" readonly value="<?=Security::e($secKey)?>" class="w-full border rounded p-2 bg-white font-mono text-xs font-bold text-slate-700">
        <button type="button" onclick="copyTxt(document.getElementById('apiSecretKey').value, 'API Key')" class="bg-emerald-700 text-white font-bold text-xs px-4 rounded-xl flex items-center gap-1 shrink-0"><i class="fa-solid fa-copy"></i> Salin Key</button>
      </div>
    </div>

    <?php
    $endpoints = [
      [
        'title' => 'Endpoint Data Guru (Keluar)',
        'desc' => 'Menyediakan daftar data guru dari RKAM ke aplikasi luar.',
        'url' => $dynBase . 'api/v1/export/guru?api_key=' . $secKey,
        'icon' => 'fa-chalkboard-user'
      ],
      [
        'title' => 'Endpoint Data RKAM (Keluar)',
        'desc' => 'Menyediakan rincian anggaran & item RKAM tahun aktif ke aplikasi luar.',
        'url' => $dynBase . 'api/v1/export/rkam?api_key=' . $secKey,
        'icon' => 'fa-clipboard-list'
      ],
      [
        'title' => 'Endpoint Data Realisasi (Keluar)',
        'desc' => 'Menyediakan data transaksi realisasi belanja ke aplikasi luar.',
        'url' => $dynBase . 'api/v1/export/realisasi?api_key=' . $secKey,
        'icon' => 'fa-money-bill-wave'
      ],
      [
        'title' => 'Endpoint Identitas Madrasah (Keluar)',
        'desc' => 'Menyediakan profil & identitas madrasah ke aplikasi luar.',
        'url' => $dynBase . 'api/v1/export/madrasah?api_key=' . $secKey,
        'icon' => 'fa-building-columns'
      ],
    ];
    ?>

    <div class="grid md:grid-cols-2 gap-3">
      <?php foreach($endpoints as $ep): ?>
      <div class="border border-emerald-100 bg-white rounded-xl p-3.5 shadow-sm">
        <div class="font-bold text-emerald-900 flex items-center gap-2 text-[13px]"><i class="fa-solid <?=$ep['icon']?> text-emerald-600"></i> <?=$ep['title']?></div>
        <div class="text-[11px] text-gray-500 mb-2"><?=$ep['desc']?></div>
        <div class="flex gap-2">
          <input type="text" readonly value="<?=Security::e($ep['url'])?>" class="w-full border rounded px-2 py-1 bg-gray-50 text-[11px] font-mono select-all">
          <button type="button" onclick="copyTxt('<?=Security::e($ep['url'])?>', '<?=$ep['title']?>')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-bold text-xs px-2.5 rounded-lg flex items-center gap-1 shrink-0" title="Salin Endpoint"><i class="fa-solid fa-copy"></i> Salin</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl shadow text-sm">
    <div class="border-b border-emerald-100 pb-3 mb-4">
      <b class="text-base text-emerald-900"><i class="fa-solid fa-cloud-arrow-down text-blue-600 mr-1.5"></i> Endpoint Masuk (Tarik Data dari SIMAD / Web Lain)</b>
      <p class="text-xs text-gray-500">Masukkan endpoint dari aplikasi SIMAD / web luar untuk mengambil dan mencatat data guru ke RKAM secara otomatis tanpa perlu input manual.</p>
    </div>

    <form onsubmit="saveEndpoint(event)" class="grid gap-3">
      <div>
        <label class="text-xs font-bold text-emerald-900">URL Endpoint Masuk (SIMAD Data Guru)</label>
        <input type="url" name="endpoint_simad_guru" value="<?=Security::e($simadUrl)?>" placeholder="https://simad.sekolah.sch.id/api/v1/export/guru" class="w-full border rounded p-2.5 mt-1 text-xs font-mono">
        <span class="text-[11px] text-gray-500">Format respon JSON yang didukung: array langsung `[{...}]` atau terbungkus `{"data": [...]}` / `{"guru": [...]}`.</span>
      </div>

      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-bold text-emerald-900">Secret Key / Token API SIMAD (Opsional)</label>
          <input type="text" name="endpoint_simad_key" value="<?=Security::e($simadKey)?>" placeholder="rkam_sec_..." class="w-full border rounded p-2.5 mt-1 text-xs font-mono">
        </div>
        <div>
          <label class="text-xs font-bold text-emerald-900">Status Sinkronisasi Terakhir</label>
          <input type="text" readonly value="<?=Security::e($lastSync)?>" class="w-full border rounded p-2.5 mt-1 text-xs bg-gray-50 font-bold text-emerald-800">
        </div>
      </div>

      <div class="flex flex-wrap gap-2 pt-2">
        <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs px-5 py-2.5 rounded-xl flex items-center gap-1.5 shadow-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan Endpoint</button>
        <button type="button" onclick="runSyncSimad()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl flex items-center gap-1.5 shadow-sm ml-auto"><i class="fa-solid fa-rotate text-xs"></i> Sinkronkan Data Guru Dari SIMAD</button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(t){
  const tId=document.getElementById('tabIdentitas'), tEp=document.getElementById('tabEndpoint');
  const bId=document.getElementById('tabBtnIdentitas'), bEp=document.getElementById('tabBtnEndpoint');
  if(t==='endpoint'){
    tId.classList.add('hidden'); tEp.classList.remove('hidden');
    bEp.className='px-4 py-2 font-bold text-sm border-b-2 transition-all border-emerald-600 text-emerald-800 bg-white rounded-t-xl';
    bId.className='px-4 py-2 font-bold text-sm border-b-2 transition-all border-transparent text-gray-500 hover:text-emerald-700';
  }else{
    tEp.classList.add('hidden'); tId.classList.remove('hidden');
    bId.className='px-4 py-2 font-bold text-sm border-b-2 transition-all border-emerald-600 text-emerald-800 bg-white rounded-t-xl';
    bEp.className='px-4 py-2 font-bold text-sm border-b-2 transition-all border-transparent text-gray-500 hover:text-emerald-700';
  }
}

function copyTxt(txt, label){
  if(!txt){ err('Teks kosong'); return; }
  navigator.clipboard.writeText(txt).then(()=>{
    ok((label||'Endpoint')+' berhasil disalin!');
  }).catch(()=>{
    const inp=document.createElement('input'); inp.value=txt; document.body.appendChild(inp); inp.select();
    document.execCommand('copy'); document.body.removeChild(inp);
    ok((label||'Endpoint')+' berhasil disalin!');
  });
}

function syncGuru(sel){const o=sel.options[sel.selectedIndex];const nm=o?o.dataset.nama||'':'';const nip=o?o.dataset.nip||'':'';
if(sel.name==='kepala_guru_id'){document.getElementById('nama_kepala').value=nm;document.getElementById('nip_kepala').value=nip;}
if(sel.name==='bendahara_guru_id'){document.getElementById('nama_bendahara').value=nm;document.getElementById('nip_bendahara').value=nip;}}

function previewLogo(inp){if(!inp.files||!inp.files[0])return;const f=inp.files[0];if(f.size>2*1024*1024){err('Logo > 2 MB');inp.value='';return;}const box=document.getElementById('logoBox');if(!box)return;const url=URL.createObjectURL(f);box.innerHTML=`<img id="logoPrev" src="${url}" class="w-16 h-16 rounded-2xl object-contain bg-emerald-50 border border-emerald-200 p-1">`;}

async function saveSet(e){e.preventDefault();const f=new FormData(e.target);f.append('csrf_token',window.__CSRF||CSRF);f.append('act','setting_save');Swal.fire({title:'Menyimpan...',didOpen:()=>Swal.showLoading(),allowOutsideClick:false});try{const r=await fetch((window.__BASE||BASE)+'api/x',{method:'POST',body:f});const j=await r.json();Swal.close();if(j.ok){ok(j.logo?'Tersimpan + logo terupload':'Tersimpan');setTimeout(()=>location.reload(),900);}else err(j.msg||'Gagal');}catch(ex){Swal.close();err('Gagal: '+ex.message);}}

async function chPass(e){e.preventDefault();const f=new FormData(e.target);const j=await api('x',{act:'change_pass',old:f.get('old'),new:f.get('new')});if(j.ok){ok('Password diganti');e.target.reset();}else err(j.msg);}

async function delLogo(){if(!await ask('Hapus logo sekolah?','','Ya, Hapus'))return;const j=await api('x',{act:'logo_delete'});if(j.ok){ok('Logo dihapus');setTimeout(()=>location.reload(),800);}else err(j.msg||'Gagal');}

async function saveEndpoint(e){
  e.preventDefault();
  const f=new FormData(e.target);
  f.append('act','endpoint_save');
  f.append('csrf_token',window.__CSRF||CSRF);
  Swal.fire({title:'Menyimpan...',didOpen:()=>Swal.showLoading(),allowOutsideClick:false});
  try{
    const r=await fetch((window.__BASE||BASE)+'api/x',{method:'POST',body:f});
    const j=await r.json(); Swal.close();
    if(j.ok){ ok('Pengaturan Endpoint tersimpan'); }
    else err(j.msg||'Gagal menyimpan');
  }catch(ex){ Swal.close(); err('Terjadi kesalahan: '+ex.message); }
}

async function genApiKey(){
  if(!await ask('Generate API Secret Key baru?','Key lama akan tidak berlaku lagi untuk sistem pengakses.','Ya, Generate')) return;
  const j=await api('x',{act:'endpoint_gen_key'});
  if(j.ok){
    document.getElementById('apiSecretKey').value=j.key;
    ok('API Key berhasil diperbarui');
    setTimeout(()=>location.reload(),900);
  }else err(j.msg);
}

async function runSyncSimad(){
  Swal.fire({
    title:'Menghubungi SIMAD...',
    text:'Mengambil data guru dari Endpoint SIMAD.',
    didOpen:()=>Swal.showLoading(),
    allowOutsideClick:false
  });
  try{
    const j=await api('x',{act:'sync_simad_guru'});
    Swal.close();
    if(j.ok){
      Swal.fire({
        icon:'success',
        title:'Sinkronisasi Berhasil',
        html:`<b>${j.msg}</b><br><span class="text-xs text-gray-500">Waktu sinkron: ${j.sync_at}</span>`,
        confirmButtonColor:'#059669'
      }).then(()=>location.reload());
    }else{
      err(j.msg||'Gagal sinkronisasi data');
    }
  }catch(ex){
    Swal.close();
    err('Kesalahan koneksi sinkronisasi: '+ex.message);
  }
}
</script>
