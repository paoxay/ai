<?php
// index.php - ໜ້າ Landing Page
session_start();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lao AI Studio - ສ້າງປ້າຍໂຄສະນາດ້ວຍ AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section { min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; position: relative; overflow: hidden; }
        .hero-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; opacity: 0.4; }
        .feature-icon { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border-radius: 50%; margin: 0 auto 20px; font-size: 2rem; color: #06b6d4; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark fixed-top py-3" style="background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-robot text-info me-2"></i> Lao AI Studio</a>
            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-ai btn-sm px-4">ໄປທີ່ Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm px-4">ເຂົ້າສູ່ລະບົບ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="hero-bg">
            <div style="position:absolute; top:20%; left:20%; width:300px; height:300px; background:#8b5cf6; filter:blur(100px); border-radius:50%;"></div>
            <div style="position:absolute; bottom:20%; right:20%; width:300px; height:300px; background:#06b6d4; filter:blur(100px); border-radius:50%;"></div>
        </div>

        <div class="container">
            <h1 class="display-3 fw-bold mb-4 text-white">
                ສ້າງປ້າຍໂຄສະນາ <span class="text-info">ພາສາລາວ</span><br>
                ດ້ວຍ AI ອັດສະລິຍະ
            </h1>
            <p class="lead text-white-50 mb-5 mx-auto" style="max-width: 700px;">
                ບໍ່ຕ້ອງຈ້າງກຣາຟິກ, ບໍ່ຕ້ອງຖ້າດົນ. ພຽງແຕ່ພິມສິ່ງທີ່ຕ້ອງການ ລະບົບຈະສ້າງຮູບພາບພ້ອມຂໍ້ຄວາມພາສາລາວທີ່ສວຍງາມໃຫ້ທ່ານພາຍໃນ 1 ນາທີ.
            </p>
            
            <div class="d-flex justify-content-center gap-3">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-ai btn-lg px-5 py-3 rounded-pill shadow-lg">
                        <i class="fas fa-magic me-2"></i> ສ້າງຮູບດຽວນີ້
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-ai btn-lg px-5 py-3 rounded-pill shadow-lg">
                        <i class="fas fa-rocket me-2"></i> ເລີ່ມຕົ້ນໃຊ້ງານຟຣີ
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-dark bg-opacity-50">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="glass-card p-4 h-100 hover-effect">
                        <div class="feature-icon"><i class="fas fa-language"></i></div>
                        <h4 class="fw-bold">ຮອງຮັບພາສາລາວ</h4>
                        <p class="text-white-50">AI ຂອງພວກເຮົາຖືກຝຶກມາໃຫ້ຈັດວາງ Font Phetsarath OT ໄດ້ຢ່າງຖືກຕ້ອງ ສວຍງາມ.</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="glass-card p-4 h-100 hover-effect">
                        <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                        <h4 class="fw-bold">ວ່ອງໄວທັນໃຈ</h4>
                        <p class="text-white-50">ໄດ້ຮັບຮູບພາບຄຸນນະພາບສູງພາຍໃນ 30-60 ວິນາທີ ພ້ອມດາວໂຫຼດໄປໃຊ້ໄດ້ເລີຍ.</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="glass-card p-4 h-100 hover-effect">
                        <div class="feature-icon"><i class="fas fa-wallet"></i></div>
                        <h4 class="fw-bold">ລາຄາປະຢັດ</h4>
                        <p class="text-white-50">ເລີ່ມຕົ້ນພຽງ 5,000 ກີບ ຕໍ່ຮູບ. ຖືກກວ່າຈ້າງເຮັດ ແລະ ສະດວກກວ່າຫຼາຍ.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-4 text-center text-white-50 border-top border-secondary mt-5">
        <small>&copy; <?php echo date('Y'); ?> Lao AI Studio. All rights reserved.</small>
    </footer>

</body>
</html>