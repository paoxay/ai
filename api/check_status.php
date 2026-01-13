<?php
// api/check_status.php (Check & Download)
header('Content-Type: application/json');
require_once '../config/database.php';

// ໂຫລດ Env
function loadEnv($path) {
    if(!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
loadEnv(__DIR__ . '/../.env');
$api_key = $_ENV['KIE_API_KEY'] ?? '';

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'No Order ID']);
    exit;
}

// ດຶງ Task ID ຈາກ Database
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

// ຖ້າຮູບສຳເລັດແລ້ວ ກໍສົ່ງກັບເລີຍ
if ($order['status'] === 'completed') {
    echo json_encode(['status' => 'completed', 'image' => $order['final_image_path']]);
    exit;
}
if ($order['status'] === 'failed') {
    echo json_encode(['status' => 'failed', 'message' => 'Creation Failed previously']);
    exit;
}

// ຖ້າຍັງບໍ່ແລ້ວ ໃຫ້ໄປຖາມ API
$task_id = $order['task_id'];
$url = "https://api.kie.ai/api/v1/jobs/queryTask?taskId=" . $task_id;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $api_key]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['data']['state'])) {
    $state = $result['data']['state'];
    
    if ($state === 'success') {
        // 1. ໄດ້ URL ຮູບແລ້ວ!
        $json_res = json_decode($result['data']['resultJson'], true);
        $img_url = $json_res['resultUrls'][0];

        // 2. ດາວໂຫລດຮູບ
        $final_name = 'final_' . $order_id . '.png';
        $save_path = __DIR__ . '/../assets/images/' . $final_name;

        // Download Code
        $ch_img = curl_init($img_url);
        $fp = fopen($save_path, 'wb');
        curl_setopt($ch_img, CURLOPT_FILE, $fp);
        curl_setopt($ch_img, CURLOPT_HEADER, 0);
        curl_setopt($ch_img, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch_img);
        curl_close($ch_img);
        fclose($fp);

        // 3. ອັບເດດ Database
        $db_path = 'assets/images/' . $final_name;
        $pdo->prepare("UPDATE orders SET status='completed', final_image_path=? WHERE id=?")->execute([$db_path, $order_id]);

        echo json_encode(['status' => 'completed', 'image' => $db_path]);

    } elseif ($state === 'fail') {
        // ແຈ້ງວ່າພາດ
        $pdo->prepare("UPDATE orders SET status='failed' WHERE id=?")->execute([$order_id]);
        echo json_encode(['status' => 'failed', 'message' => $result['data']['failMsg'] ?? 'Unknown Error']);

    } else {
        // ຍັງເຮັດຢູ່ (running/pending)
        echo json_encode(['status' => 'processing']);
    }
} else {
    echo json_encode(['status' => 'processing']);
}
?>