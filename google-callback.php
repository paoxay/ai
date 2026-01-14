<?php
// google-callback.php
require_once 'config/database.php';
session_start();

// *** SECURITY 2: ກວດສອບ CSRF State ***
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Security Check Failed: Invalid State Token. (ກະລຸນາກັບໄປໜ້າ Login ໃໝ່)');
}
// ລຶບ State ຖິ້ມເມື່ອໃຊ້ແລ້ວ
unset($_SESSION['oauth_state']);

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $client_id = getEnvVar('GOOGLE_CLIENT_ID');
    $client_secret = getEnvVar('GOOGLE_CLIENT_SECRET');
    $redirect_uri = getEnvVar('APP_URL') . '/google-callback.php';

    // 1. ເອົາ Code ໄປແລກ Token
    $token_url = "https://oauth2.googleapis.com/token";
    $post_data = [
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $token_data = json_decode($response, true);
    curl_close($ch);

    if (isset($token_data['access_token'])) {
        // 2. ເອົາ Token ໄປດຶງຂໍ້ມູນ User
        $user_info_url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=" . $token_data['access_token'];
        $user_info = json_decode(file_get_contents($user_info_url), true);

        if (isset($user_info['email'])) {
            $google_id = $user_info['id'];
            $email = $user_info['email'];
            $fullname = $user_info['name'];
            $avatar = $user_info['picture'];

            // 3. ກວດສອບ Database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$google_id, $email]);
            $user = $stmt->fetch();

            if ($user) {
                // User ເກົ່າ: ອັບເດດຂໍ້ມູນ
                // ຖ້າມີອີເມວຊ້ຳກັນແຕ່ google_id ຍັງບໍ່ມີ ໃຫ້ອັບເດດ google_id ໃສ່ (Link Account)
                $pdo->prepare("UPDATE users SET google_id=?, fullname=?, avatar=? WHERE id=?")
                    ->execute([$google_id, $fullname, $avatar, $user['id']]);
                
                $user_id = $user['id'];
                $role = $user['role']; // ດຶງ Role ຈາກ Database (Admin ຫຼື User)
            } else {
                // User ໃໝ່: ສ້າງເລີຍ (Default Role: user)
                $stmt = $pdo->prepare("INSERT INTO users (google_id, email, fullname, avatar, credit, role) VALUES (?, ?, ?, ?, 0.00, 'user')");
                $stmt->execute([$google_id, $email, $fullname, $avatar]);
                
                $user_id = $pdo->lastInsertId();
                $role = 'user';
            }

            // *** SECURITY 3: Session Fixation Protection ***
            // ລ້າງ Session ເກົ່າ ແລະ ສ້າງ ID ໃໝ່ທັນທີເມື່ອ Login ສຳເລັດ
            session_regenerate_id(true);

            // ບັນທຶກ Session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['avatar'] = $avatar;
            
            // *** SECURITY 4: ບັນທຶກ Browser ຂອງຜູ້ໃຊ້ (Fingerprint) ***
            // ຖ້າ Session ຖືກລັກໄປໃຊ້ເຄື່ອງອື່ນ ຈະກວດສອບໄດ້
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            // 4. Redirect ຕາມ Role
            if ($role === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    }
}

// ຖ້າຜິດພາດ
header("Location: login.php?error=google_login_failed");
exit;
?>