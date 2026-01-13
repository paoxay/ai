<?php
require_once 'config/database.php';
session_start();

// ຖ້າ Login ແລ້ວ ໃຫ້ໄປ Dashboard ເລີຍ
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// ສ້າງລິ້ງ Google Login
$client_id = getEnvVar('GOOGLE_CLIENT_ID');
$redirect_uri = getEnvVar('APP_URL') . '/google-callback.php';
$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?client_id={$client_id}&redirect_uri={$redirect_uri}&response_type=code&scope=email%20profile";
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lao AI Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="d-flex align-items-center justify-content-center">

    <div class="glass-card text-center p-5" style="max-width: 450px; width: 100%;">
        <div class="mb-4">
            <i class="fas fa-robot fa-3x text-info mb-3"></i>
            <h2 class="fw-bold">Lao AI Studio</h2>
            <p class="text-white-50">ລະບົບສ້າງປ້າຍໂຄສະນາອັດຕະໂນມັດ ດ້ວຍ AI</p>
        </div>

        <a href="<?php echo $auth_url; ?>" class="btn btn-light btn-lg w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" width="24">
            <span>ເຂົ້າສູ່ລະບົບດ້ວຍ Google</span>
        </a>
        
        <p class="small text-white-50 mt-3">
            * ລະບົບຈະສ້າງບັນຊີໃຫ້ອັດຕະໂນມັດເມື່ອເຂົ້າສູ່ລະບົບຄັ້ງທຳອິດ
        </p>
    </div>

</body>
</html>