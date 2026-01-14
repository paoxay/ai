<?php
// api/process_image.php
// (Logic Fix: Handle Empty Inputs Correctly)
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

// Load ENV
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

// ກວດສອບ Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $template_id = $_POST['template_id'];
        $aspect_ratio = $_POST['aspect_ratio'] ?? '1:1';

        // 1. ດຶງຂໍ້ມູນ Template
        $stmt = $pdo->prepare("SELECT * FROM ai_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch();

        if (!$template) throw new Exception("Template not found");

        // 2. ຕັດເງິນ
        $stmt = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user['credit'] < $template['price']) throw new Exception("Credit ບໍ່ພໍ");
        
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE users SET credit = credit - ? WHERE id = ?")->execute([$template['price'], $user_id]);

        // ========================================================
        // 🔥 3. ປະມວນຜົນ Dynamic Fields (Logic ໃໝ່)
        // ========================================================
        $final_prompt = $template['system_prompt'];
        $form_config = json_decode($template['form_config'] ?? '[]', true);
        
        $collected_data = []; 

        foreach ($form_config as $field) {
            $key = $field['key'];      
            $type = $field['type'];    
            $post_key = 'dynamic_' . $key; 

            $replacement_value = "";

            // 1. ກວດສອບວ່າ User ສົ່ງຄ່າມາບໍ່?
            if ($type == 'image') {
                // --- ກໍລະນີຮູບພາບ ---
                if (isset($_FILES[$post_key]) && $_FILES[$post_key]['error'] == 0) {
                    $upload_dir = __DIR__ . '/../assets/uploads/user_inputs/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $ext = pathinfo($_FILES[$post_key]['name'], PATHINFO_EXTENSION);
                    $new_name = 'img_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    
                    if (move_uploaded_file($_FILES[$post_key]['tmp_name'], $upload_dir . $new_name)) {
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];
                        // ຖ້າອັບໂຫລດສຳເລັດ -> ໃຊ້ URL
                        $replacement_value = "$protocol://$host/assets/uploads/user_inputs/$new_name";
                    }
                } else {
                    // ຖ້າບໍ່ອັບໂຫລດ -> ເປັນຄ່າວ່າງ
                    $replacement_value = "";
                }
            } else {
                // --- ກໍລະນີຂໍ້ຄວາມ/ຕົວເລກ ---
                $raw_val = $_POST[$post_key] ?? '';
                $replacement_value = trim($raw_val); // ຕັດຍະຫວ່າງໜ້າຫຼັງ
            }

            // 2. 🔥 ຈຸດສຳຄັນ: ການແທນຄ່າ
            if ($replacement_value === "") {
                // ຖ້າເປັນຄ່າວ່າງ -> ລົບ {{key}} ອອກຈາກ Prompt ເລີຍ
                // AI ຈະໄດ້ບໍ່ເຫັນຄຳວ່າ {{key}} ແລະ ບໍ່ມະໂນຂໍ້ມູນ
                $final_prompt = str_replace("{{" . $key . "}}", "", $final_prompt);
            } else {
                // ຖ້າມີຄ່າ -> ແທນທີ່ຕາມປົກກະຕິ
                $final_prompt = str_replace("{{" . $key . "}}", $replacement_value, $final_prompt);
            }
            
            // ເກັບ Log (ສະເພາະອັນທີ່ມີຂໍ້ມູນ)
            if ($replacement_value !== "") {
                $collected_data[$key] = $replacement_value;
            }
        }

        // ========================================================

        // 4. ສົ່ງໄປ API
        $api_url = "https://api.kie.ai/api/v1/jobs/createTask";
        $postData = [
            "model" => "nano-banana-pro",
            "input" => [
                "prompt" => $final_prompt,
                "aspect_ratio" => $aspect_ratio,
                "resolution" => "1K",
                "output_format" => "png"
            ]
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $api_key]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (!isset($result['data']['taskId'])) {
            throw new Exception("API Error: " . ($result['message'] ?? 'Unknown Error'));
        }
        
        // 5. ບັນທຶກ Order
        $user_inputs_json = json_encode($collected_data, JSON_UNESCAPED_UNICODE);
        $sql = "INSERT INTO orders (user_id, template_id, task_id, status, user_text_title, created_at) VALUES (?, ?, ?, 'processing', ?, NOW())";
        $pdo->prepare($sql)->execute([$user_id, $template_id, $result['data']['taskId'], $user_inputs_json]);
        
        $pdo->commit();
        echo json_encode(['status' => 'processing', 'order_id' => $pdo->lastInsertId()]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>