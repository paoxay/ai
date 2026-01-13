<?php
// admin/index.php
require_once '../config/database.php';
session_start();

// 1. ລະບົບປ້ອງກັນ: ຖ້າບໍ່ແມ່ນ Admin ດີດອອກທັນທີ!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 2. ດຶງຂໍ້ມູນສະຖິຕິ (Stats)
// ຍອດຂາຍລວມ (Total Revenue)
$stmt = $pdo->query("SELECT SUM(t.price) FROM orders o JOIN ai_templates t ON o.template_id = t.id WHERE o.status = 'completed'");
$total_revenue = $stmt->fetchColumn() ?: 0;

// ຈຳນວນອໍເດີ້ (Total Orders)
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
$total_orders = $stmt->fetchColumn();

// ຈຳນວນສະມາຊິກ (Total Users)
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$total_users = $stmt->fetchColumn();

// 3. ດຶງລາຍການສັ່ງຊື້ລ່າສຸດ (Recent Orders)
$stmt = $pdo->query("
    SELECT o.*, u.fullname, t.title 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN ai_templates t ON o.template_id = t.id 
    ORDER BY o.created_at DESC LIMIT 10
");
$recent_orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Lao AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ປັບສີ Sidebar ໃຫ້ຕ່າງຈາກໜ້າບ້ານ */
        .admin-sidebar {
            background: rgba(0, 0, 0, 0.5);
            min-height: 100vh;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        .nav-link { color: #ccc; padding: 15px 20px; border-radius: 10px; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(90deg, var(--neon-purple), transparent);
            color: white;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.2);
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar p-3 d-none d-md-block">
            <h4 class="text-white fw-bold mb-4 px-2"><i class="fas fa-user-shield text-danger me-2"></i> Admin</h4>
            <nav class="nav flex-column">
                <a class="nav-link active" href="index.php"><i class="fas fa-chart-line me-2"></i> ພາບລວມ</a>
                <a class="nav-link" href="templates.php"><i class="fas fa-layer-group me-2"></i> ຈັດການ AI & ລາຄາ</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i> ສະມາຊິກ</a>
                <a class="nav-link mt-5 text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ</a>
            </nav>
        </div>

        <div class="col-md-10 p-4">
            <h2 class="fw-bold mb-4">Dashboard ພາບລວມ</h2>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="glass-card p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-white-50 mb-1">ຍອດຂາຍລວມ</p>
                            <h3 class="fw-bold text-success mb-0"><?php echo number_format($total_revenue); ?> ກີບ</h3>
                        </div>
                        <div class="bg-success bg-opacity-25 p-3 rounded-circle">
                            <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-white-50 mb-1">ອໍເດີ້ທັງໝົດ</p>
                            <h3 class="fw-bold text-info mb-0"><?php echo number_format($total_orders); ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-25 p-3 rounded-circle">
                            <i class="fas fa-shopping-bag fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-white-50 mb-1">ສະມາຊິກທັງໝົດ</p>
                            <h3 class="fw-bold text-warning mb-0"><?php echo number_format($total_users); ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-25 p-3 rounded-circle">
                            <i class="fas fa-users fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="fw-bold mb-3">ລາຍການລ່າສຸດ</h4>
            <div class="glass-card p-0 overflow-hidden">
                <table class="table table-dark table-hover mb-0" style="background: transparent;">
                    <thead>
                        <tr>
                            <th class="p-3">ID</th>
                            <th class="p-3">ລູກຄ້າ</th>
                            <th class="p-3">ສິນຄ້າ (Template)</th>
                            <th class="p-3">ວັນທີ</th>
                            <th class="p-3">ສະຖານະ</th>
                            <th class="p-3">ຮູບຜົນລັດ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td class="p-3">#<?php echo $order['id']; ?></td>
                            <td class="p-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary rounded-circle me-2" style="width:30px;height:30px;"></div>
                                    <?php echo htmlspecialchars($order['fullname']); ?>
                                </div>
                            </td>
                            <td class="p-3"><?php echo htmlspecialchars($order['title']); ?></td>
                            <td class="p-3 text-white-50"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td class="p-3"><span class="badge bg-success">ສຳເລັດ</span></td>
                            <td class="p-3">
                                <a href="../<?php echo $order['final_image_path']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye"></i> ເບິ່ງ
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

</body>
</html>