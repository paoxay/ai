<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ດຶງຂໍ້ມູນອໍເດີ້ຂອງລູກຄ້າຄົນນີ້
$stmt = $pdo->prepare("SELECT o.*, t.title as template_name FROM orders o JOIN ai_templates t ON o.template_id = t.id WHERE o.user_id = ? ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My History - Lao AI Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="navbar navbar-dark py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-arrow-left me-2"></i> ກັບໜ້າຫຼັກ</a>
            <span class="text-white">ປະຫວັດຜົນງານຂອງຂ້ອຍ</span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row g-4">
            <?php if(count($orders) == 0): ?>
                <div class="col-12 text-center text-white-50 mt-5">
                    <h3>ຍັງບໍ່ມີປະຫວັດການສ້າງ</h3>
                    <a href="dashboard.php" class="btn btn-ai mt-3">ໄປສ້າງຮູບທຳອິດກັນເລີຍ!</a>
                </div>
            <?php endif; ?>

            <?php foreach($orders as $order): ?>
            <div class="col-md-3 col-6">
                <div class="glass-card p-2 h-100">
                    <div class="ratio ratio-1x1 mb-2 rounded overflow-hidden">
                        <img src="<?php echo htmlspecialchars($order['final_image_path']); ?>" class="object-fit-cover w-100 h-100" style="cursor: pointer;" onclick="window.open(this.src)">
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-white-50"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                        <a href="<?php echo htmlspecialchars($order['final_image_path']); ?>" download class="btn btn-sm btn-success"><i class="fas fa-download"></i></a>
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($order['template_name']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>