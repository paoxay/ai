<?php
// api/process_image.php
// Logic ສົມບູນ: ຮັບ Text, ອັບໂຫລດຮູບ, ແທນຄ່າ Prompt, ຕັດເງິນ, ຍິງ API
header('Content-Type: application/json');

// 1. Load Config & Database
require_once '../config/database.php';
session_start();

// Helper Function: ໂຫຼດ .env
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

// 2. ກວດສອບ Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບກ່ອນ (Login required)']);
    exit;
}

// 3. ເລີ່ມຕົ້ນການປະມວນຜົນ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $template_id = $_POST['template_id'] ?? null;
        $aspect_ratio = $_POST['aspect_ratio'] ?? '1:1';

        if (!$template_id) throw new Exception("ບໍ່ພົບຂໍ້ມູນ Template ID");

        // A. ດຶງຂໍ້ມູນ Template ຈາກ Database
        $stmt = $pdo->prepare("SELECT * FROM ai_templates WHERE id = ? AND is_active = 1");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch();

        if (!$template) throw new Exception("Template ນີ້ບໍ່ມີຢູ່ ຫຼື ຖືກປິດໃຊ້ງານ");

        // B. ກວດສອບ ແລະ ຕັດເງິນ (Credit)
        $stmt = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user['credit'] < $template['price']) {
            throw new Exception("ຍອດເງິນຂອງທ່ານບໍ່ພຽງພໍ! ກະລຸນາເຕີມເງິນ.");
        }
        
        // ເລີ່ມ Transaction (ເພື່ອຄວາມປອດໄພຂອງຂໍ້ມູນການເງິນ)
        $pdo->beginTransaction();

        // ຕັດເງິນ
        $pdo->prepare("UPDATE users SET credit = credit - ? WHERE id = ?")->execute([$template['price'], $user_id]);

        // ========================================================
        // C. ປະມວນຜົນ Dynamic Inputs (Text & Image)
        // ========================================================
        $final_prompt = $template['system_prompt'];
        $form_config = json_decode($template['form_config'] ?? '[]', true);
        
        $collected_data = []; // ເກັບຂໍ້ມູນທີ່ User ປ້ອນມາເພື່ອບັນທຶກ Log

        foreach ($form_config as $field) {
            $key = $field['key'];      
            $type = $field['type'];    
            $post_key = 'dynamic_' . $key; // ຊື່ Field ທີ່ສົ່ງມາຈາກ Frontend

            $replacement_value = "";

            // --- ກວດສອບປະເພດຂໍ້ມູນ ---
            
            if ($type == 'image') {
                // >>> ກໍລະນີຮູບພາບ (Upload) <<<
                if (isset($_FILES[$post_key]) && $_FILES[$post_key]['error'] == 0) {
                    
                    // ຕັ້ງຄ່າ Path
                    $upload_dir = __DIR__ . '/../assets/uploads/user_inputs/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    // ກວດສອບນາມສະກຸນໄຟລ໌
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext = strtolower(pathinfo($_FILES[$post_key]['name'], PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        // ຕັ້ງຊື່ໄຟລ໌ໃໝ່ (Random)
                        $new_name = 'img_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                        $target_file = $upload_dir . $new_name;
                        
                        // ຍ້າຍໄຟລ໌
                        if (move_uploaded_file($_FILES[$post_key]['tmp_name'], $target_file)) {
                            // ສ້າງ URL ເພື່ອສົ່ງໃຫ້ AI
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            
                            // ສົມມຸດວ່າ Folder Project ຢູ່ Root, ຖ້າຢູ່ໃນ Subfolder ຕ້ອງປັບ Path ນີ້
                            // ຕົວຢ່າງ: https://example.com/assets/uploads/user_inputs/xxx.jpg
                            $replacement_value = "$protocol://$host/paoxay/ai/paoxay-ai-e392c411f55f74204028a5ea50406ed432928638/assets/uploads/user_inputs/$new_name";
                        }
                    }
                }
                // ໝາຍເຫດ: ຖ້າບໍ່ອັບໂຫລດ $replacement_value ຈະເປັນ ""
            } 
            else {
                // >>> ກໍລະນີຂໍ້ຄວາມ/ຕົວເລກ (Text/Number/Textarea) <<<
                $raw_val = $_POST[$post_key] ?? '';
                $replacement_value = trim($raw_val);
            }

            // --- ການແທນຄ່າໃນ Prompt ---
            if ($replacement_value === "") {
                // ຖ້າເປັນຄ່າວ່າງ -> ລົບ {{key}} ອອກຈາກ Prompt 
                // (ເພື່ອບໍ່ໃຫ້ AI ສັບສົນກັບ Placeholder ທີ່ຄ້າງຢູ່)
                $final_prompt = str_replace("{{" . $key . "}}", "", $final_prompt);
            } else {
                // ແທນທີ່ {{key}} ດ້ວຍຂໍ້ມູນຈິງ
                $final_prompt = str_replace("{{" . $key . "}}", $replacement_value, $final_prompt);
                
                // ເກັບ Log (ບໍ່ເກັບຄ່າວ່າງ)
                $collected_data[$key] = $replacement_value;
            }
        }

        // ========================================================
        // D. ສົ່ງຄຳສັ່ງໄປຫາ KIE API
        // ========================================================
        $api_url = "https://api.kie.ai/api/v1/jobs/createTask";
        
        $postData = [
            "model" => "nano-banana-pro", // ຫຼືໃຊ້ $template['model_key'] ຖ້າມີ
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $api_key
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // ກວດສອບຜົນລັບ API
        if ($curl_error) {
            throw new Exception("Connection Error: " . $curl_error);
        }
        
        $result = json_decode($response, true);
        
        // ຖ້າ API Error ຫຼື ບໍ່ໄດ້ Task ID
        if ($http_code !== 200 || !isset($result['data']['taskId'])) {
            throw new Exception("AI Provider Error: " . ($result['message'] ?? 'Unknown Error'));
        }
        
        // E. ບັນທຶກ Order ລົງ Database
        $taskId = $result['data']['taskId'];
        $user_inputs_json = json_encode($collected_data, JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO orders (user_id, template_id, task_id, status, user_inputs, created_at) 
                VALUES (?, ?, ?, 'processing', ?, NOW())";
        
        $pdo->prepare($sql)->execute([$user_id, $template_id, $taskId, $user_inputs_json]);
        $order_id = $pdo->lastInsertId();
        
        // Commit Transaction (ບັນທຶກທຸກຢ່າງຖ້າສຳເລັດ)
        $pdo->commit();

        // F. ສົ່ງຄືນຜົນລັບໃຫ້ Frontend
        echo json_encode([
            'status' => 'processing', 
            'order_id' => $order_id,
            'message' => 'Task created successfully'
        ]);

    } catch (Exception $e) {
        // ຖ້າເກີດ Error ໃຫ້ Rollback ເງິນຄືນ
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage()
        ]);
    }
}
?>