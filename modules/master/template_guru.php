<?php
// Template impor guru (.xlsx). Dipanggil dari index.php case 'template' (role sudah dicek).
$rows=[
['Kode','Nama*','NUPTK','Jabatan','Status'],
['','BUDI ASHARI','1234567890123456','Guru Kelas','aktif'],
['','SITI AMINAH','','Guru Mapel','aktif'],
];
$col=function($i){$s='';$i++;while($i>0){$m=($i-1)%26;$s=chr(65+$m).$s;$i=(int)(($i-1)/26);}return $s;};
$xc=function($ref,$val,$s=null){$v=htmlspecialchars((string)$val,ENT_XML1,'UTF-8');$st=$s!==null?' s="'.$s.'"':'';return '<c r="'.$ref.'"'.$st.' t="inlineStr"><is><t>'.$v.'</t></is></c>';};
$sheet='<cols><col min="1" max="1" width="14"/><col min="2" max="2" width="26"/><col min="3" max="3" width="20"/><col min="4" max="4" width="20"/><col min="5" max="5" width="12"/></cols><sheetData>';
foreach($rows as $ri=>$r){$rn=$ri+1;$sheet.='<row r="'.$rn.'">';foreach($r as $ci=>$v){$sheet.=$xc($col($ci).$rn,$v,$ri===0?1:0);}$sheet.='</row>';}
$sheet.='</sheetData>';
$files=[
'[Content_Types].xml'=>'<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
'_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
'xl/workbook.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Guru" sheetId="1" r:id="rId1"/></sheets></workbook>',
'xl/_rels/workbook.xml.rels'=>'<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="../styles.xml"/></Relationships>',
'xl/worksheets/sheet1.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews>'.$sheet.'</worksheet>',
'xl/styles.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF059669"/><bgColor indexed="64"/></patternFill></fill><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>',
];
$tmp=tempnam(sys_get_temp_dir(),'tmpl').'.xlsx';
$zip=new ZipArchive();
if($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){http_response_code(500);exit('Gagal buat template');}
foreach($files as $name=>$content)$zip->addFromString($name,$content);
$zip->close();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template_guru.xlsx"');
header('Content-Length: '.filesize($tmp));
readfile($tmp);@unlink($tmp);exit;
