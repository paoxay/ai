<?php
// admin/users.php
require_once '../config/database.php';
session_start();

// 1. ກວດສອບ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 2. ຈັດການຂໍ້ມູນ (ແກ້ໄຂ / ລົບ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // --- ແກ້ໄຂຂໍ້ມູນ (ເຕີມເງິນ / ປ່ຽນ Role) ---
        if ($_POST['action'] == 'update') {
            $stmt = $pdo->prepare("UPDATE users SET fullname=?, email=?, credit=?, role=? WHERE id=?");
            $stmt->execute([
                $_POST['fullname'],
                $_POST['email'],
                $_POST['credit'],
                $_POST['role'],
                $_POST['id']
            ]);
            $success_msg = "✅ ບັນທຶກຂໍ້ມູນສຳເລັດ!";
        }
        // --- ລົບ User ---
        elseif ($_POST['action'] == 'delete') {
            // ຫ້າມລົບໂຕເອງ!
            if ($_POST['id'] == $_SESSION['user_id']) {
                throw new Exception("ບໍ່ສາມາດລົບແອັກເຄົ້າທີ່ກຳລັງໃຊ້ງານຢູ່ໄດ້");
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$_POST['id']]);
            $success_msg = "🗑️ ລົບສະມາຊິກອອກແລ້ວ!";
        }
    } catch (Exception $e) {
        $error_msg = "❌ Error: " . $e->getMessage();
    }
}

// 3. ດຶງຂໍ້ມູນ Users ທັງໝົດ
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users WHERE fullname LIKE ? OR email LIKE ? ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ຈັດການສະມາຊິກ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-sidebar { background: rgba(0, 0, 0, 0.5); min-height: 100vh; border-right: 1px solid rgba(255,255,255,0.1); }
        .nav-link { color: #ccc; } .nav-link.active { background: linear-gradient(90deg, var(--neon-purple), transparent); color: white; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar p-3 d-none d-md-block">
            <h4 class="text-white fw-bold mb-4 px-2"><i class="fas fa-user-shield text-danger me-2"></i> Admin</h4>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php"><i class="fas fa-chart-line me-2"></i> ພາບລວມ</a>
                <a class="nav-link" href="templates.php"><i class="fas fa-layer-group me-2"></i> ຈັດການ AI & ລາຄາ</a>
                <a class="nav-link active" href="users.php"><i class="fas fa-users me-2"></i> ສະມາຊິກ</a>
                <a class="nav-link mt-5 text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ</a>
            </nav>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">ຈັດການສະມາຊິກ (<?php echo count($users); ?>)</h2>
                
                <form class="d-flex" method="GET">
                    <input class="form-control form-control-dark me-2" type="search" name="search" placeholder="ຄົ້ນຫາຊື່ ຫຼື ອີເມວ..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-info" type="submit">ຄົ້ນຫາ</button>
                </form>
            </div>

            <?php if(isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if(isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

            <div class="glass-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" style="background: transparent;">
                        <thead>
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">ຜູ້ໃຊ້ງານ</th>
                                <th class="p-3">ອີເມວ</th>
                                <th class="p-3">ສະຖານະ</th>
                                <th class="p-3 text-warning">ຍອດເງິນ (Credits)</th>
                                <th class="p-3 text-end">ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td class="p-3">#<?php echo $u['id']; ?></td>
                                <td class="p-3">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $u['avatar']; ?>" class="rounded-circle me-2" width="30" height="30">
                                        <?php echo htmlspecialchars($u['fullname']); ?>
                                    </div>
                                </td>
                                <td class="p-3 text-white-50"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="p-3">
                                    <?php if($u['role'] == 'admin'): ?>
                                        <span class="badge bg-danger">ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">USER</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 fw-bold text-warning"><?php echo number_format($u['credit']); ?> ₭</td>
                                <td class="p-3 text-end">
                                    <button class="btn btn-sm btn-info me-1" onclick='editUser(<?php echo json_encode($u); ?>)'>
                                        <i class="fas fa-edit"></i> ແກ້ໄຂ/ເຕີມເງິນ
                                    </button>
                                    
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('ຕ້ອງການລົບ User ນີ້ແທ້ບໍ່? ປະຫວັດທຸກຢ່າງຈະຫາຍໄປ!');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0" style="background: #1e293b;">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title fw-bold">ຈັດການຂໍ້ມູນສະມາຊິກ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">

                    <div class="mb-3">
                        <label class="form-label text-info small fw-bold">ຊື່-ນາມສະກຸນ</label>
                        <input type="text" class="form-control form-control-dark" name="fullname" id="editName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">ອີເມວ</label>
                        <input type="email" class="form-control form-control-dark" name="email" id="editEmail" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-warning small fw-bold">ຍອດເງິນຄົງເຫຼືອ (ກີບ)</label>
                            <input type="number" class="form-control form-control-dark fw-bold text-warning" name="credit" id="editCredit" required>
                            <small class="text-success">* ໃສ່ຕົວເລກເພື່ອເຕີມເງິນ</small>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">ສິດການໃຊ້ງານ</label>
                            <select class="form-select form-control-dark" name="role" id="editRole">
                                <option value="user">User ທຳມະດາ</option>
                                <option value="admin">Admin (ຜູ້ຈັດການ)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-success px-4">ບັນທຶກການປ່ຽນແປງ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modal = new bootstrap.Modal(document.getElementById('userModal'));

    function editUser(data) {
        document.getElementById('editId').value = data.id;
        document.getElementById('editName').value = data.fullname;
        document.getElementById('editEmail').value = data.email;
        document.getElementById('editCredit').value = data.credit;
        document.getElementById('editRole').value = data.role;
        
        modal.show();
    }
</script>

</body>
</html>