<?php
// admin/templates.php
// ສະບັບແກ້ໄຂສົມບູນ: Design Dark Theme (ຕາມຮູບ 2), Logic ແກ້ໄຂແລ້ວ, ລະບົບຄົບ 100%
require_once '../config/database.php';
session_start();

// 1. ກວດສອບສິດ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ========================================================
// 2. ຈັດການ Database (Save / Update / Delete)
// ========================================================
$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // ຈັດການອັບໂຫລດຮູບ Preview
        $imagePath = $_POST['current_image'] ?? '';
        if (!empty($_FILES['preview_image']['name'])) {
            $targetDir = "../assets/images/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . "_" . basename($_FILES['preview_image']['name']);
            if (move_uploaded_file($_FILES['preview_image']['tmp_name'], $targetDir . $fileName)) {
                $imagePath = "assets/images/" . $fileName;
            }
        }

        // ຮັບຄ່າ JSON Config (Dynamic Fields)
        $form_config = $_POST['form_config_json'] ?? '[]';

        // ຮັບຄ່າ Text Config (JSON)
        $text_config = $_POST['text_config'] ?? '{}';

        if ($_POST['action'] == 'save') {
            $id = $_POST['id'] ?? '';
            
            // Parameter ສຳລັບ SQL
            $params = [
                $_POST['title'],
                $_POST['cost_price'],
                $_POST['price'],
                $_POST['system_prompt'],
                $form_config,
                $imagePath,
                $_POST['is_active'],
                $text_config
            ];

            if (empty($id)) {
                // INSERT
                $sql = "INSERT INTO ai_templates (title, cost_price, price, system_prompt, form_config, preview_image, is_active, text_config) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute($params);
                $success_msg = "✅ ເພີ່ມຂໍ້ມູນ Template ສຳເລັດ!";
            } else {
                // UPDATE
                $sql = "UPDATE ai_templates SET title=?, cost_price=?, price=?, system_prompt=?, form_config=?, preview_image=?, is_active=?, text_config=? WHERE id=?";
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);
                $success_msg = "✅ ບັນທຶກການແກ້ໄຂສຳເລັດ!";
            }
        }
        elseif ($_POST['action'] == 'delete') {
            $pdo->prepare("DELETE FROM ai_templates WHERE id=?")->execute([$_POST['id']]);
            $success_msg = "🗑️ ລົບຂໍ້ມູນຮຽບຮ້ອຍ!";
        }

    } catch (Exception $e) {
        $error_msg = "❌ Error: " . $e->getMessage();
    }
}

