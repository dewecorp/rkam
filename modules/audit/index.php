<?php
$rows=$pdo->query("SELECT l.*,u.nama FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 200")->fetchAll();
?>
<div class="bg-white rounded shadow overflow-auto"><table class="w-full text-sm min-w-[800px]"><thead><tr class="bg-gray-50 border-b text-left"><th class="p-2">Waktu</th><th class="p-2">User</th><th class="p-2">Aksi</th><th class="p-2">Modul</th><th class="p-2">Record</th><th class="p-2">IP</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr class="border-b"><td class="p-2"><?=Security::e($r['created_at'])?></td><td class="p-2"><?=Security::e($r['nama']??'-')?></td><td class="p-2"><?=Security::e($r['aktivitas'])?></td><td class="p-2"><?=Security::e($r['modul'])?></td><td class="p-2"><?=Security::e($r['record_id']??'')?></td><td class="p-2"><?=Security::e($r['ip']??'')?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="6" class="p-8 text-center text-gray-500">Belum ada log.</td></tr><?php endif;?></tbody></table></div>
