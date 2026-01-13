<?php
// api/process_image.php (Start Task Only)
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $template_id = $_POST['template_id'];
    $game_name = $_POST['game_name'] ?? 'Game';
    $title_text = $_POST['title'] ?? '';
    $price_text = $_POST['price'] ?? '';

    try {
        $pdo->beginTransaction();

        // ກວດສອບເງິນ
        $stmt = $pdo->prepare("SELECT * FROM ai_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user['credit'] < $template['price']) throw new Exception("ຍອດເງິນບໍ່ພຽງພໍ");

        // ຕັດເງິນ
        $pdo->prepare("UPDATE users SET credit = credit - ? WHERE id = ?")->execute([$template['price'], $user_id]);

        // 1. ຕຽມ Prompt (ແບບ Full AI ທີ່ເຈົ້າມັກ)
        $final_prompt = str_replace('{game_name}', $game_name, $template['system_prompt']);
        if (strpos($final_prompt, '{user_text_title}') !== false) {
            $final_prompt = str_replace('{user_text_title}', $title_text, $final_prompt);
        } else {
            $final_prompt .= ", text \"$title_text\" written in huge beautiful typography";
        }
        if (!empty($price_text)) $final_prompt .= ", price \"$price_text\"";

        // 2. ຍິງ API ສັ່ງງານ (Create Task)
        $url = "https://api.kie.ai/api/v1/jobs/createTask";
        $postData = [
            "model" => "nano-banana-pro",
            "input" => [
                "prompt" => $final_prompt,
                "aspect_ratio" => $_POST['aspect_ratio'] ?? "1:1",
                "resolution" => "1K",
                "output_format" => "png"
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $api_key]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // ຖ້າ API Error
        if (!isset($result['data']['taskId'])) {
            throw new Exception("API Failed: " . ($result['message'] ?? 'Unknown Error'));
        }
        
        $task_id = $result['data']['taskId'];

        // 3. ບັນທຶກ Order ພ້ອມ Task ID ລົງ Database
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, template_id, task_id, status, created_at) VALUES (?, ?, ?, 'processing', NOW())");
        $stmt->execute([$user_id, $template_id, $task_id]);
        $order_id = $pdo->lastInsertId();

        $pdo->commit();

        // 4. ຕອບກັບທັນທີ! (ບໍ່ຕ້ອງຖ້າຮູບ)
        echo json_encode([
            'status' => 'processing', 
            'order_id' => $order_id,
            'message' => 'ກຳລັງສັ່ງ AI ສ້າງຮູບ...'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>