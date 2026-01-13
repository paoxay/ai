<?php
// dashboard.php - ສະບັບສົມບູນ (Async Polling Fix)
session_start();
require_once 'config/database.php';

// 1. ກວດສອບການ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. ດຶງຂໍ້ມູນ User ແລະ ເຄຣດິດ
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 3. ດຶງຂໍ້ມູນ Template ທັງໝົດ
$templates = $pdo->query("SELECT * FROM ai_templates ORDER BY id ASC")->fetchAll();

// 4. ດຶງປະຫວັດການສ້າງ 10 ລາຍການລ່າສຸດ
$historyStmt = $pdo->prepare("
    SELECT o.*, t.title as template_name 
    FROM orders o 
    JOIN ai_templates t ON o.template_id = t.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC LIMIT 10
");
$historyStmt->execute([$_SESSION['user_id']]);
$histories = $historyStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lao AI Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #0f172a; color: white; font-family: 'Phetsarath OT', sans-serif; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; }
        .sidebar { min-height: 100vh; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(255,255,255,0.1); }
        .nav-link { color: #94a3b8; padding: 12px 20px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: #3b82f6; color: white; }
        .credit-badge { background: linear-gradient(45deg, #fbbf24, #d97706); color: black; font-weight: bold; border-radius: 50px; padding: 5px 15px; }
        
        /* Loading Animation */
        .spinner-ai { width: 3rem; height: 3rem; border: 5px solid #f3f3f3; border-top: 5px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-none d-md-block sidebar py-4">
            <h4 class="text-center mb-4 text-white fw-bold"><i class="fas fa-robot text-primary"></i> Lao AI</h4>
            <div class="text-center mb-4">
                <img src="<?php echo $user['avatar'] ?? 'assets/images/default_avatar.png'; ?>" class="rounded-circle mb-2" width="60">
                <h6 class="mb-0"><?php echo htmlspecialchars($user['fullname']); ?></h6>
                <small class="text-muted">ສະມາຊິກທົ່ວໄປ</small>
                <div class="mt-2">
                    <span class="credit-badge"><i class="fas fa-coins"></i> <?php echo number_format($user['credit']); ?> ກີບ</span>
                </div>
            </div>
            <nav class="nav flex-column px-2">
                <a class="nav-link active" href="dashboard.php"><i class="fas fa-magic me-2"></i> ສ້າງຮູບພາບ</a>
                <a class="nav-link" href="history.php"><i class="fas fa-history me-2"></i> ປະຫວັດ</a>
                <a class="nav-link" href="topup.php"><i class="fas fa-wallet me-2"></i> ເຕີມເງິນ</a>
                <hr class="border-secondary my-3">
                <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ</a>
            </nav>
        </div>

        <div class="col-md-9 col-lg-10 py-4 px-md-5">
            <div class="d-md-none d-flex justify-content-between align-items-center mb-4">
                <span class="fw-bold"><i class="fas fa-robot"></i> Lao AI</span>
                <span class="credit-badge"><i class="fas fa-coins"></i> <?php echo number_format($user['credit']); ?></span>
                <a href="logout.php" class="text-white"><i class="fas fa-sign-out-alt"></i></a>
            </div>

            <h3 class="mb-4">✨ ສ້າງປ້າຍໂຄສະນາດ້ວຍ AI</h3>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="glass-card p-4">
                        <form id="generateForm" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label class="form-label text-info">1. ເລືອກຮູບແບບ (Template)</label>
                                <select class="form-select bg-dark text-white border-secondary" name="template_id" required>
                                    <?php foreach($templates as $tpl): ?>
                                        <option value="<?php echo $tpl['id']; ?>">
                                            <?php echo $tpl['title']; ?> (<?php echo number_format($tpl['price']); ?> ກີບ)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr class="border-secondary">

                            <label class="form-label text-info">2. ໃສ່ຂໍ້ມູນທີ່ຕ້ອງການ</label>
                            
                            <div class="mb-3">
                                <label>ຊື່ເກມ / ຫົວຂໍ້ຫຼັກ</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" name="game_name" placeholder="ຕົວຢ່າງ: ROV, FreeFire, ໂປຣໂມຊັ່ນ" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>ຂໍ້ຄວາມຫົວຂໍ້ (Title)</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" name="title" placeholder="ຕົວຢ່າງ: ເຕີມຄຸ້ມໆ, ປົດແບນ" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>ລາຄາ / ໂປຣໂມຊັ່ນ</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" name="price" placeholder="ຕົວຢ່າງ: 5,000 ກີບ">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>ຂະໜາດຮູບພາບ</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="aspect_ratio" id="ar1" value="1:1" checked>
                                    <label class="btn btn-outline-secondary" for="ar1">1:1 (ສີ່ຫຼ່ຽມ)</label>

                                    <input type="radio" class="btn-check" name="aspect_ratio" id="ar2" value="9:16">
                                    <label class="btn btn-outline-secondary" for="ar2">9:16 (Story)</label>
                                    
                                    <input type="radio" class="btn-check" name="aspect_ratio" id="ar3" value="16:9">
                                    <label class="btn btn-outline-secondary" for="ar3">16:9 (Youtube)</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold mt-2 shadow-lg">
                                <i class="fas fa-magic me-2"></i> ສ້າງຮູບພາບດຽວນີ້ (AI)
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="glass-card p-4 h-100">
                        <h5 class="mb-3 border-bottom pb-2 border-secondary"><i class="fas fa-history text-warning"></i> ປະຫວັດລ່າສຸດ</h5>
                        <div class="list-group list-group-flush">
                            <?php foreach($histories as $h): ?>
                                <div class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="d-block text-info"><?php echo $h['template_name']; ?></small>
                                        <span class="badge <?php echo $h['status']=='completed'?'bg-success':($h['status']=='failed'?'bg-danger':'bg-warning'); ?>">
                                            <?php echo ucfirst($h['status']); ?>
                                        </span>
                                    </div>
                                    <?php if($h['status'] == 'completed'): ?>
                                        <a href="<?php echo $h['final_image_path']; ?>" target="_blank" class="btn btn-sm btn-outline-light"><i class="fas fa-eye"></i></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loadingModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-body text-center py-5">
                <div class="spinner-ai mb-4"></div>
                <h4 class="text-white" id="loadingTitle">ກຳລັງເຮັດວຽກ...</h4>
                <p class="text-white-50" id="loadingText">ກະລຸນາລໍຖ້າປະມານ 30-60 ວິນາທີ ຫ້າມປິດໜ້ານີ້</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-success"><i class="fas fa-check-circle"></i> ສ້າງສຳເລັດແລ້ວ!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0 bg-black">
                <img src="" id="resultImage" class="img-fluid" style="max-height: 70vh;">
            </div>
            <div class="modal-footer border-secondary justify-content-center">
                <a href="#" id="downloadBtn" class="btn btn-success px-4" download>
                    <i class="fas fa-download me-2"></i> ດາວໂຫລດຮູບພາບ
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ------------------------------------------------------------------
    // 🔥 JavaScript ລະບົບ Polling (ສຳຄັນທີ່ສຸດ) 🔥
    // ------------------------------------------------------------------
    
    document.getElementById('generateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // 1. ເປີດ Modal Loading
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        const resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
        
        loadingModal.show();
        document.getElementById('loadingTitle').innerText = 'ກຳລັງສົ່ງຄຳສັ່ງ...';
        document.getElementById('loadingText').innerText = 'ກຳລັງຕິດຕໍ່ຫາ AI Server...';

        const formData = new FormData(this);

        // 2. ສົ່ງຄຳສັ່ງສ້າງ (POST)
        fetch('api/process_image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'processing') {
                // ✅ ຮັບຄຳສັ່ງແລ້ວ -> ເລີ່ມວົນຖາມ
                document.getElementById('loadingTitle').innerText = 'AI ກຳລັງວາດຮູບ...';
                document.getElementById('loadingText').innerText = 'AI ຂອງພວກເຮົາກຳລັງຕັ້ງໃຈວາດ (ໃຊ້ເວລາ 30-60 ວິ)...';
                
                // ເອີ້ນຟັງຊັນວົນຖາມ
                startPolling(data.order_id, loadingModal, resultModal);
            } else {
                // ❌ Error ແຕ່ຫົວທີ
                loadingModal.hide();
                alert('ເກີດຂໍ້ຜິດພາດ: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            loadingModal.hide();
            alert('ການເຊື່ອມຕໍ່ຜິດພາດ ກະລຸນາກວດສອບອິນເຕີເນັດ');
            console.error(err);
        });
    });

    // ຟັງຊັນວົນຖາມສະຖານະ (Check Status Loop)
    function startPolling(orderId, loadingModal, resultModal) {
        let attempts = 0;
        const maxAttempts = 40; // ຖາມ 40 ເທື່ອ (40 x 3ວິ = 120 ວິນາທີ)

        const interval = setInterval(() => {
            attempts++;
            
            // ຍິງໄປຖາມ api/check_status.php
            fetch(`api/check_status.php?order_id=${orderId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'completed') {
                    // 🎉 ສຳເລັດ!
                    clearInterval(interval);
                    loadingModal.hide();

                    // ໂຊຮູບໃນ Modal
                    document.getElementById('resultImage').src = data.image;
                    document.getElementById('downloadBtn').href = data.image;
                    resultModal.show();
                    
                    // ໂຫຼດໜ້າໃໝ່ເມື່ອປິດ Modal ເພື່ອອັບເດດເຄຣດິດ
                    document.getElementById('resultModal').addEventListener('hidden.bs.modal', function () {
                        location.reload();
                    });

                } else if (data.status === 'failed') {
                    // 💀 ລົ້ມເຫຼວ
                    clearInterval(interval);
                    loadingModal.hide();
                    alert('AI ບໍ່ສາມາດສ້າງຮູບໄດ້: ' + (data.message || 'Unknown Error'));
                }
                
                // ຖ້າດົນເກີນໄປ (Timeout)
                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    loadingModal.hide();
                    alert('ໝົດເວລາລໍຖ້າ (Timeout). ກະລຸນາໄປກວດສອບທີ່ເມນູ "ປະຫວັດ" ພາຍຫຼັງ');
                }
            })
            .catch(err => console.error('Polling error:', err));
            
        }, 3000); // ຖາມທຸກໆ 3 ວິນາທີ
    }
</script>

</body>
</html>