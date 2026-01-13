<?php
// admin/templates.php (V3 - ຮອງຮັບຕົ້ນທຶນ & ກຳໄລ)
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ຈັດການ Add / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'add') {
            // ເພີ່ມ cost_price
            $stmt = $pdo->prepare("INSERT INTO ai_templates (title, cost_price, price, system_prompt, text_config, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $_POST['title'],
                $_POST['cost_price'], // ຕົ້ນທຶນ
                $_POST['price'],      // ລາຄາຂາຍ
                $_POST['system_prompt'],
                $_POST['text_config']
            ]);
            $success_msg = "✅ ເພີ່ມຂໍ້ມູນສຳເລັດ!";
        } 
        elseif ($_POST['action'] == 'update') {
            // ອັບເດດ cost_price
            $stmt = $pdo->prepare("UPDATE ai_templates SET title=?, cost_price=?, price=?, system_prompt=?, text_config=?, is_active=? WHERE id=?");
            $stmt->execute([
                $_POST['title'],
                $_POST['cost_price'],
                $_POST['price'],
                $_POST['system_prompt'],
                $_POST['text_config'],
                $_POST['is_active'],
                $_POST['id']
            ]);
            $success_msg = "✅ ບັນທຶກສຳເລັດ!";
        }
        elseif ($_POST['action'] == 'delete') {
            $stmt = $pdo->prepare("DELETE FROM ai_templates WHERE id=?");
            $stmt->execute([$_POST['id']]);
            $success_msg = "🗑️ ລົບຂໍ້ມູນແລ້ວ!";
        }
    } catch (Exception $e) {
        $error_msg = "❌ Error: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM ai_templates ORDER BY id DESC");
