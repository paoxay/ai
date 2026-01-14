<?php
// api/check_status.php (Updated Endpoint: recordInfo)
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
if (!$order_id) exit(json_encode(['status' => 'error', 'message' => 'No Order ID']));

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) exit(json_encode(['status' => 'error', 'message' => 'Order not found']));

if ($order['status'] === 'completed') {
    echo json_encode(['status' => 'completed', 'image' => $order['final_image_path']]);
    exit;
}
if ($order['status'] === 'failed') {
    echo json_encode(['status' => 'failed', 'message' => 'Failed previously']);
    exit;
}

// ✅ ແກ້ໄຂ: ໃຊ້ endpoint recordInfo ແທນ queryTask
$url = "https://api.kie.ai/api/v1/jobs/recordInfo?taskId=" . $order['task_id'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $api_key]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// ກວດສອບໂຄງສ້າງຕາມ Doc ໃໝ່
if (isset($result['data']['state'])) {
    $state = $result['data']['state'];
    
    if ($state === 'success') {
        $json_res = json_decode($result['data']['resultJson'], true);
        $img_url = $json_res['resultUrls'][0];

        $final_name = 'final_' . $order_id . '.png';
        $save_path = __DIR__ . '/../assets/images/' . $final_name;

        // ດາວໂຫລດຮູບ
        $image_data = file_get_contents($img_url);
        if ($image_data) {
            file_put_contents($save_path, $image_data);
            $db_path = 'assets/images/' . $final_name;
            $pdo->prepare("UPDATE orders SET status='completed', final_image_path=? WHERE id=?")->execute([$db_path, $order_id]);
            echo json_encode(['status' => 'completed', 'image' => $db_path]);
        } else {
             echo json_encode(['status' => 'processing']);
        }

    } elseif ($state === 'fail') {
        $msg = $result['data']['failMsg'] ?? 'Unknown Error';
        $pdo->prepare("UPDATE orders SET status='failed' WHERE id=?")->execute([$order_id]);
        echo json_encode(['status' => 'failed', 'message' => $msg]);

    } else {
        // waiting, queuing, generating
        echo json_encode(['status' => 'processing']);
    }
} else {
    echo json_encode(['status' => 'processing']);
}
?>