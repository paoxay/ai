<?php
// api/check_all.php (Updated Endpoint: recordInfo)
header('Content-Type: text/html; charset=utf-8');
require_once '../config/database.php';

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

echo "<h2>🔄 System Auto-Check (Using Endpoint: recordInfo)</h2><hr>";

$stmt = $pdo->prepare("SELECT * FROM orders WHERE status = 'processing'");
$stmt->execute();
$orders = $stmt->fetchAll();

if (count($orders) === 0) {
    exit("<h3 style='color:green'>✅ All Clear (ບໍ່ມີງານຄ້າງ)</h3>");
}

foreach ($orders as $order) {
    $order_id = $order['id'];
    $task_id = $order['task_id'];

    echo "<div>Checking Order <b>#$order_id</b> (Task: $task_id)... ";

    if (empty($task_id)) {
        echo "<span style='color:red'>[Error: No Task ID]</span></div>";
        $pdo->prepare("UPDATE orders SET status='failed' WHERE id=?")->execute([$order_id]);
        continue;
    }

    // ✅ ແກ້ໄຂ: ໃຊ້ endpoint recordInfo
    $url = "https://api.kie.ai/api/v1/jobs/recordInfo?taskId=" . $task_id;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $api_key]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $state = $result['data']['state'] ?? 'unknown';

    if ($state === 'success') {
        $json_res = json_decode($result['data']['resultJson'], true);
        $img_url = $json_res['resultUrls'][0];
        
        $final_name = 'final_' . $order_id . '.png';
        $save_dir = __DIR__ . '/../assets/images/';
        $save_path = $save_dir . $final_name;

        if (!is_dir($save_dir)) mkdir($save_dir, 0777, true);

        $image_data = file_get_contents($img_url);
        
        if ($image_data) {
            file_put_contents($save_path, $image_data);
            $db_path = 'assets/images/' . $final_name;
            $pdo->prepare("UPDATE orders SET status='completed', final_image_path=? WHERE id=?")->execute([$db_path, $order_id]);
            echo "<span style='color:green; font-weight:bold;'>[✅ ສຳເລັດ! ດາວໂຫລດແລ້ວ]</span>";
        } else {
            echo "<span style='color:red;'>[❌ Download Error]</span>";
        }

    } elseif ($state === 'fail') {
        $msg = $result['data']['failMsg'] ?? 'Unknown';
        $pdo->prepare("UPDATE orders SET status='failed' WHERE id=?")->execute([$order_id]);
        echo "<span style='color:red'>[❌ ລົ້ມເຫຼວ: $msg]</span>";
    } else {
        echo "<span style='color:orange'>[⏳ $state]</span>";
    }
    
    echo "</div>";
    flush();
}
?>