// ດຶງຂໍ້ມູນທັງໝົດ
$templates = $pdo->query("SELECT * FROM ai_templates ORDER BY id DESC")->fetchAll();
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
        /* 🔥 Theme Config: Dark Blue/Slate (ຕາມຮູບທີ 2) */
        :root {
            --bg-body: #0f172a;       /* ສີພື້ນຫຼັງຫຼັກ (Dark Slate) */
            --bg-sidebar: #1e293b;    /* ສີ Sidebar */
            --bg-card: #1e293b;       /* ສີກ່ອງ */
            --text-main: #f8fafc;     /* ສີຕົວໜັງສືຫຼັກ */
            --text-muted: #94a3b8;    /* ສີຕົວໜັງສືຮອງ */
            --primary: #3b82f6;       /* ສີຟ້າ Neon */
            --border-color: rgba(255,255,255,0.08);
        }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main); 
            font-family: 'Phetsarath OT', sans-serif; 
            overflow-x: hidden; 
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px; height: 100vh; position: fixed; top: 0; left: 0;
            background-color: var(--bg-sidebar); border-right: 1px solid var(--border-color);
            z-index: 1000; padding-top: 20px;
        }
        .sidebar-brand {
            font-size: 1.4rem; font-weight: bold; color: white; 
            padding: 0 20px 20px; display: block; text-decoration: none;
        }
        .sidebar-brand i { color: var(--primary); }
        
        .nav-link { 
            color: var(--text-muted); padding: 12px 20px; font-size: 0.95rem; 
            display: flex; align-items: center; transition: 0.3s;
        }
        .nav-link i { width: 25px; text-align: center; margin-right: 10px; }
        .nav-link:hover, .nav-link.active { 
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15), transparent); 
            color: var(--primary); border-left: 3px solid var(--primary);
        }

        /* Main Content */
        .main-content { margin-left: 250px; padding: 30px; }

        /* Card & Table Design */
        .custom-card {
            background-color: var(--bg-card); border-radius: 12px;
            border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-dark-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-dark-custom thead th { 
            background-color: #0f172a; color: var(--text-muted); 
            padding: 15px; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }
        .table-dark-custom tbody td { 
            padding: 15px; vertical-align: middle; 
            border-bottom: 1px solid var(--border-color); color: var(--text-main);
        }
        .table-dark-custom tbody tr:hover { background-color: rgba(255,255,255,0.02); }
        
        .img-preview-sm { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); }

        /* Buttons */
        .btn-primary { background-color: var(--primary); border: none; }
        .btn-primary:hover { background-color: #2563eb; }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; }

        /* Modal Customization (Dark Theme) */
        .modal-content { background-color: #1e293b; border: 1px solid var(--border-color); color: white; }
        .modal-header, .modal-footer { border-color: var(--border-color); }
        .btn-close { filter: invert(1); }

        /* Inputs */
        .form-control, .form-select {
            background-color: #0f172a; border: 1px solid #334155; color: white;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0f172a; border-color: var(--primary); color: white; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }

        /* Dynamic Field Builder Area */
        .builder-container {
            background: #0f172a; border: 1px dashed #475569; border-radius: 8px;
            min-height: 250px; max-height: 450px; overflow-y: auto; padding: 10px;
        }
        .field-item {
            background: #1e293b; border: 1px solid var(--border-color); border-radius: 8px;
            padding: 10px; margin-bottom: 10px; position: relative;
        }
        .key-badge {
            cursor: pointer; background: #334155; padding: 4px 8px; border-radius: 4px;
            font-size: 0.8rem; margin-right: 5px; margin-bottom: 5px; display: inline-block;
            border: 1px solid transparent; transition: 0.2s;
        }
        .key-badge:hover { border-color: #eab308; color: #eab308; }

        /* Prompt Box */
        textarea.prompt-box {
            font-family: 'Courier New', monospace; font-size: 0.9rem; line-height: 1.5;
            background: #0b1120; color: #4ade80; border: 1px solid #334155;
        }

        /* Profit Display */
        .profit-box { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; padding: 10px; border-radius: 8px; text-align: center; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="#" class="sidebar-brand"><i class="fas fa-robot"></i> Admin Panel</a>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-chart-pie"></i> ພາບລວມ</a>
        <a class="nav-link active" href="templates.php"><i class="fas fa-images"></i> ຈັດການ Template</a>
        <a class="nav-link" href="users.php"><i class="fas fa-users"></i> ສະມາຊິກ</a>
        <a class="nav-link mt-5 text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> ອອກຈາກລະບົບ</a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">ຈັດການ Template & ກຳໄລ</h3>
            <span class="text-muted small">ຕັ້ງຄ່າລາຄາ, ຕົ້ນທຶນ ແລະ ຮູບແບບຟອມ (Dynamic Forms)</span>
        </div>
        <button class="btn btn-primary" onclick="openModal()">
            <i class="fas fa-plus me-2"></i> ເພີ່ມ Template ໃໝ່
        </button>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success border-0 bg-success bg-opacity-25 text-white">
            <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
        </div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-25 text-white">
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
                    ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?= $tpl['id'] ?></td>
                        <td>
                            <?php if(!empty($tpl['preview_image'])): ?>
                                <img src="../<?= $tpl['preview_image'] ?>" class="img-preview-sm">
                            <?php else: ?>
                                <div class="img-preview-sm d-flex align-items-center justify-content-center bg-dark text-muted border-secondary" style="font-size:10px;">No Pic</div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-info"><?= htmlspecialchars($tpl['title']) ?></td>
                        <td class="text-secondary"><?= number_format($tpl['cost_price']) ?></td>
                        <td class="text-warning fw-bold"><?= number_format($tpl['price']) ?></td>
                        <td>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                +<?= number_format($profit) ?> ₭
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary text-light border border-secondary"><?= count($fields) ?> ຊ່ອງ</span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-primary btn-action me-1" onclick='editTemplate(<?= json_encode($tpl) ?>)'>
                                <i class="fas fa-edit small"></i>
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('ຕ້ອງການລຶບແທ້ບໍ່?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $tpl['id'] ?>">
                                <button class="btn btn-danger btn-action"><i class="fas fa-trash small"></i></button>
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
                    <h5 class="modal-title fw-bold" id="modalTitle">ເພີ່ມ Template ໃໝ່</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="inpId">
                    <input type="hidden" name="current_image" id="inpCurrImg">
                    <input type="hidden" name="form_config_json" id="inpJson" value="[]">
                    <input type="hidden" name="text_config" id="inpTextConfig" value="{}">

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle me-2"></i> 1. ຂໍ້ມູນທົ່ວໄປ</h6>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small">ຊື່ Template</label>
                                <input type="text" class="form-control" name="title" id="inpTitle" placeholder="ຕົວຢ່າງ: ປ້າຍໂຄສະນາເກມ" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small">ຕົ້ນທຶນ (API Cost)</label>
                                    <input type="number" class="form-control" name="cost_price" id="inpCost" value="0" onkeyup="calcProfit()" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-warning small">ລາຄາຂາຍ (Price)</label>
                                    <input type="number" class="form-control" name="price" id="inpPrice" value="5000" onkeyup="calcProfit()" required>
                                </div>
                            </div>

                            <div class="mb-3 profit-box">
                                <small class="d-block text-uppercase fw-bold" style="font-size: 0.7rem;">ກຳໄລຄາດຄະເນ (Profit)</small>
                                <h3 class="m-0 fw-bold" id="showProfit">0 ₭</h3>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small">ຮູບໜ້າປົກ</label>
                                <input type="file" class="form-control" name="preview_image">
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small">ສະຖານະ</label>
                                <select class="form-select" name="is_active" id="inpActive">
                                    <option value="1">🟢 ເປີດໃຊ້ງານ</option>
                                    <option value="0">🔴 ປິດ</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-warning fw-bold m-0"><i class="fas fa-tools me-2"></i> 2. ສ້າງ Input ໃຫ້ລູກຄ້າ</h6>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="addField()">+ ເພີ່ມຊ່ອງ</button>
                            </div>
                            
                            <div id="fieldsContainer" class="builder-container">
                                </div>
                        </div>

                        <div class="col-lg-4 d-flex flex-column">
                            <h6 class="text-danger fw-bold mb-3"><i class="fas fa-robot me-2"></i> 3. ຄຳສັ່ງ AI (Prompt)</h6>
                            
                            <div class="mb-2">
                                <span class="text-muted small">ຄິກເພື່ອແຊກຕົວແປ:</span>
                                <div id="keyBadges" class="mt-1"></div>
                            </div>

                            <textarea name="system_prompt" id="inpPrompt" class="form-control prompt-box flex-grow-1" required placeholder="Design a banner for {{gamename}}..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="button" class="btn btn-primary px-4" onclick="submitForm()">ບັນທຶກຂໍ້ມູນ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    let fields = []; 

    // 1. ຄຳນວນກຳໄລ
    function calcProfit() {
        let cost = parseFloat(document.getElementById('inpCost').value) || 0;
        let price = parseFloat(document.getElementById('inpPrice').value) || 0;
        let profit = price - cost;
        
        let el = document.getElementById('showProfit');
        el.innerText = new Intl.NumberFormat().format(profit) + ' ₭';
        
        if(profit < 0) {
            el.parentElement.style.color = '#ef4444';
            el.parentElement.style.borderColor = '#ef4444';
            el.parentElement.style.background = 'rgba(239, 68, 68, 0.1)';
        } else {
            el.parentElement.style.color = '#10b981';
            el.parentElement.style.borderColor = '#10b981';
            el.parentElement.style.background = 'rgba(16, 185, 129, 0.1)';
        }
    }

    // 2. ຈັດການ Modal
    function openModal() {
        document.getElementById('modalTitle').innerText = 'ເພີ່ມ Template ໃໝ່';
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

    function editTemplate(data) {
        document.getElementById('modalTitle').innerText = 'ແກ້ໄຂ Template';
        document.getElementById('inpId').value = data.id;
        document.getElementById('inpTitle').value = data.title;
        document.getElementById('inpCost').value = data.cost_price;
        document.getElementById('inpPrice').value = data.price;
        document.getElementById('inpPrompt').value = data.system_prompt;
        document.getElementById('inpActive').value = data.is_active;
        document.getElementById('inpCurrImg').value = data.preview_image;

        try {
            fields = JSON.parse(data.form_config);
            if(!Array.isArray(fields)) fields = [];
        } catch(e) { fields = []; }
        
        renderFields();
        calcProfit();
        modal.show();
    }

    // 3. Dynamic Fields Logic (Fix Focus Loss)
    function addField() {
        const c = fields.length + 1;
        fields.push({ label: "Input " + c, key: "input" + c, type: "text" });
        renderFields();
    }

    function removeField(index) {
        fields.splice(index, 1);
        renderFields();
    }

    // Render Fields: ສ້າງ HTML ຈາກ Array
    function renderFields() {
        const container = document.getElementById('fieldsContainer');
        const badgeContainer = document.getElementById('keyBadges');
        
        container.innerHTML = "";
        badgeContainer.innerHTML = "";

        if (fields.length === 0) {
            container.innerHTML = `<div class="text-center text-muted py-5"><i class="fas fa-box-open fa-2x mb-2"></i><br>ຍັງບໍ່ມີຊ່ອງຂໍ້ມູນ</div>`;
        }

        fields.forEach((f, index) => {
            // A. Create Badge
            const badge = document.createElement('span');
            badge.className = "key-badge";
            badge.innerText = "{{" + f.key + "}}";
            badge.onmousedown = (e) => { 
                e.preventDefault(); 
                insertAtCursor(document.getElementById('inpPrompt'), "{{" + f.key + "}}"); 
            };
            badgeContainer.appendChild(badge);

            // B. Create Field Card
            const item = document.createElement('div');
            item.className = "field-item";
            item.innerHTML = `
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-primary">Input #${index + 1}</span>
                    <i class="fas fa-times text-danger" style="cursor:pointer" onclick="removeField(${index})"></i>
                </div>
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Label Name" 
                        value="${f.label}" oninput="updateField(${index}, 'label', this.value)">
                </div>
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-secondary text-white border-secondary">KEY</span>
                    <input type="text" class="form-control" placeholder="unique_key" 
                        value="${f.key}" oninput="updateField(${index}, 'key', this.value)">
                </div>
                <select class="form-select form-select-sm" onchange="updateField(${index}, 'type', this.value)">
                    <option value="text" ${f.type === 'text' ? 'selected' : ''}>Text (ຂໍ້ຄວາມ)</option>
                    <option value="number" ${f.type === 'number' ? 'selected' : ''}>Number (ຕົວເລກ)</option>
                    <option value="image" ${f.type === 'image' ? 'selected' : ''}>Image (ຮູບພາບ)</option>
                    <option value="textarea" ${f.type === 'textarea' ? 'selected' : ''}>Long Text</option>
                </select>
            `;
            container.appendChild(item);
        });
    }

    // Update Array without Re-rendering HTML (Fixes focus loss)
    function updateField(index, prop, val) {
        fields[index][prop] = val;
        // ຖ້າແກ້ໄຂ Key ໃຫ້ອັບເດດ Badge ນຳ
        if(prop === 'key') {
            const badges = document.getElementById('keyBadges').children;
            if(badges[index]) badges[index].innerText = "{{" + val + "}}";
        }
    }

    // 4. Insert at Cursor
    function insertAtCursor(myField, myValue) {
        myField.focus();
        if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
            myField.selectionStart = startPos + myValue.length;
            myField.selectionEnd = startPos + myValue.length;
        } else {
            myField.value += myValue;
        }
    }

    // 5. Submit
    function submitForm() {
        document.getElementById('inpJson').value = JSON.stringify(fields);
        document.getElementById('mainForm').submit();
    }
</script>

</body>
</html>