$templates = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ຈັດການ AI & ກຳໄລ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-sidebar { background: rgba(0, 0, 0, 0.5); min-height: 100vh; border-right: 1px solid rgba(255,255,255,0.1); }
        .nav-link { color: #ccc; } .nav-link.active { background: linear-gradient(90deg, var(--neon-purple), transparent); color: white; }
        textarea { background: #0f172a; color: #00ff41; border: 1px solid #334155; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar p-3 d-none d-md-block">
            <h4 class="text-white fw-bold mb-4 px-2"><i class="fas fa-user-shield text-danger me-2"></i> Admin</h4>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php"><i class="fas fa-chart-line me-2"></i> ພາບລວມ</a>
                <a class="nav-link active" href="templates.php"><i class="fas fa-layer-group me-2"></i> ຈັດການ AI & ລາຄາ</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i> ສະມາຊິກ</a>
                <a class="nav-link mt-5 text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ</a>
            </nav>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">ຈັດການ Template & ກຳໄລ</h2>
                <button class="btn btn-success" onclick="openAddModal()">
                    <i class="fas fa-plus me-2"></i> ເພີ່ມໃໝ່
                </button>
            </div>

            <?php if(isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if(isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

            <div class="glass-card p-0 overflow-hidden">
                <table class="table table-dark table-hover mb-0" style="background: transparent;">
                    <thead>
                        <tr>
                            <th class="p-3">ID</th>
                            <th class="p-3">ຊື່ Template</th>
                            <th class="p-3 text-secondary">ຕົ້ນທຶນ</th>
                            <th class="p-3 text-warning">ລາຄາຂາຍ</th>
                            <th class="p-3 text-success">ກຳໄລ/ອໍເດີ້</th>
                            <th class="p-3 text-end">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($templates as $tpl): 
                            $profit = $tpl['price'] - $tpl['cost_price']; // ຄຳນວນກຳໄລ
                        ?>
                        <tr>
                            <td class="p-3">#<?php echo $tpl['id']; ?></td>
                            <td class="p-3 fw-bold text-info"><?php echo htmlspecialchars($tpl['title']); ?></td>
                            <td class="p-3 text-secondary"><?php echo number_format($tpl['cost_price']); ?></td>
                            <td class="p-3 text-warning"><?php echo number_format($tpl['price']); ?></td>
                            <td class="p-3">
                                <span class="badge bg-success bg-opacity-25 text-success fs-6">
                                    +<?php echo number_format($profit); ?> ₭
                                </span>
                            </td>
                            <td class="p-3 text-end">
                                <button class="btn btn-sm btn-primary me-1" onclick='editTemplate(<?php echo json_encode($tpl); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('ລົບແທ້ບໍ່?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $tpl['id']; ?>">
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-card border-0" style="background: #1e293b;">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title fw-bold" id="modalTitle">ຈັດການ Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="templateForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="editId">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-info small fw-bold">ຊື່ Template</label>
                            <input type="text" class="form-control form-control-dark" name="title" id="editTitle" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-bold">ຕົ້ນທຶນ API (Cost)</label>
                            <input type="number" class="form-control form-control-dark" name="cost_price" id="editCost" placeholder="0" onkeyup="calcProfit()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-warning small fw-bold">ລາຄາຂາຍ (Price)</label>
                            <input type="number" class="form-control form-control-dark" name="price" id="editPrice" placeholder="0" onkeyup="calcProfit()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-success small fw-bold">ກຳໄລຕໍ່ຮູບ (Profit)</label>
                            <div class="p-2 rounded bg-success bg-opacity-10 border border-success text-center">
                                <h4 class="m-0 fw-bold text-success" id="showProfit">0 ₭</h4>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label small fw-bold">System Prompt</label>
                            <textarea class="form-control" name="system_prompt" id="editPrompt" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Text Config (JSON)</label>
                            <textarea class="form-control" name="text_config" id="editConfig" rows="4" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">ສະຖານະ</label>
                            <select class="form-select form-control-dark" name="is_active" id="editActive">
                                <option value="1">ເປີດໃຊ້ງານ</option>
                                <option value="0">ປິດ</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-success px-4" id="saveBtn">ບັນທຶກ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));

    // ຟັງຊັນຄຳນວນກຳໄລແບບ Real-time
    function calcProfit() {
        let cost = parseFloat(document.getElementById('editCost').value) || 0;
        let price = parseFloat(document.getElementById('editPrice').value) || 0;
        let profit = price - cost;
        
        // ສະແດງຜົນ ແລະ ໃສ່ຈຸດທົດສະນິຍົມ
        document.getElementById('showProfit').innerText = new Intl.NumberFormat().format(profit) + ' ₭';
        
        // ປ່ຽນສີຖ້າຂາດທຶນ
        if(profit < 0) {
            document.getElementById('showProfit').className = 'm-0 fw-bold text-danger';
        } else {
            document.getElementById('showProfit').className = 'm-0 fw-bold text-success';
        }
    }

    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'ເພີ່ມ Template ໃໝ່';
        document.getElementById('formAction').value = 'add';
        document.getElementById('saveBtn').innerText = 'ເພີ່ມຂໍ້ມູນ';
        document.getElementById('templateForm').reset();
        
        // ຕັ້ງຄ່າເລີ່ມຕົ້ນ
        document.getElementById('editCost').value = 0;
        document.getElementById('editPrice').value = 5000;
        calcProfit(); // ຄຳນວນທັນທີ
        
        document.getElementById('editConfig').value = JSON.stringify({"title": {"x":50,"y":800,"size":60,"color":"white"}}, null, 4);
        modal.show();
    }

    function editTemplate(data) {
        document.getElementById('modalTitle').innerText = 'ແກ້ໄຂ Template';
        document.getElementById('formAction').value = 'update';
        document.getElementById('saveBtn').innerText = 'ບັນທຶກການແກ້ໄຂ';

        document.getElementById('editId').value = data.id;
        document.getElementById('editTitle').value = data.title;
        document.getElementById('editCost').value = data.cost_price; // ດຶງຕົ້ນທຶນມາໃສ່
        document.getElementById('editPrice').value = data.price;
        document.getElementById('editPrompt').value = data.system_prompt;
        document.getElementById('editConfig').value = data.text_config;
        document.getElementById('editActive').value = data.is_active;

        calcProfit(); // ຄຳນວນໃຫ້ເຫັນເລີຍ
        modal.show();
    }
</script>

</body>
</html>