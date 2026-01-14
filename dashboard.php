<?php
// dashboard.php - ສະບັບອັບເດດ UI (Upload Box + Responsive)
session_start();
require_once 'config/database.php';

// 1. ກວດສອບການ Login
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// 2. ດຶງຂໍ້ມູນ User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header("Location: login.php"); exit; }

// 3. ດຶງ Templates
$templates = $pdo->query("SELECT * FROM ai_templates WHERE is_active = 1 ORDER BY id DESC")->fetchAll();

// 4. ດຶງ History
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
    
    <style>
        /* --- Theme Config --- */
        body { background-color: #0f172a; color: white; font-family: 'Phetsarath OT', sans-serif; }
        
        /* Navbar */
        .navbar-custom { background: rgba(15, 23, 42, 0.95); border-bottom: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); }
        
        /* Game Card (Template Item) */
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
        
        /* Modal Styles */
        .glass-modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); color: white; }
        .modal-header { border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        /* Form Inputs */
        .form-control-dark, .form-select-dark {
            background-color: #0f172a; 
            border: 1px solid rgba(255,255,255,0.2); 
            color: white; 
            padding: 12px;
            border-radius: 8px;
        }
        .form-control-dark:focus {
            background-color: #0f172a; 
            color: white; 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* 🔥 New Upload Zone Style (ສຳລັບອັບໂຫລດຮູບ) */
        .upload-zone {
            border: 2px dashed #475569; 
            border-radius: 12px; 
            background: rgba(15, 23, 42, 0.5);
            cursor: pointer; 
            transition: all 0.3s ease; 
            position: relative; 
            overflow: hidden;
            text-align: center;
            padding: 20px;
        }
        .upload-zone:hover { 
            border-color: #3b82f6; 
            background: rgba(59, 130, 246, 0.1); 
            transform: scale(1.02);
        }
        .upload-zone i {
            transition: 0.3s;
        }
        .upload-zone:hover i {
            transform: translateY(-5px);
            color: #3b82f6 !important;
        }
        
        /* Textarea Resize */
        textarea.form-control-dark {
            resize: none; 
            min-height: 60px;
            overflow-y: hidden;
        }

        /* Loading Spinner */
        .spinner-ai { 
            width: 3rem; height: 3rem; 
            border: 5px solid #1e293b; 
            border-top: 5px solid #3b82f6; 
            border-radius: 50%; 
            animation: spin 1s linear infinite; 
            margin: 0 auto; 
        }
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
            // ກຽມຂໍ້ມູນ Config ເພື່ອສົ່ງໃຫ້ JS
            $fieldsJson = htmlspecialchars($tpl['form_config'] ?? '[]', ENT_QUOTES, 'UTF-8'); 
            // ກວດສອບຮູບ Preview
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

<div class="modal fade" id="genModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">✨ ຕັ້ງຄ່າ: <span id="modalTitle" class="text-primary fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="aiForm" enctype="multipart/form-data">
                    <input type="hidden" name="template_id" id="tplId">

                    <div id="dynamicFieldsContainer" class="mb-4"></div>

                    <div class="mb-4">
                        <label class="form-label text-info small fw-bold">ອັດຕາສ່ວນຮູບພາບ</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="aspect_ratio" id="ar1" value="1:1" checked>
                                <label class="btn btn-outline-secondary w-100 py-2" for="ar1">
                                    <i class="fas fa-square me-1"></i> 1:1 (ສີ່ຫຼ່ຽມ)
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="aspect_ratio" id="ar2" value="4:5">
                                <label class="btn btn-outline-secondary w-100 py-2" for="ar2">
                                    <i class="fas fa-mobile-alt me-1"></i> 4:5 (Story)
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-lg">
                        <i class="fas fa-bolt me-2"></i> ຢືນຢັນ (<span id="modalPrice"></span> Pts)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loadingModal" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white p-5 text-center border-secondary">
            <div class="spinner-ai mb-4"></div>
            <h4>ກຳລັງປະມວນຜົນ...</h4>
            <p class="text-white-50 mb-0">AI ກຳລັງວາດຮູບໃຫ້ທ່ານ ກະລຸນາລໍຖ້າ</p>
        </div>
    </div>
</div>

<div class="modal fade" id="resultModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-center border-secondary">
            <div class="modal-header border-secondary justify-content-center">
                <h5 class="modal-title text-success">🎉 ສ້າງສຳເລັດແລ້ວ!</h5>
            </div>
            <div class="modal-body p-0">
                <img id="resultImage" class="img-fluid w-100">
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ປິດ</button>
                <a id="downloadBtn" class="btn btn-success px-4" download>
                    <i class="fas fa-download me-2"></i> ດາວໂຫລດ
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ai_shop.js"></script>

</body>
</html>