<?php
header('Content-Type: application/json; charset=utf-8');

// Ensure API key setting exists
$secKey = Helper::setting($pdo, 'api_secret_key');
if (!$secKey) {
    $secKey = 'rkam_sec_' . bin2hex(random_bytes(16));
    $pdo->prepare("INSERT INTO app_settings(skey,svalue) VALUES('api_secret_key',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$secKey]);
}

// Get API Key from Header or Query Param
$passedKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? $_GET['key'] ?? $_POST['api_key'] ?? '';
if (!$passedKey && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
        $passedKey = trim($m[1]);
    }
}

if (!$passedKey || !hash_equals($secKey, $passedKey)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'API Key tidak valid / Unauthorized']);
    exit;
}

$endpoint = $p[2] ?? $arg2 ?? $_GET['action'] ?? 'guru';
if ($endpoint === 'export' && isset($p[3])) {
    $endpoint = $p[3];
}

switch ($endpoint) {
    case 'guru':
        $rows = $pdo->query("SELECT g.id, g.kode, g.nuptk, g.nama, g.jabatan_id, g.jabatan, g.status, g.created_at, g.updated_at FROM guru g ORDER BY g.nama")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'total' => count($rows), 'data' => $rows]);
        break;

    case 'rkam':
        $th = Helper::tahunAktif($pdo);
        $thId = (int)($_GET['tahun_id'] ?? $th['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT r.*, t.tahun FROM rkam r JOIN tahun_anggaran t ON t.id=r.tahun_id WHERE r.tahun_id=? ORDER BY r.id DESC");
        $stmt->execute([$thId]);
        $rkams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rkams as &$r) {
            $stItems = $pdo->prepare("SELECT * FROM rkam_items WHERE rkam_id=?");
            $stItems->execute([$r['id']]);
            $r['items'] = $stItems->fetchAll(PDO::FETCH_ASSOC);

            $stSumber = $pdo->prepare("SELECT rs.*, s.nama_sumber_dana FROM rkam_sumber_dana rs JOIN sumber_dana s ON s.id=rs.sumber_dana_id WHERE rs.rkam_id=?");
            $stSumber->execute([$r['id']]);
            $r['sumber_dana'] = $stSumber->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['ok' => true, 'tahun_id' => $thId, 'total' => count($rkams), 'data' => $rkams]);
        break;

    case 'realisasi':
        $th = Helper::tahunAktif($pdo);
        $thId = (int)($_GET['tahun_id'] ?? $th['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT r.*, k.nama_kegiatan, s.nama_sumber_dana FROM realisasi r LEFT JOIN rkam k ON k.id=r.rkam_id LEFT JOIN sumber_dana s ON s.id=r.sumber_dana_id WHERE r.tahun_id=? ORDER BY r.tanggal_transaksi DESC");
        $stmt->execute([$thId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'tahun_id' => $thId, 'total' => count($rows), 'data' => $rows]);
        break;

    case 'madrasah':
        $m = Helper::madrasah($pdo);
        unset($m['logo']);
        echo json_encode(['ok' => true, 'data' => $m]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Endpoint tidak ditemukan. Available: guru, rkam, realisasi, madrasah']);
        break;
}
exit;
