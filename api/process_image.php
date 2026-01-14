<?php
// api/process_image.php - ສະບັບແກ້ໄຂຕາມ Official Docs (image_input Array)
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

// 1. Load Env
function loadEnv($path) {
    if(!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($n, $v) = explode('=', $line, 2);
        $_ENV[trim($n)] = trim($v);
    }
}
loadEnv(__DIR__ . '/../.env');
$api_key = $_ENV['KIE_API_KEY'] ?? '';

// Check Login
if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Login required']); exit; }

// ==========================================
// 🔥 Helper: ສ້າງລິ້ງສາທາລະນະ (ແກ້ Error 422 ສຳລັບ Localhost)
// ==========================================
function getPublicUrl($localFilePath, $originalUrl) {
    // ຖ້າຢູ່ Server ຈິງ (ບໍ່ແມ່ນ localhost) ໃຊ້ລິ້ງເດີມເລີຍ
    $whitelist = ['127.0.0.1', '::1', 'localhost'];
    if (!in_array($_SERVER['HTTP_HOST'], $whitelist)) {
        return $originalUrl;
    }

    // ຖ້າເປັນ Localhost ໃຫ້ອັບໄປ Catbox ຊົ່ວຄາວ
    try {
        $ch = curl_init();
        $cfile = new CURLFile($localFilePath);
        $data = array('reqtype' => 'fileupload', 'fileToUpload' => $cfile);
        curl_setopt($ch, CURLOPT_URL, "https://catbox.moe/user/api.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || filter_var(trim($response), FILTER_VALIDATE_URL) === false) {
            return null; // ອັບໂຫລດບໍ່ໄດ້
        }
        return trim($response); // ໄດ້ລິ້ງສາທາລະນະ
    } catch (Exception $e) {
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $template_id = $_POST['template_id'] ?? null;
        
        if (!$template_id) throw new Exception("Template ID missing");

        // Get Template & Check Credit
        $stmt = $pdo->prepare("SELECT * FROM ai_templates WHERE id = ? AND is_active = 1");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch();
        if (!$template) throw new Exception("Template not found");

        $stmt = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user['credit'] < $template['price']) throw new Exception("Insufficient credit");

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE users SET credit = credit - ? WHERE id = ?")->execute([$template['price'], $user_id]);

        // ========================================================
        // 2. ປະມວນຜົນ Inputs
        // ========================================================
        $final_prompt = $template['system_prompt'];
        $form_config = json_decode($template['form_config'] ?? '[]', true);
        
        $collected_data = [];
        $image_inputs_array = []; // 🔥 Array ສຳລັບ image_input ຕາມ Docs

        foreach ($form_config as $field) {
            $key = $field['key'];      
            $post_key = 'dynamic_' . $key;
            $replace_val = "";

            if ($field['type'] == 'image') {
                // >>> ຈັດການອັບໂຫລດຮູບ <<<
                if (isset($_FILES[$post_key]) && is_array($_FILES[$post_key]['name'])) {
                    $count = count($_FILES[$post_key]['name']);
                    $dir = __DIR__ . '/../assets/uploads/user_inputs/';
                    if (!is_dir($dir)) mkdir($dir, 0777, true);

                    for($i=0; $i<$count; $i++) {
                        if ($_FILES[$post_key]['error'][$i] == 0) {
                            $ext = strtolower(pathinfo($_FILES[$post_key]['name'][$i], PATHINFO_EXTENSION));
                            if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $new_name = uniqid() . "_$i." . $ext;
                                $target_path = $dir . $new_name;
                                
                                if(move_uploaded_file($_FILES[$post_key]['tmp_name'][$i], $target_path)) {
                                    // 1. ສ້າງ URL
                                    $base = str_replace('/api/process_image.php', '', $_SERVER['SCRIPT_NAME']);
                                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                                    $local_url = "$protocol://{$_SERVER['HTTP_HOST']}$base/assets/uploads/user_inputs/$new_name";
                                    
                                    // 2. ແປງເປັນ Public URL (ສຳຄັນ!)
                                    $public_url = getPublicUrl($target_path, $local_url);
                                    
                                    if ($public_url) {
                                        $image_inputs_array[] = $public_url; // ເກັບລົງ Array
                                    }
                                }
                            }
                        }
                    }
                }
                
                // ຫມາຍເຫດ: ສຳລັບຮູບພາບ ເຮົາຈະບໍ່ເອົາ URL ໄປແທນໃນ Prompt ແລ້ວ
                // ເພາະເຮົາສົ່ງຜ່ານ parameter 'image_input' ແທນ
                // ດັ່ງນັ້ນ replace_val ເປັນຄ່າວ່າງ ເພື່ອລົບ {{key}} ອອກຈາກ Prompt
                $replace_val = ""; 

            } else {
                // Text Inputs
                $replace_val = trim($_POST[$post_key] ?? '');
            }

            // ແທນຄ່າໃນ Prompt
            $final_prompt = str_replace("{{".$key."}}", $replace_val, $final_prompt);
            $collected_data[$key] = $replace_val; // ເກັບ Log
        }

        // ========================================================
        // 3. ກຽມ Payload ຕາມ Document (Input Object Parameters)
        // ========================================================
        
        $input_object = [
            "prompt" => $final_prompt,
            "aspect_ratio" => $_POST['aspect_ratio'] ?? "1:1",
            "resolution" => "1K",
            "output_format" => "png"
        ];

        // 🔥 ໃສ່ image_input ເປັນ Array ຕາມທີ່ Docs ບອກ
        if (!empty($image_inputs_array)) {
            $input_object['image_input'] = $image_inputs_array;
        }

        $postData = [
            "model" => "nano-banana-pro", // ຫຼື $template['model_key']
            "input" => $input_object
        ];

        // ========================================================
        // 4. ສົ່ງ Request
        // ========================================================
        $ch = curl_init("https://api.kie.ai/api/v1/jobs/createTask");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $api_key"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) throw new Exception("API Connection Error: " . $err);

        $result = json_decode($response, true);
        
        // Debug Response (ຖ້າຢາກເບິ່ງວ່າ API ຕອບຫຍັງມາ)
        // file_put_contents('debug_api.txt', print_r($result, true));

        if (!isset($result['data']['taskId'])) {
            $msg = $result['message'] ?? 'Unknown Error';
            throw new Exception("AI Error: " . $msg);
        }

        // Save Order
        $pdo->prepare("INSERT INTO orders (user_id, template_id, task_id, status, user_inputs, created_at) VALUES (?, ?, ?, 'processing', ?, NOW())")
            ->execute([$user_id, $template_id, $result['data']['taskId'], json_encode($collected_data, JSON_UNESCAPED_UNICODE)]);
        
        $pdo->commit();
        echo json_encode(['status' => 'processing', 'order_id' => $pdo->lastInsertId()]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>