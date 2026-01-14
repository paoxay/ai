<?php
// dashboard.php (Clean UI + Allow Empty Inputs)
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// ດຶງຂໍ້ມູນ User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) { session_destroy(); header("Location: login.php"); exit; }

// ດຶງ Templates
$templates = $pdo->query("SELECT * FROM ai_templates WHERE is_active = 1 ORDER BY id DESC")->fetchAll();

// ດຶງ History
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
        /* Theme Config */
        body { background-color: #0f172a; color: white; font-family: 'Phetsarath OT', sans-serif; }
        .navbar-custom { background: rgba(15, 23, 42, 0.95); border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        /* Card Style */
        .game-card {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px; overflow: hidden; position: relative;
            transition: 0.3s; cursor: pointer; height: 100%;
        }
        .game-card:hover { transform: translateY(-5px); border-color: #3b82f6; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }
        .card-img-wrapper { height: 200px; overflow: hidden; position: relative; }
        .card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .game-card:hover .card-img-wrapper img { transform: scale(1.1); }
        
        /* Modal */
        .glass-modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); color: white; }
        .form-control-dark, .form-select-dark {
            background-color: #0f172a; border: 1px solid rgba(255,255,255,0.2); color: white; padding: 12px;
        }
        .form-control-dark:focus {
            background-color: #0f172a; color: white; border-color: #3b82f6; box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        /* Loading Spinner */
        .spinner-ai { width: 3rem; height: 3rem; border: 5px solid #1e293b; border-top: 5px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#"><i class="fas fa-robot me-2"></i>Lao AI Studio</a>
        <div class="d-flex align-items-center">
            <span class="me-3 px-3 py-1 rounded-pill border border-warning text-warning bg-black">
                <i class="fas fa-coins me-1"></i> <?= number_format($user['credit']) ?>
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">ອອກ</a>
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
        <?php $fieldsJson = htmlspecialchars($tpl['form_config'] ?? '[]', ENT_QUOTES, 'UTF-8'); ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="game-card shadow" onclick="openGenerateModal(<?= $tpl['id'] ?>, '<?= htmlspecialchars($tpl['title']) ?>', <?= $tpl['price'] ?>, '<?= $fieldsJson ?>')">
                <div class="card-img-wrapper">
                    <img src="<?= !empty($tpl['preview_image']) ? $tpl['preview_image'] : 'assets/images/default_bg.jpg' ?>">
                    <div class="position-absolute top-0 end-0 m-2 badge bg-dark bg-opacity-75 border border-warning text-warning">
                        <?= number_format($tpl['price']) ?>
                    </div>
                </div>
                <div class="p-3">
                    <h5 class="mb-1 text-white text-truncate fw-bold"><?= htmlspecialchars($tpl['title']) ?></h5>
                    <button class="btn btn-primary btn-sm rounded-pill w-100 mt-2">ເລືອກໃຊ້ງານ</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="border-start border-4 border-primary ps-3 mb-0">📜 ຜົນງານລ່າສຸດ</h4>
        <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sync"></i> Refresh</button>
    </div>
    <div class="row g-3">
        <?php foreach($histories as $h): ?>
        <div class="col-4 col-md-2">
            <div class="bg-dark border border-secondary rounded overflow-hidden position-relative">
                <?php if($h['status'] == 'completed'): ?>
                    <img src="<?= $h['final_image_path'] ?>" class="w-100" style="aspect-ratio: 1/1; object-fit: cover; cursor: pointer;" onclick="showResult('<?= $h['final_image_path'] ?>')">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center text-warning" style="aspect-ratio: 1/1;">
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
                        <label class="form-label text-info small fw-bold">ຂະໜາດຮູບພາບ</label>
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
                        <i class="fas fa-bolt me-2"></i> ຢືນຢັນການສ້າງ (<span id="modalPrice"></span> Pts)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loadingModal" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered"><div class="modal-content bg-dark text-white p-5 text-center"><div class="spinner-ai mb-4"></div><h4>ກຳລັງສ້າງ...</h4></div></div></div>
<div class="modal fade" id="resultModal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content bg-dark text-center"><img id="resultImage" class="img-fluid"><div class="modal-footer"><a id="downloadBtn" class="btn btn-success w-100" download>Download</a></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ai_shop.js"></script>

</body>
</html>