<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php $favLogo=$pdo->query("SELECT logo FROM madrasah WHERE id=1")->fetchColumn(); $favPath=$favLogo?__DIR__.'/../../uploads/logo/'.$favLogo:''; if($favLogo&&is_file($favPath)):?><link rel="icon" type="image/png" href="<?=BASE_URL?>uploads/logo/<?=Security::e($favLogo)?>?v=<?=filemtime($favPath)?>"><?php endif; ?>
<script src="https://cdn.tailwindcss.com"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<meta name="csrf" content="<?=Security::e(CSRF::token())?>"><title><?=Security::e($title??'RKAM')?> | RKAM</title>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif']},colors:{brand:{50:'#ecfdf5',100:'#d1fae5',500:'#10b981',600:'#059669',700:'#047857'}}}}}</script>
<style>
body{font-family:'Inter',system-ui,sans-serif}
html{scroll-behavior:smooth}
@media print{.no-print{display:none!important}}
*{scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent}
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px;border:1.5px solid transparent;background-clip:content-box}
::-webkit-scrollbar-thumb:hover{background:#94a3b8;border:1.5px solid transparent;background-clip:content-box}
::-webkit-scrollbar-corner{background:transparent}
/* input emerald global */
input:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not(.swal2-input),select,textarea{
border:1.5px solid #6ee7b7 !important;border-radius:.75rem !important;background:#fff !important;color:#064e3b !important}
input:focus,select:focus,textarea:focus{outline:none !important;border-color:#059669 !important;box-shadow:0 0 0 3px rgba(16,185,129,.18) !important}
input::placeholder,textarea::placeholder{color:#6bafa0 !important}
table thead tr{background:#ecfdf5 !important}table thead th{color:#065f46 !important;font-weight:700 !important;text-transform:capitalize !important}
label{text-transform:capitalize !important}
input::placeholder,textarea::placeholder{text-transform:capitalize !important}
.bg-white{border:1px solid #d1fae5}
.navlink{position:relative;display:flex;align-items:center;gap:.6rem;padding:.6rem .8rem;border-radius:1rem;color:#d1fae5;font-weight:500;transition:.15s}
.navlink:hover{background:rgba(255,255,255,.15);color:#fff}
.navlink.active{background:#fff;color:#065f46;box-shadow:0 4px 14px rgba(0,0,0,.2)}
.navlink.active::before{content:'';position:absolute;left:-1rem;top:20%;bottom:20%;width:4px;border-radius:99px;background:#fff;box-shadow:0 0 8px rgba(255,255,255,.8)}
.navlink.active::after{content:'●';margin-left:auto;font-size:8px;color:#059669}
.navlink.parent.active::after{display:none}
#mchev,#lchev{margin-left:auto;flex-shrink:0}
.navlink.sub{padding:.45rem .8rem .45rem 2.2rem;font-size:12.5px;border-radius:.8rem}
.navlink.sub::before{content:'';position:absolute;left:1.2rem;top:50%;width:6px;height:6px;transform:translateY(-50%);border-radius:99px;background:rgba(255,255,255,.5)}
.navlink.sub.active::before{left:-1rem;top:20%;bottom:20%;width:4px;height:auto;transform:none;background:#fff}
/* rounded modern global */
.rounded{border-radius:.9rem !important}
.rounded-lg{border-radius:1.1rem !important}
.rounded-xl{border-radius:1.25rem !important}
.rounded-2xl{border-radius:1.5rem !important}
.bg-white{border-radius:1.25rem !important;box-shadow:0 12px 30px -14px rgba(6,95,70,.18) !important}
button:not(.swal2-confirm):not(.swal2-cancel):not(.swal2-close){border-radius:.9rem !important}
a[class*="px-3"][class*="bg-"],a[class*="px-4"][class*="bg-"]{border-radius:.9rem !important;display:inline-flex;align-items:center;gap:.4rem}
table{border-collapse:separate !important;border-spacing:0}
table thead th:first-child{border-top-left-radius:1rem}table thead th:last-child{border-top-right-radius:1rem}
select{appearance:none !important;-webkit-appearance:none !important;-moz-appearance:none !important;border-radius:.75rem !important;background-color:#fff !important;padding-right:2.2rem !important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%23059669'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E") !important;background-repeat:no-repeat !important;background-position:right .8rem center !important;background-size:.9rem !important;cursor:pointer !important}
select::-ms-expand{display:none !important}
/* custom dropdown rounded - ganti semua select */
.dd{position:relative;min-width:8rem;max-width:100%;border:0 !important;background:transparent !important;box-shadow:none !important;padding:0 !important}
.dd>select{display:none !important}
.dd-btn{width:100%;max-width:100%;display:flex;align-items:center;justify-content:space-between;gap:.5rem;border:1.5px solid #6ee7b7 !important;border-radius:1.25rem !important;background:#fff !important;color:#064e3b !important;padding:.5rem 1rem;font-size:inherit;cursor:pointer;text-align:left;line-height:1.5;overflow:hidden;min-height:2.5rem;box-sizing:border-box}
.dd-btn>span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.dd-btn>i{flex-shrink:0}
.dd-list{position:absolute;left:0;right:0;width:100%;max-width:100%;top:calc(100% + 4px);z-index:80;background:#fff;border:1.5px solid #6ee7b7;border-radius:1.25rem !important;box-shadow:0 16px 40px -12px rgba(6,95,70,.35);max-height:12rem;overflow-x:hidden;overflow-y:auto;padding:.35rem;box-sizing:border-box;scrollbar-width:none;-ms-overflow-style:none}
.dd-list::-webkit-scrollbar{width:0;height:0}
.dd-item{padding:.5rem .75rem;border-radius:.85rem;cursor:pointer;font-size:.8rem;color:#064e3b;white-space:normal;overflow-wrap:anywhere;word-break:break-word}
.dd-item:first-child{border-top-left-radius:.95rem;border-top-right-radius:.95rem}
.dd-item:last-child{border-bottom-left-radius:.95rem;border-bottom-right-radius:.95rem}
.dd-item:hover{background:#ecfdf5}
.dd-item.sel{background:#d1fae5;font-weight:700}
.srow .dd-btn,.irow .dd-btn{min-height:0 !important;padding:.375rem .75rem !important;line-height:1.5 !important}
.srow .sumPagu{min-height:1rem}
/* samakan radius semua kontrol form */
input.rounded,input.rounded-lg,input.rounded-xl,input.rounded-2xl,select.rounded,select.rounded-lg,select.rounded-xl,select.rounded-2xl,textarea.rounded,textarea.rounded-lg,textarea.rounded-xl,textarea.rounded-2xl,.dd.rounded,.dd.rounded-lg,.dd.rounded-xl,.dd.rounded-2xl{border-radius:1.25rem !important}
.dd-list{border-radius:1.25rem !important}
.dd-item{border-radius:.5rem !important}
.dd-item:first-child{border-top-left-radius:.65rem !important;border-top-right-radius:.65rem !important}
.dd-item:last-child{border-bottom-left-radius:.65rem !important;border-bottom-right-radius:.65rem !important}
#userWrap #userDrop{display:none}
#userWrap:hover #userDrop,#userWrap:focus-within #userDrop{display:block}
#userDrop::before{content:'';position:absolute;top:-10px;left:0;right:0;height:10px}
/* tombol aksi icon modern */
.btn-ic{width:auto;height:auto;display:inline-flex;align-items:center;justify-content:center;font-size:.9rem;transition:.15s;background:transparent !important;border:0 !important;border-radius:0 !important;box-shadow:none !important;padding:.15rem .3rem}
.btn-ic:hover{transform:scale(1.2);box-shadow:none !important;background:transparent !important}
.btn-view{color:#1d4ed8 !important}
.btn-edit{color:#b45309 !important}
.btn-del{color:#dc2626 !important}
.btn-dl{color:#047857 !important}
.btn-key{color:#92400e !important}
#modal{scrollbar-width:thin}
</style>
<script>
window.__BASE='<?=BASE_URL?>';window.__CSRF='<?=Security::e(CSRF::token())?>';
const BASE=window.__BASE;const CSRF=window.__CSRF;
async function api(url,data){const f=new FormData();for(const k in data)f.append(k,data[k]);f.append('csrf_token',window.__CSRF||CSRF);const r=await fetch((window.__BASE||BASE)+'api/'+url,{method:'POST',body:f});return r.json();}
function rp(n){return 'Rp '+Number(n||0).toLocaleString('id-ID');}
function fmtID(n,dec){return Number(n||0).toLocaleString('id-ID',{minimumFractionDigits:dec||0,maximumFractionDigits:dec||0});}
function parseID(v){v=String(v??'').trim();if(!v)return 0;v=v.replace(/[^0-9,\.\-]/g,'');if(!v||v==='-'||v==='.'||v==='-,')return 0;if(v.indexOf(',')>=0){var p=v.split(',');var ip=p[0].replace(/\./g,'');var fp=p.slice(1).join('').replace(/[^0-9]/g,'');v=ip+'.'+fp;}else{var parts=v.split('.');if(parts.length===1){}else if(parts.length===2&&parts[1].length!==3){}else if(parts[parts.length-1].length===3&&parts[0].length>=1&&parts[0].length<=3){v=parts.join('');}else if(parts[parts.length-1].length===2&&parts.length>2){var fr=parts.pop();v=parts.join('')+'.'+fr;}else if(parts[parts.length-1].length===2&&parts.length===2){}else{v=parts.join('');}}var n=parseFloat(v);return isNaN(n)?0:n;}
function ddSync(sel){try{if(!sel)return;const w=sel.closest?sel.closest('.dd'):null;if(!w)return;const b=w.querySelector('.dd-btn');if(!b)return;const o=sel.options[sel.selectedIndex];b.innerHTML='<span>'+(((o&&o.text)!==''&&o)?o.text:'-')+'</span><i class="fa-solid fa-chevron-down text-[10px] text-emerald-600"></i>';}catch(e){}}
function ddRefresh(sel){try{if(!sel)return;const w=sel.closest?sel.closest('.dd'):null;if(w){w.parentNode.insertBefore(sel,w);w.remove();delete sel.dataset.dd;}if(typeof ddify==='function')ddify(sel.closest('#modalBox')||document);}catch(e){}}
function moneyDec(inp){return inp.dataset.dec?parseInt(inp.dataset.dec):0;}
document.addEventListener('money',e=>{try{const t=e.target;if(!t||!t.closest)return;if(t.closest('.irow')||(t.closest('#modalBox')&&t.classList&&(t.classList.contains('ih')||t.classList.contains('iv')))){if(typeof calc==='function')calc();}if(t.closest('.srow')||(t.closest('#modalBox')&&t.classList&&t.classList.contains('sj'))){if(typeof calcSum==='function')calcSum();}if(t.id==='rh'){if(typeof rcalc==='function')rcalc();}}catch(x){}});
function bindMoney(scope){(scope||document).querySelectorAll('input[data-money]').forEach(inp=>{if(inp.dataset.m==='1')return;inp.dataset.m='1';inp.inputMode='numeric';inp.autocomplete='off';const paint=()=>{const n=parseID(inp.value);inp.value=n?fmtID(n,moneyDec(inp)):'';};const live=()=>{const dec=moneyDec(inp);const raw=String(inp.value??'');const neg=raw.trim().startsWith('-');let dig=raw.replace(/[^0-9]/g,'').replace(/^0+(?=\d)/,'');if(!dig){inp.value='';inp.dispatchEvent(new Event('money',{bubbles:true}));return;}if(dec>0){while(dig.length<dec+1)dig='0'+dig;const intP=dig.slice(0,-dec)||'0',frac=dig.slice(-dec);inp.value=(neg?'-':'')+fmtID(parseInt(intP,10),0)+','+frac;}else{inp.value=(neg?'-':'')+fmtID(parseInt(dig,10),0);}inp.dispatchEvent(new Event('money',{bubbles:true}));};inp.addEventListener('focus',()=>{const n=parseID(inp.value);inp.value=n||'';if(inp.select)try{inp.select();}catch(e){}});inp.addEventListener('input',live);inp.addEventListener('blur',paint);if(inp.value)paint();inp.closest('form')?.addEventListener('submit',()=>{const n=parseID(inp.value);inp.value=n||'';},{capture:true});});}
function ok(msg){Swal.fire({icon:'success',title:'Berhasil',text:msg,timer:1500,showConfirmButton:false});}
function err(msg){Swal.fire({icon:'error',title:'Gagal',text:msg});}
async function ask(title,text,okText){const c=await Swal.fire({title:title||'Yakin?',text:text||'',icon:'question',showCancelButton:true,cancelButtonText:'Batal',confirmButtonText:okText||'Ya',confirmButtonColor:'#059669'});return c.isConfirmed;}
async function askLogout(){if(await ask('Logout dari aplikasi?','Sesi berakhir, login lagi untuk lanjut.','Ya, Logout'))location.href=(window.__BASE||'/rkam/')+'logout';}
function openModal(html,size){const m=document.getElementById('modal');if(!m)return;const box=document.getElementById('modalBox');box.innerHTML=html;box.classList.remove('max-w-md','max-w-2xl','max-w-4xl');box.classList.add(size==='md'?'max-w-2xl':(size==='lg'?'max-w-4xl':'max-w-md'));m.classList.remove('hidden');m.classList.add('flex');document.body.style.overflow='hidden';document.documentElement.style.overflow='hidden';m.scrollTop=0;if(typeof ddify==='function')setTimeout(()=>ddify(box),0);if(typeof bindMoney==='function')setTimeout(()=>bindMoney(box),0);}
function closeModal(){const m=document.getElementById('modal');if(!m)return;m.classList.add('hidden');m.classList.remove('flex');document.body.style.overflow='';document.documentElement.style.overflow='';}
function ddify(scope){(scope||document).querySelectorAll('select').forEach(sel=>{if(sel.dataset.dd==='1')return;if(sel.closest('#sidebar')||sel.closest('.swal2-container'))return;sel.dataset.dd='1';const keep=(sel.className||'').split(/\s+/).filter(c=>/^(w-|max-w-|min-w-|flex-1|flex-auto|col-span-|row-span-|md:|lg:|sm:|xl:|basis-|shrink|grow|order-|self-|m[trblxy]?-|gap-)/.test(c)).join(' ');const w=document.createElement('div');w.className=('dd '+keep).replace(/\s+/g,' ').trim();sel.parentNode.insertBefore(w,sel);const b=document.createElement('button');b.type='button';b.className='dd-btn';w.appendChild(b);w.appendChild(sel);const L=document.createElement('div');L.className='dd-list hidden';w.appendChild(L);function paint(){const o=sel.options[sel.selectedIndex];b.innerHTML='<span>'+((o&&o.text!=='')?o.text:'-')+'</span><i class="fa-solid fa-chevron-down text-[10px] text-emerald-600"></i>';}function build(){L.innerHTML='';[...sel.options].forEach(o=>{const d=document.createElement('div');d.className='dd-item'+(o.selected?' sel':'');d.textContent=o.text||'-';d.onclick=ev=>{ev.stopPropagation();sel.value=o.value;sel.dispatchEvent(new Event('change',{bubbles:true}));sel.dispatchEvent(new Event('input',{bubbles:true}));paint();build();L.classList.add('hidden');};L.appendChild(d);});}b.onclick=ev=>{ev.stopPropagation();document.querySelectorAll('.dd-list').forEach(x=>{if(x!==L)x.classList.add('hidden');});build();L.classList.toggle('hidden');};sel.addEventListener('change',()=>{paint();build();});paint();});if(!window.__ddDoc){window.__ddDoc=1;document.addEventListener('click',()=>{document.querySelectorAll('.dd-list').forEach(x=>x.classList.add('hidden'));});document.addEventListener('DOMContentLoaded',()=>ddify(document));}}
</script></head>
<body class="bg-gradient-to-br from-emerald-50 via-slate-100 to-emerald-100 min-h-screen text-slate-800">
<?php $u=Auth::user(); $role=Auth::role(); if($role==='superadmin'){$notif=$pdo->query("SELECT COUNT(*) c FROM notifications WHERE is_read=0")->fetchColumn();}else{$nq=$pdo->prepare("SELECT COUNT(*) c FROM notifications WHERE is_read=0 AND (role_target=? OR role_target IS NULL OR role_target='' OR user_id=?)");$nq->execute([$role,Auth::id()]);$notif=$nq->fetchColumn();} $initial=strtoupper(substr($u['nama']??'U',0,1)); $mlogo=$pdo->query("SELECT logo,nama_madrasah FROM madrasah WHERE id=1")->fetch(); $appName=Helper::setting($pdo,'app_name','SIRKAM'); $thAktif=Helper::tahunAktif($pdo); $thList=$pdo->query("SELECT id,tahun,status FROM tahun_anggaran ORDER BY tahun DESC")->fetchAll(); ?>
<div class="flex min-h-screen">
<aside id="sidebar" class="bg-gradient-to-b from-emerald-700 via-emerald-600 to-emerald-800 text-white hidden md:flex flex-col no-print fixed md:sticky top-0 inset-y-0 left-0 z-40 w-72 h-screen shrink-0 overflow-hidden">
<div class="h-16 px-5 flex items-center gap-3 shrink-0 bg-emerald-700 border-b border-white/10">
<?php $logoFile=!empty($mlogo['logo'])?__DIR__.'/../../uploads/logo/'.$mlogo['logo']:''; $logoOk=$logoFile&&is_file($logoFile); $logoV=$logoOk?filemtime($logoFile):0; ?>
<?php if($logoOk):?><img src="<?=BASE_URL?>uploads/logo/<?=Security::e($mlogo['logo'])?>?v=<?=$logoV?>" class="w-11 h-11 rounded-2xl object-contain bg-white p-1 shadow-lg"><?php else:?><div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-xl shadow-lg"><i class="fa-solid fa-mosque"></i></div><?php endif;?>
<div class="min-w-0"><div class="font-extrabold text-[15px] leading-tight truncate"><?=Security::e($appName)?></div><div class="text-xs text-emerald-100 truncate"><?=Security::e($mlogo['nama_madrasah']??'')?></div></div></div>
<?php $cur=$page??'dashboard'; if($cur==='print'||$cur==='export')$cur='laporan'; ?>
<nav class="flex-1 p-4 space-y-1.5 text-sm overflow-y-auto" id="nav">
<a href="<?=BASE_URL?>" data-p="dashboard" class="navlink <?=($cur==='dashboard'?'active':'')?>"><i class="fa-solid fa-gauge w-5 text-center"></i> Dashboard</a>
<?php if(in_array($role,['superadmin','operator'])):?>
<?php $mitems=[['bidang','Bidang','fa-layer-group'],['sumber_dana','Sumber Dana','fa-sack-dollar'],['jenis_belanja','Jenis Belanja','fa-tags'],['satuan','Satuan','fa-ruler'],['rekening','Rekening','fa-book'],['kegiatan','Kegiatan','fa-list-check'],['guru','Guru','fa-chalkboard-user'],['tahun_anggaran','Tahun Anggaran','fa-calendar']]; $mcur=($_GET['tab']??$arg??''); $mopen=($cur==='master')?'':''; ?>
<div>
<button type="button" onclick="toggleMaster()" class="navlink parent w-full text-left <?=($cur==='master'?'active':'')?>"><i class="fa-solid fa-database w-5 text-center"></i> <span>Master Data</span> <i id="mchev" class="fa-solid fa-chevron-down text-[10px] transition-transform"></i></button>
<div id="msub" class="mt-1 ml-3 pl-2 border-l border-white/10 space-y-1 <?=($cur==='master'?'':'hidden')?>">
<?php foreach($mitems as $mi):?><a href="<?=BASE_URL?>master/<?=$mi[0]?>" class="navlink sub <?=($cur==='master'&&$mcur===$mi[0]?'active':'')?>"><i class="fa-solid <?=$mi[2]?> w-5 text-center text-xs"></i> <?=$mi[1]?></a><?php endforeach;?>
</div></div>
<?php endif;?>
<a href="<?=BASE_URL?>rkam" data-p="rkam" class="navlink <?=($cur==='rkam'?'active':'')?>"><i class="fa-solid fa-clipboard-list w-5 text-center"></i> RKAM</a>
<a href="<?=BASE_URL?>realisasi" data-p="realisasi" class="navlink <?=($cur==='realisasi'?'active':'')?>"><i class="fa-solid fa-money-bill-wave w-5 text-center"></i> Realisasi</a>
<a href="<?=BASE_URL?>monitoring" data-p="monitoring" class="navlink <?=($cur==='monitoring'?'active':'')?>"><i class="fa-solid fa-chart-line w-5 text-center"></i> Monitoring</a>
<?php $litems=[['rkam','RKAM','fa-clipboard-list'],['realisasi','Realisasi','fa-money-bill-wave'],['sumber','Per Sumber Dana','fa-sack-dollar'],['bidang','Per Bidang','fa-layer-group'],['bulanan','Bulanan','fa-calendar-days'],['transaksi','Transaksi','fa-receipt']]; $lcur=($_GET['jenis']??$arg??''); ?>
<div>
<button type="button" onclick="toggleLap()" class="navlink parent w-full text-left <?=($cur==='laporan'?'active':'')?>"><i class="fa-solid fa-file-lines w-5 text-center"></i> <span>Laporan</span> <i id="lchev" class="fa-solid fa-chevron-down text-[10px] transition-transform"></i></button>
<div id="lsub" class="mt-1 ml-3 pl-2 border-l border-white/10 space-y-1 <?=($cur==='laporan'?'':'hidden')?>">
<?php foreach($litems as $li):?><a href="<?=BASE_URL?>laporan/<?=$li[0]?>" class="navlink sub <?=($cur==='laporan'&&$lcur===$li[0]?'active':'')?>"><i class="fa-solid <?=$li[2]?> w-5 text-center text-xs"></i> <?=$li[1]?></a><?php endforeach;?>
</div></div>
<?php if($role==='superadmin'):?><a href="<?=BASE_URL?>users" data-p="users" class="navlink <?=($cur==='users'?'active':'')?>"><i class="fa-solid fa-users w-5 text-center"></i> Pengguna</a><?php endif;?>
<?php if(in_array($role,['superadmin','kepala_madrasah','bendahara'])):?><a href="<?=BASE_URL?>pengaturan" data-p="pengaturan" class="navlink <?=($cur==='pengaturan'?'active':'')?>"><i class="fa-solid fa-gear w-5 text-center"></i> Pengaturan</a><?php endif;?>
<?php if($role==='superadmin'):?><a href="<?=BASE_URL?>backup" data-p="backup" class="navlink <?=($cur==='backup'?'active':'')?>"><i class="fa-solid fa-floppy-disk w-5 text-center"></i> Backup</a><?php endif;?>
</nav>
<div class="p-4 border-t border-white/20 flex items-center gap-3 shrink-0 bg-emerald-800/60">
<div class="w-9 h-9 rounded-full bg-white text-emerald-700 flex items-center justify-center font-bold"><?=$initial?></div>
<div class="text-xs"><div class="font-semibold text-white"><?=Security::e($u['nama']??'')?></div><div class="text-emerald-100"><?=Security::e($role)?> • v<?=APP_VERSION?></div></div></div></aside>
<div id="sbOverlay" class="fixed inset-0 bg-black/50 hidden z-30 md:hidden" onclick="toggleSb(false)"></div>
<div class="flex-1 flex flex-col min-w-0 md:ml-0">
<header class="sticky top-0 z-20 bg-emerald-700 text-white px-4 h-16 flex items-center gap-3 no-print shrink-0 border-b border-white/10">
<button class="md:hidden w-9 h-9 rounded-xl bg-white/10 border border-white/20 text-white" onclick="toggleSb(true)"><i class="fa-solid fa-bars"></i></button>
<?php $hari=['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu']; $bulan=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; ?>
<div class="hidden sm:flex items-center gap-2 text-white/90 text-sm"><i class="fa-solid fa-calendar-day text-emerald-300"></i><span><?=$hari[date('N')-1]?>, <?=date('j')?> <?=$bulan[(int)date('n')]?> <?=date('Y')?></span></div>
<div class="relative">
<button onclick="toggleTahun(event)" title="Tahun Anggaran Aktif" class="hidden sm:flex items-center gap-2 bg-emerald-500/20 border border-emerald-400/40 text-emerald-100 rounded-full pl-3 pr-2 py-1 text-sm hover:bg-emerald-500/30"><i class="fa-solid fa-calendar-days text-emerald-300"></i><span class="font-bold">TA <?=Security::e($thAktif['tahun']??'-')?></span><i class="fa-solid fa-chevron-down text-[10px]"></i></button>
<div id="tahunDrop" class="hidden absolute left-0 mt-2 w-48 bg-white text-slate-800 border border-emerald-100 rounded-2xl shadow-2xl z-50 overflow-hidden">
<div class="px-4 py-2 border-b border-emerald-100 text-xs font-bold text-gray-500">Pilih Tahun Anggaran</div>
<div class="max-h-60 overflow-y-auto p-1.5">
<?php foreach($thList as $t):?><button onclick="setTahun(<?=$t['id']?>)" class="w-full flex items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-emerald-50 <?=((int)($thAktif['id']??0)===(int)$t['id']?'bg-emerald-50 font-bold text-emerald-800':'text-slate-700')?>"><span><?=$t['tahun']?></span><span class="text-[10px] px-2 py-px rounded-full <?=($t['status']==='aktif'?'bg-emerald-600 text-white':'bg-gray-200 text-gray-600')?>"><?=$t['status']?></span></button><?php endforeach;?>
<?php if(!$thList):?><div class="p-3 text-xs text-gray-400">Belum ada tahun.</div><?php endif;?></div></div></div>
<div class="ml-auto flex items-center gap-3 text-sm">
<div class="relative">
<button onclick="toggleNotif(event)" class="relative w-9 h-9 rounded-xl bg-white/10 border border-white/20 text-white hover:bg-white/20"><i class="fa-solid fa-bell"></i><span id="notifBadge" class="<?=($notif>0?'':'hidden')?> absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full px-1.5 py-px"><?=$notif?></span></button>
<div id="notifDrop" class="hidden absolute right-0 mt-2 w-80 max-w-[85vw] bg-white text-slate-800 border border-emerald-100 rounded-2xl shadow-2xl z-50 overflow-hidden">
<div class="flex items-center justify-between px-4 py-3 border-b border-emerald-100"><b class="text-sm">Notifikasi</b><button onclick="readAllNotif(event)" class="text-[11px] font-bold text-emerald-700 hover:underline">Tandai Semua Dibaca</button></div>
<div id="notifList" class="max-h-80 overflow-y-auto p-2 text-sm"><div class="p-4 text-center text-gray-400">Memuat...</div></div></div></div>
<div class="relative" id="userWrap">
<button type="button" aria-haspopup="true" onclick="toggleUser(event)" onmouseenter="showUser()" class="flex items-center gap-2 bg-white/10 border border-white/20 rounded-full pl-3 pr-1 py-1 hover:bg-white/20"><span class="font-semibold text-white text-sm max-w-32 truncate"><?=Security::e($u['nama']??'')?></span><span class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold shrink-0"><?=$initial?></span></button>
<div id="userDrop" class="hidden absolute right-0 top-full pt-2 z-50" onmouseenter="showUser()" onmouseleave="hideUser()">
<div class="w-56 bg-white text-slate-800 border border-emerald-100 rounded-2xl shadow-2xl overflow-hidden">
<div class="px-4 py-3 border-b border-emerald-100 flex items-center gap-3"><span class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold shrink-0"><?=$initial?></span><div class="min-w-0"><div class="font-bold text-sm truncate"><?=Security::e($u['nama']??'')?></div><div class="text-[11px] text-gray-500 truncate">@<?=Security::e($u['username']??'')?></div></div></div>
<div class="p-2"><button type="button" onclick="askLogout()" class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-red-600 hover:bg-red-50"><i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout</button></div></div></div></div></div></header>
<main class="p-4 md:p-8 flex-1 max-w-7xl w-full mx-auto">
<div class="mb-4"><div class="font-extrabold text-xl text-slate-800"><?=Security::e($title??'')?></div><div class="text-xs text-emerald-700 font-medium">Rencana Kegiatan & Anggaran Madrasah</div></div>
<?=$content??''?></main>
<footer class="px-6 pb-6 text-center text-[11px] text-emerald-800/60 no-print"><?=Security::e($appName)?> v<?=APP_VERSION?> • <?=date('Y')?> • Modern • Aman • Responsif</footer></div></div>
<div id="modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden justify-center items-start p-2 md:p-4 z-50 overflow-y-auto overscroll-contain"><div id="modalBox" class="bg-white rounded-2xl border border-emerald-100 shadow-2xl w-full max-w-4xl my-4 p-5 md:p-6 overflow-visible"></div></div>
<script>
function toggleSb(o){const s=document.getElementById('sidebar'),v=document.getElementById('sbOverlay');if(o){s.classList.remove('hidden');s.classList.add('flex');v.classList.remove('hidden');}else{if(window.innerWidth<768){s.classList.add('hidden');s.classList.remove('flex');}v.classList.add('hidden');}}
let __uT=null;
function showUser(){const d=document.getElementById('userDrop');if(!d)return;if(__uT){clearTimeout(__uT);__uT=null;}d.classList.remove('hidden');}
function hideUser(){const d=document.getElementById('userDrop');if(!d)return;if(__uT)clearTimeout(__uT);__uT=setTimeout(()=>d.classList.add('hidden'),120);}
function toggleUser(e){if(e)e.stopPropagation();const d=document.getElementById('userDrop');if(!d)return;d.classList.toggle('hidden');}
document.addEventListener('click',e=>{const d=document.getElementById('userDrop');if(d&&!d.classList.contains('hidden')&&!e.target.closest('#userWrap'))d.classList.add('hidden');});
function toggleMaster(force){const s=document.getElementById('msub'),c=document.getElementById('mchev');if(!s)return;const show=(typeof force==='boolean')?force:s.classList.contains('hidden');s.classList.toggle('hidden',!show);if(c)c.style.transform=show?'rotate(180deg)':'';try{localStorage.setItem('mopen',show?'1':'0');}catch(e){}}
function toggleLap(force){const s=document.getElementById('lsub'),c=document.getElementById('lchev');if(!s)return;const show=(typeof force==='boolean')?force:s.classList.contains('hidden');s.classList.toggle('hidden',!show);if(c)c.style.transform=show?'rotate(180deg)':'';try{localStorage.setItem('lopen',show?'1':'0');}catch(e){}}
(function(){try{if(localStorage.getItem('mopen')==='1'&&!document.querySelector('#msub .navlink.active'))toggleMaster(true);if(document.querySelector('#msub .navlink.active'))toggleMaster(true);if(localStorage.getItem('lopen')==='1'&&!document.querySelector('#lsub .navlink.active'))toggleLap(true);if(document.querySelector('#lsub .navlink.active'))toggleLap(true);}catch(e){}})();
function keepActiveVisible(){try{var nav=document.getElementById('nav');if(!nav)return;var a=nav.querySelector('.navlink.active');if(!a)return;var nr=nav.getBoundingClientRect(),ar=a.getBoundingClientRect();var y=ar.top-nr.top+nav.scrollTop-nav.clientHeight/2+a.clientHeight/2;nav.scrollTop=Math.max(0,y);}catch(e){}}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(keepActiveVisible,60);});else setTimeout(keepActiveVisible,60);
document.getElementById('modal').addEventListener('click',e=>{if(e.target.id==='modal')closeModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
if(typeof ddify==='function'){if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>ddify(document));else ddify(document);}
function loadNotif(){fetch((window.__BASE||'/rkam/')+'api/notif_list',{method:'POST',body:new URLSearchParams({csrf_token:window.__CSRF})}).then(r=>r.json()).then(j=>{let h='<div class="flex justify-between items-center mb-3"><b>Notifikasi</b><button type="button" onclick="closeModal()" title="Tutup" class="w-8 h-8 rounded-full bg-red-50 border border-red-200 text-red-600 flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button></div>';(j.data||[]).forEach(n=>{h+=`<div class="border border-emerald-100 bg-emerald-50/50 rounded-xl p-2 mb-2 text-sm"><b>${n.judul}</b><div>${n.pesan||''}</div></div>`;});openModal(h);});}
function notifTarget(n){const b=(window.__BASE||'/rkam/');if(n.modul==='rkam'&&n.record_id)return b+'rkam/detail/'+n.record_id;return b+'rkam';}
function paintBadge(){const left=document.querySelectorAll('#notifList .nitem[data-read="0"]').length;const bg=document.getElementById('notifBadge');if(!bg)return;if(left>0){bg.textContent=left;bg.classList.remove('hidden');}else{bg.classList.add('hidden');}}
async function toggleNotif(e){if(e)e.stopPropagation();const d=document.getElementById('notifDrop');const open=d.classList.contains('hidden');document.querySelectorAll('.dd-list').forEach(x=>x.classList.add('hidden'));if(!open){d.classList.add('hidden');return;}d.classList.remove('hidden');const box=document.getElementById('notifList');box.innerHTML='<div class="p-4 text-center text-gray-400">Memuat...</div>';try{const j=await api('x',{act:'notif_list'});if(!j.ok||!(j.data||[]).length){box.innerHTML='<div class="p-4 text-center text-gray-400">Tidak ada notifikasi.</div>';return;}box.innerHTML=(j.data||[]).map(n=>{const un=String(n.is_read)==='0';return `<button onclick="clickNotif(event,${n.id})" data-id="${n.id}" data-read="${un?0:1}" class="nitem w-full text-left rounded-xl p-2.5 mb-1 border ${un?'bg-emerald-50 border-emerald-200':'bg-white border-gray-100'} hover:bg-emerald-50"><div class="${un?'font-extrabold text-slate-800':'font-normal text-gray-500'} text-[13px]">${(n.judul||'').replace(/</g,'&lt;')}</div><div class="${un?'text-slate-600':'text-gray-400'} text-xs">${(n.pesan||'').replace(/</g,'&lt;')}</div></button>`;}).join('');paintBadge();}catch(ex){box.innerHTML='<div class="p-4 text-center text-red-500">Gagal memuat.</div>';}}
async function clickNotif(e,id){if(e){e.preventDefault();e.stopPropagation();}const el=document.querySelector('#notifList .nitem[data-id="'+id+'"]');try{const j=await api('x',{act:'notif_read',id});const target=(j&&j.ok&&j.target)?j.target:((window.__BASE||'/rkam/')+'rkam');if(el){el.dataset.read='1';}paintBadge();location.href=target;}catch(ex){location.href=(window.__BASE||'/rkam/')+'rkam';}}
async function readAllNotif(e){if(e){e.preventDefault();e.stopPropagation();}const j=await api('x',{act:'notif_read_all'});if(j.ok){document.querySelectorAll('#notifList .nitem').forEach(x=>{x.dataset.read='1';x.classList.remove('bg-emerald-50','border-emerald-200');x.classList.add('bg-white','border-gray-100');});paintBadge();}}
document.addEventListener('click',e=>{const d=document.getElementById('notifDrop');if(d&&!d.classList.contains('hidden')&&!e.target.closest('#notifDrop')&&!e.target.closest('[onclick^="toggleNotif"]'))d.classList.add('hidden');const t=document.getElementById('tahunDrop');if(t&&!t.classList.contains('hidden')&&!e.target.closest('#tahunDrop')&&!e.target.closest('[onclick^="toggleTahun"]'))t.classList.add('hidden');});
function toggleTahun(e){if(e)e.stopPropagation();const d=document.getElementById('tahunDrop');if(!d)return;const nd=document.getElementById('notifDrop');if(nd)nd.classList.add('hidden');d.classList.toggle('hidden');}
async function setTahun(id){if(!await ask('Ganti tahun aktif?','Dashboard + filter ikut tahun ini.','Ya, Ganti'))return;const j=await api('x',{act:'tahun_aktif',id});if(j.ok){ok('Tahun aktif '+j.tahun);setTimeout(()=>location.reload(),900);}else err(j.msg||'Gagal');}
</script>
</body></html>
