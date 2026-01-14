<?php
// admin/templates.php
// ສະບັບສົມບູນ 100% - ຈັດການ Template ແບບລະອຽດ (Full Logic)
require_once '../config/database.php';
session_start();

// 1. ກວດສອບສິດ Admin (Security Check)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ========================================================
// 2. ຈັດການ Database Logic (Save / Update / Delete)
// ========================================================
$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // A. ຈັດການອັບໂຫລດຮູບ Preview (Cover Image)
        $imagePath = $_POST['current_image'] ?? '';
        
        if (!empty($_FILES['preview_image']['name'])) {
            $targetDir = "../assets/images/";
            // ສ້າງໂຟນເດີຖ້າຍັງບໍ່ມີ
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['preview_image']['name'], PATHINFO_EXTENSION);
            $fileName = time() . "_" . uniqid() . "." . $fileExtension;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES['preview_image']['tmp_name'], $targetFilePath)) {
                $imagePath = "assets/images/" . $fileName;
            }
        }

        // B. ຮັບຄ່າ JSON Config (Dynamic Fields) ຈາກ JavaScript
        $form_config = $_POST['form_config_json'] ?? '[]';
        
        // C. ກວດສອບ Action
        if ($_POST['action'] == 'save') {
            $id = $_POST['id'] ?? '';
            
            // ກຽມຂໍ້ມູນສຳລັບ SQL
            $title = $_POST['title'];
            $cost_price = $_POST['cost_price'];
            $price = $_POST['price'];
            $system_prompt = $_POST['system_prompt'];
            $is_active = $_POST['is_active'];
            $text_config = '{}'; // Future use

            if (empty($id)) {
                // --- INSERT (ເພີ່ມໃໝ່) ---
                $sql = "INSERT INTO ai_templates (title, cost_price, price, system_prompt, form_config, preview_image, is_active, text_config) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $cost_price, $price, $system_prompt, $form_config, $imagePath, $is_active, $text_config]);
                $success_msg = "✅ ເພີ່ມຂໍ້ມູນ Template ໃໝ່ສຳເລັດ!";
            } else {
                // --- UPDATE (ແກ້ໄຂ) ---
                $sql = "UPDATE ai_templates 
                        SET title=?, cost_price=?, price=?, system_prompt=?, form_config=?, preview_image=?, is_active=?, text_config=? 
                        WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $cost_price, $price, $system_prompt, $form_config, $imagePath, $is_active, $text_config, $id]);
                $success_msg = "✅ ບັນທຶກການແກ້ໄຂສຳເລັດຮຽບຮ້ອຍ!";
            }
        }
        elseif ($_POST['action'] == 'delete') {
            // --- DELETE (ລົບ) ---
            $id_to_delete = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM ai_templates WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $success_msg = "🗑️ ລົບຂໍ້ມູນອອກຈາກລະບົບແລ້ວ!";
        }

    } catch (Exception $e) {
        $error_msg = "❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}

// 3. ດຶງຂໍ້ມູນ Templates ທັງໝົດມາສະແດງ
$stmt = $pdo->query("SELECT * FROM ai_templates ORDER BY id DESC");
$templates = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ຈັດການ Template - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* 🔥 Theme Config: Dark Blue/Slate */
        :root {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --border-color: rgba(255,255,255,0.08);
        }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main); 
            font-family: 'Phetsarath OT', sans-serif; 
            overflow-x: hidden; 
        }

        /* Sidebar */
        .sidebar {
            width: 250px; height: 100vh; position: fixed; top: 0; left: 0;
            background-color: var(--bg-sidebar); border-right: 1px solid var(--border-color);
            z-index: 1000; padding-top: 20px;
        }
        .sidebar-brand {
            font-size: 1.4rem; font-weight: bold; color: white; 
            padding: 0 20px 20px; display: block; text-decoration: none;
        }
        .nav-link { 
            color: var(--text-muted); padding: 12px 20px; font-size: 0.95rem; 
            display: flex; align-items: center; transition: 0.3s;
        }
        .nav-link:hover, .nav-link.active { 
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15), transparent); 
            color: var(--primary); border-left: 3px solid var(--primary);
        }

        /* Content Area */
        .main-content { margin-left: 250px; padding: 30px; }

        /* Card & Table */
        .custom-card {
            background-color: var(--bg-card); border-radius: 12px;
            border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-dark-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-dark-custom th { 
            background-color: #0f172a; color: var(--text-muted); 
            padding: 15px; font-weight: 600; text-transform: uppercase; font-size: 0.85rem;
            border-bottom: 1px solid var(--border-color);
        }
        .table-dark-custom td { 
            padding: 15px; vertical-align: middle; 
            border-bottom: 1px solid var(--border-color); color: var(--text-main);
        }
        .img-preview-sm { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color); }

        /* Form Controls */
        .form-control, .form-select {
            background-color: #0f172a; border: 1px solid #334155; color: white;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0f172a; border-color: var(--primary); color: white; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }
        
        /* Dynamic Builder Styles */
        .builder-container {
            background: #0f172a; border: 1px dashed #475569; border-radius: 8px;
            min-height: 300px; max-height: 500px; overflow-y: auto; padding: 15px;
        }
        .field-item {
            background: #1e293b; border: 1px solid var(--border-color); border-radius: 8px;
            padding: 15px; margin-bottom: 10px; position: relative; transition: 0.2s;
        }
        .field-item:hover { border-color: var(--primary); }

        /* Badges & Prompts */
        .key-badge {
            cursor: pointer; background: #334155; padding: 4px 10px; border-radius: 20px;
            font-size: 0.8rem; margin-right: 5px; margin-bottom: 5px; display: inline-block;
            border: 1px solid transparent; transition: 0.2s; user-select: none;
        }
        .key-badge:hover { border-color: #eab308; color: #eab308; background: rgba(234, 179, 8, 0.1); }
        
        textarea.prompt-box {
            font-family: 'Courier New', monospace; font-size: 0.95rem; line-height: 1.6;
            background: #0b1120; color: #4ade80; border: 1px solid #334155; min-height: 250px;
        }

        /* Modal */
        .modal-content { background-color: #1e293b; border: 1px solid var(--border-color); color: white; }
        .modal-header, .modal-footer { border-color: var(--border-color); }
        .btn-close { filter: invert(1); }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="#" class="sidebar-brand"><i class="fas fa-robot text-primary me-2"></i> Admin Panel</a>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-chart-pie me-2"></i> ພາບລວມ</a>
        <a class="nav-link active" href="templates.php"><i class="fas fa-images me-2"></i> ຈັດການ Template</a>
        <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i> ສະມາຊິກ</a>
        <a class="nav-link mt-5 text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ</a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">ຈັດການ Template & ກຳໄລ</h3>
            <span class="text-muted small">ສ້າງແບບຟອມ AI, ກຳນົດລາຄາ ແລະ ຕົ້ນທຶນ</span>
        </div>
        <button class="btn btn-primary shadow-lg" onclick="openModal()">
            <i class="fas fa-plus me-2"></i> ເພີ່ມ Template ໃໝ່
        </button>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success border-0 bg-success bg-opacity-25 text-white fade show">
            <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
        </div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-25 text-white fade show">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <div class="custom-card">
        <div class="table-responsive">
            <table class="table-dark-custom">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>ຮູບ</th>
                        <th>ຊື່ Template</th>
                        <th>ຕົ້ນທຶນ (API)</th>
                        <th>ລາຄາຂາຍ</th>
                        <th>ກຳໄລ/ຮູບ</th>
                        <th>Inputs</th>
                        <th class="text-end pe-4">ຈັດການ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($templates as $tpl): 
                        $profit = $tpl['price'] - $tpl['cost_price'];
                        $fields = json_decode($tpl['form_config'], true) ?? [];
                        // ຄິດໄລ່ % ກຳໄລ
                        $margin = ($tpl['price'] > 0) ? round(($profit / $tpl['price']) * 100) : 0;
                    ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?= $tpl['id'] ?></td>
                        <td>
                            <?php if(!empty($tpl['preview_image'])): ?>
                                <img src="../<?= $tpl['preview_image'] ?>" class="img-preview-sm">
                            <?php else: ?>
                                <div class="img-preview-sm d-flex align-items-center justify-content-center bg-dark text-muted">No IMG</div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-info"><?= htmlspecialchars($tpl['title']) ?></td>
                        <td class="text-secondary"><?= number_format($tpl['cost_price']) ?></td>
                        <td class="text-warning fw-bold"><?= number_format($tpl['price']) ?></td>
                        <td>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                +<?= number_format($profit) ?> (<?= $margin ?>%)
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary text-light border border-secondary"><?= count($fields) ?> ຊ່ອງ</span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-primary me-1" onclick='editTemplate(<?= json_encode($tpl) ?>)'>
                                <i class="fas fa-edit"></i> ແກ້ໄຂ
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('⚠️ ຢືນຢັນການລຶບ Template ນີ້?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $tpl['id'] ?>">
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

<div class="modal fade" id="templateModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered"> 
        <div class="modal-content">
            <form method="post" id="mainForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-layer-group me-2"></i> ຈັດການຂໍ້ມູນ Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="inpId">
                    <input type="hidden" name="current_image" id="inpCurrImg">
                    
                    <input type="hidden" name="form_config_json" id="inpJson" value="[]">

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h6 class="text-primary fw-bold mb-3 border-bottom border-secondary pb-2">1. ຂໍ້ມູນທົ່ວໄປ</h6>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small">ຊື່ Template</label>
                                <input type="text" class="form-control" name="title" id="inpTitle" placeholder="ໃສ່ຊື່ເຊັ່ນ: ປ້າຍໂຄສະນາ..." required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small">ຕົ້ນທຶນ API</label>
                                    <input type="number" class="form-control" name="cost_price" id="inpCost" value="0" onkeyup="calcProfit()" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-warning small">ລາຄາຂາຍ (Credits)</label>
                                    <input type="number" class="form-control" name="price" id="inpPrice" value="5000" onkeyup="calcProfit()" required>
                                </div>
                            </div>

                            <div class="mb-3 p-3 rounded" id="profitBox" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3);">
                                <small class="d-block text-uppercase fw-bold text-success" style="font-size: 0.7rem;">ກຳໄລຄາດຄະເນ (Profit)</small>
                                <h3 class="m-0 fw-bold text-success" id="showProfit">0 ₭</h3>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small">ຮູບໜ້າປົກ (Preview)</label>
                                <input type="file" class="form-control" name="preview_image">
                                <small class="text-white-50">* ປະໄວ້ວ່າງຖ້າບໍ່ຕ້ອງການປ່ຽນຮູບ</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small">ສະຖານະ</label>
                                <select class="form-select" name="is_active" id="inpActive">
                                    <option value="1">🟢 ເປີດໃຊ້ງານ (Active)</option>
                                    <option value="0">🔴 ປິດ (Inactive)</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
                                <h6 class="text-warning fw-bold m-0">2. ສ້າງ Input ໃຫ້ລູກຄ້າ</h6>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="addField()">
                                    <i class="fas fa-plus"></i> ເພີ່ມຊ່ອງ
                                </button>
                            </div>
                            
                            <div id="fieldsContainer" class="builder-container"></div>
                            <small class="text-white-50 mt-2 d-block"><i class="fas fa-info-circle"></i> ກຳນົດຊ່ອງໃຫ້ລູກຄ້າປ້ອນຂໍ້ມູນ ຫຼື ອັບໂຫລດຮູບ</small>
                        </div>

                        <div class="col-lg-4 d-flex flex-column">
                            <h6 class="text-danger fw-bold mb-3 border-bottom border-secondary pb-2">3. ຄຳສັ່ງ AI (System Prompt)</h6>
                            
                            <div class="mb-2">
                                <span class="text-muted small">ຕົວແປທີ່ໃຊ້ໄດ້ (ຄິກເພື່ອແຊກ):</span>
                                <div id="keyBadges" class="mt-1 d-flex flex-wrap"></div>
                            </div>

                            <textarea name="system_prompt" id="inpPrompt" class="form-control prompt-box flex-grow-1" required 
                                placeholder="ຕົວຢ່າງ: Design a professional advertisement for {{product_name}}, style is {{style}}..."></textarea>
                            
                            <small class="text-white-50 mt-2">
                                * ໃຊ້ {{key}} ເພື່ອດຶງຂໍ້ມູນຈາກ Input ທີ່ສ້າງໃນຂັ້ນຕອນທີ 2 ມາໃສ່
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="button" class="btn btn-primary px-4 fw-bold" onclick="submitForm()">
                        <i class="fas fa-save me-2"></i> ບັນທຶກຂໍ້ມູນ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    let fields = []; // Array ເກັບຂໍ້ມູນ Fields

    // 1. ຟັງຊັນຄຳນວນກຳໄລ Real-time
    function calcProfit() {
        let cost = parseFloat(document.getElementById('inpCost').value) || 0;
        let price = parseFloat(document.getElementById('inpPrice').value) || 0;
        let profit = price - cost;
        
        let el = document.getElementById('showProfit');
        let box = document.getElementById('profitBox');
        
        el.innerText = new Intl.NumberFormat().format(profit) + ' ₭';
        
        if(profit < 0) {
            el.className = "m-0 fw-bold text-danger";
            box.style.background = 'rgba(239, 68, 68, 0.1)';
            box.style.borderColor = '#ef4444';
        } else {
            el.className = "m-0 fw-bold text-success";
            box.style.background = 'rgba(16, 185, 129, 0.1)';
            box.style.borderColor = '#10b981';
        }
    }

    // 2. ເປີດ Modal ສຳລັບເພີ່ມໃໝ່
    function openModal() {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i> ເພີ່ມ Template ໃໝ່';
        document.getElementById('mainForm').reset();
        document.getElementById('inpId').value = "";
        document.getElementById('inpCost').value = 0;
        document.getElementById('inpPrice').value = 5000;
        document.getElementById('inpJson').value = "[]";
        
        fields = []; 
        renderFields();
        calcProfit();
        modal.show();
    }

    // 3. ເປີດ Modal ສຳລັບແກ້ໄຂ
    function editTemplate(data) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> ແກ້ໄຂ Template';
        document.getElementById('inpId').value = data.id;
        document.getElementById('inpTitle').value = data.title;
        document.getElementById('inpCost').value = data.cost_price;
        document.getElementById('inpPrice').value = data.price;
        document.getElementById('inpPrompt').value = data.system_prompt;
        document.getElementById('inpActive').value = data.is_active;
        document.getElementById('inpCurrImg').value = data.preview_image;

        // ແປງ JSON string ກັບມາເປັນ Array
        try {
            fields = JSON.parse(data.form_config);
            if(!Array.isArray(fields)) fields = [];
        } catch(e) { fields = []; }
        
        renderFields();
        calcProfit();
        modal.show();
    }

    // 4. ເພີ່ມ Field ໃໝ່
    function addField() {
        const c = fields.length + 1;
        // Default structure
        fields.push({ 
            label: "Input " + c, 
            key: "key_" + c, 
            type: "text",
            placeholder: "" 
        });
        renderFields();
    }

    // 5. ລົບ Field
    function removeField(index) {
        if(confirm('ຕ້ອງການລຶບຊ່ອງນີ້ບໍ່?')) {
            fields.splice(index, 1);
            renderFields();
        }
    }

    // 6. ອັບເດດຂໍ້ມູນໃນ Array ເມື່ອມີການພິມແກ້ໄຂ
    function updateField(index, prop, val) {
        fields[index][prop] = val;
        // ຖ້າແກ້ Key ໃຫ້ໄປອັບເດດປຸ່ມ Badge ນຳ
        if(prop === 'key') {
            renderBadges();
        }
    }

    // 7. Render: ສ້າງ HTML ສະແດງ Fields
    function renderFields() {
        const container = document.getElementById('fieldsContainer');
        container.innerHTML = "";

        if (fields.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                    <p>ຍັງບໍ່ມີຊ່ອງຂໍ້ມູນ<br>ກົດປຸ່ມ <b>+ ເພີ່ມຊ່ອງ</b> ເພື່ອເລີ່ມຕົ້ນ</p>
                </div>`;
        }

        fields.forEach((f, index) => {
            const item = document.createElement('div');
            item.className = "field-item";
            item.innerHTML = `
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-primary">#${index + 1}</span>
                    <i class="fas fa-times text-danger" style="cursor:pointer" onclick="removeField(${index})" title="Remove"></i>
                </div>
                
                <div class="mb-2">
                    <label class="small text-muted">Label (ຊື່ສະແດງ)</label>
                    <input type="text" class="form-control form-control-sm" 
                        value="${f.label}" oninput="updateField(${index}, 'label', this.value)">
                </div>

                <div class="mb-2">
                    <label class="small text-muted">Key (ຊື່ຕົວແປ)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-secondary text-white border-secondary">{{</span>
                        <input type="text" class="form-control" placeholder="variable_name" 
                            value="${f.key}" oninput="updateField(${index}, 'key', this.value)">
                        <span class="input-group-text bg-secondary text-white border-secondary">}}</span>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="small text-muted">Type (ປະເພດ)</label>
                    <select class="form-select form-select-sm" onchange="updateField(${index}, 'type', this.value)">
                        <option value="text" ${f.type === 'text' ? 'selected' : ''}>Text (ຂໍ້ຄວາມສັ້ນ)</option>
                        <option value="textarea" ${f.type === 'textarea' ? 'selected' : ''}>Textarea (ຂໍ້ຄວາມຍາວ)</option>
                        <option value="number" ${f.type === 'number' ? 'selected' : ''}>Number (ຕົວເລກ)</option>
                        <option value="image" ${f.type === 'image' ? 'selected' : ''}>Image (ອັບໂຫລດຮູບ)</option>
                    </select>
                </div>
            `;
            container.appendChild(item);
        });
        
        renderBadges(); // ອັບເດດປຸ່ມ Key
    }

    // 8. Render: ສ້າງປຸ່ມ Badge ສຳລັບແຊກຕົວແປ
    function renderBadges() {
        const container = document.getElementById('keyBadges');
        container.innerHTML = "";
        
        fields.forEach(f => {
            const badge = document.createElement('span');
            badge.className = "key-badge";
            badge.innerText = "{{" + f.key + "}}";
            badge.onclick = () => insertAtCursor(document.getElementById('inpPrompt'), "{{" + f.key + "}}");
            container.appendChild(badge);
        });
    }

    // 9. Helper: ແຊກຂໍ້ຄວາມບ່ອນ Cursor
    function insertAtCursor(myField, myValue) {
        if (document.selection) {
            myField.focus();
            sel = document.selection.createRange();
            sel.text = myValue;
        } else if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
            myField.focus();
            myField.selectionStart = startPos + myValue.length;
            myField.selectionEnd = startPos + myValue.length;
        } else {
            myField.value += myValue;
            myField.focus();
        }
    }

    // 10. Submit Form
    function submitForm() {
        // ແປງ Array ເປັນ JSON String ກ່ອນສົ່ງ
        document.getElementById('inpJson').value = JSON.stringify(fields);
        document.getElementById('mainForm').submit();
    }
</script>

</body>
</html>