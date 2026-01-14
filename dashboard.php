<?php
// dashboard.php - ສະບັບແກ້ໄຂ (Menu ກັບມາຄົບ + ລະບົບໃໝ່)
session_start();
require_once 'config/database.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// 2. Get User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header("Location: login.php"); exit; }

// 3. Get Templates
$templates = $pdo->query("SELECT * FROM ai_templates WHERE is_active = 1 ORDER BY id DESC")->fetchAll();

// 4. Get History
$historyStmt = $pdo->prepare("
    SELECT o.*, t.title as template_name 
    FROM orders o 
    LEFT JOIN ai_templates t ON o.template_id = t.id 
    WHERE o.user_id = ? 
    ORDER BY o.id DESC LIMIT 12
");
$historyStmt->execute([$user_id]);
$histories = $historyStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lao AI Studio - Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css"> 
    
    <style>
        /* Theme & Cards */
        body { background-color: #0f172a; color: white; font-family: 'Phetsarath OT', sans-serif; }
        .navbar-custom { background: rgba(15, 23, 42, 0.95); border-bottom: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); }
        
        /* Game Card Styles */
        .game-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px; 
            overflow: hidden; 
            position: relative;
            transition: all 0.3s ease; 
            cursor: pointer; 
            height: 100%;
        }
        .game-card:hover { 
            transform: translateY(-5px); 
            border-color: #3b82f6; 
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); 
        }
        .card-img-wrapper { height: 180px; overflow: hidden; position: relative; }
        .card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .game-card:hover .card-img-wrapper img { transform: scale(1.1); }
        
        /* Form & Modal Styles */
        .glass-modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); color: white; }
        .form-control-dark { background-color: #0f172a; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 8px; }
        .form-control-dark:focus { background-color: #0f172a; color: white; border-color: #3b82f6; }
        .spinner-ai { width: 3rem; height: 3rem; border: 5px solid #1e293b; border-top: 5px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#"><i class="fas fa-robot me-2"></i>Lao AI Studio</a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-lg-3 my-2 my-lg-0">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6">
                        <i class="fas fa-coins me-1"></i> <?= number_format($user['credit']) ?>
                    </span>
                </li>
                
                <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-home me-1"></i> ໜ້າຫຼັກ</a></li>
                <li class="nav-item"><a href="history.php" class="nav-link"><i class="fas fa-history me-1"></i> ປະຫວັດ</a></li>
                <li class="nav-item"><a href="#" class="nav-link text-success"><i class="fas fa-wallet me-1"></i> ເຕີມເງິນ</a></li>
                
                <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill w-100 px-3">ອອກ</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    
    <div class="text-center mb-5">
        <h2 class="fw-bold bg-gradient-to-r from-blue-500 to-purple-600 bg-clip-text text-white">
            ເລືອກຮູບແບບທີ່ຕ້ອງການສ້າງ
        </h2>
        <p class="text-secondary">AI ອັດສະລິຍະ ທີ່ປັບແຕ່ງໄດ້ຕາມໃຈທ່ານ</p>
    </div>

    <div class="row g-4 mb-5">
        <?php foreach($templates as $tpl): ?>
        <?php 
            $fieldsJson = htmlspecialchars($tpl['form_config'] ?? '[]', ENT_QUOTES, 'UTF-8'); 
            $previewImg = !empty($tpl['preview_image']) ? $tpl['preview_image'] : 'assets/images/default_bg.jpg';
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="game-card shadow" onclick="openGenerateModal(<?= $tpl['id'] ?>, '<?= htmlspecialchars($tpl['title']) ?>', <?= $tpl['price'] ?>, '<?= $fieldsJson ?>')">
                <div class="card-img-wrapper">
                    <img src="<?= $previewImg ?>" alt="<?= htmlspecialchars($tpl['title']) ?>">
                    <div class="position-absolute top-0 end-0 m-2 badge bg-black bg-opacity-75 border border-warning text-warning">
                        <?= number_format($tpl['price']) ?> pts
                    </div>
                </div>
                <div class="p-3">
                    <h6 class="mb-1 text-white text-truncate fw-bold"><?= htmlspecialchars($tpl['title']) ?></h6>
                    <button class="btn btn-primary btn-sm rounded-pill w-100 mt-2">
                        <i class="fas fa-magic me-1"></i> ສ້າງເລີຍ
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="border-start border-4 border-primary ps-3 mb-0 text-white">📜 ຜົນງານລ່າສຸດ</h4>
        <div>
            <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-sync"></i></button>
            <a href="history.php" class="btn btn-sm btn-outline-info">ເບິ່ງທັງໝົດ</a>
        </div>
    </div>
    
    <div class="row g-3">
        <?php foreach($histories as $h): ?>
        <div class="col-4 col-md-2">
            <div class="bg-dark border border-secondary rounded overflow-hidden position-relative ratio ratio-1x1">
                <?php if($h['status'] == 'completed'): ?>
                    <img src="<?= $h['final_image_path'] ?>" class="w-100 h-100 object-fit-cover" style="cursor: pointer;" onclick="showResult('<?= $h['final_image_path'] ?>')">
                <?php elseif($h['status'] == 'failed'): ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-danger bg-danger bg-opacity-10">
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-warning">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/modal_generate.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ai_shop.js?v=2"></script>

</body>
</html>