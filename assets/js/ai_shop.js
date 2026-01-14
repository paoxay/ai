// assets/js/ai_shop.js
// ລະບົບຈັດການໜ້າຮ້ານ AI Shop (Dynamic Form, Paste Image, Auto Polling)

document.addEventListener('DOMContentLoaded', function() {
    const genModal = new bootstrap.Modal(document.getElementById('genModal'));
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    const resultModal = new bootstrap.Modal(document.getElementById('resultModal'));

    // ຕົວແປເກັບ ID ຂອງຊ່ອງຮູບທີ່ຈະ Paste ໃສ່ (Default ເປັນ null)
    let activePasteId = null;

    // ==========================================
    // 1. ເປີດ Modal ແລະ ສ້າງ Input ແບບ Dynamic
    // ==========================================
    window.openGenerateModal = function(id, title, price, fieldsJsonString) {
        // ຕັ້ງຄ່າຂໍ້ມູນພື້ນຖານ
        document.getElementById('tplId').value = id;
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalPrice').innerText = new Intl.NumberFormat().format(price);
        document.getElementById('aiForm').reset();

        // Reset Paste Target
        activePasteId = null;

        // ແປງ JSON Config ຈາກ Admin
        let fields = [];
        try {
            if (fieldsJsonString && fieldsJsonString !== 'null') {
                fields = JSON.parse(fieldsJsonString);
            }
        } catch (e) {
            console.error("JSON Parse Error:", e);
            fields = [];
        }

        // ສ້າງ Form Inputs
        const container = document.getElementById('dynamicFieldsContainer');
        container.innerHTML = ''; 

        if (fields.length > 0) {
            const header = document.createElement('div');
            header.className = 'text-warning small mb-3 border-bottom border-secondary pb-1';
            header.innerText = '✨ ປັບແຕ່ງຂໍ້ມູນຂອງທ່ານ';
            container.appendChild(header);

            fields.forEach((field, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-4';

                // Label
                const label = document.createElement('label');
                label.className = 'form-label text-info small fw-bold mb-1';
                label.innerText = field.label || field.key;
                wrapper.appendChild(label);

                // --- ກວດສອບປະເພດ Input (Type) ---

                if (field.type === 'image') {
                    // 🔥 ກໍລະນີຮູບພາບ (Upload Zone)
                    const uniqueId = 'file_' + field.key + '_' + index;
                    
                    // ຖ້າມີຊ່ອງຮູບຊ່ອງດຽວ ໃຫ້ Active ເລີຍ (ເພື່ອ Paste ງ່າຍ)
                    if (activePasteId === null) activePasteId = uniqueId;

                    const uploadZone = document.createElement('div');
                    uploadZone.className = 'upload-zone text-center p-3';
                    uploadZone.style.cssText = "border: 2px dashed #475569; border-radius: 10px; cursor: pointer; background: rgba(255,255,255,0.05); transition: 0.3s;";
                    
                    // ເມື່ອຄິກ -> ເປີດ File Dialog ແລະ ຕັ້ງເປັນ Active Target ສຳລັບ Paste
                    uploadZone.onclick = function() { 
                        document.getElementById(uniqueId).click(); 
                        activePasteId = uniqueId;
                    };

                    const content = `
                        <div id="preview_box_${uniqueId}">
                            <i class="fas fa-cloud-upload-alt fa-2x text-secondary mb-2"></i>
                            <div class="text-white-50 small">
                                ຄິກເລືອກຮູບ ຫຼື <span class="badge bg-secondary">Ctrl+V</span>
                            </div>
                        </div>
                        <img id="img_${uniqueId}" class="img-fluid rounded d-none mt-2 shadow-sm" style="max-height: 150px; width: auto;">
                    `;
                    uploadZone.innerHTML = content;

                    // Input File (Hidden)
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.id = uniqueId;
                    input.className = 'd-none';
                    input.accept = 'image/png, image/jpeg, image/jpg';
                    input.name = 'dynamic_' + field.key;
                    
                    // ເມື່ອມີການເລືອກໄຟລ໌ -> ສະແດງ Preview
                    input.addEventListener('change', function() { showPreview(this, uniqueId); });

                    wrapper.appendChild(uploadZone);
                    wrapper.appendChild(input);

                } else if (field.type === 'textarea') {
                    // 🔥 ກໍລະນີຂໍ້ຄວາມຍາວ (Auto Resize)
                    const input = document.createElement('textarea');
                    input.className = 'form-control form-control-dark';
                    input.name = 'dynamic_' + field.key;
                    input.rows = 2;
                    input.placeholder = field.placeholder || '';
                    
                    // Logic ຢືດຫົດ
                    input.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    });
                    wrapper.appendChild(input);

                } else {
                    // ກໍລະນີ Text / Number ທົ່ວໄປ
                    const input = document.createElement('input');
                    input.type = field.type || 'text';
                    input.className = 'form-control form-control-dark py-2';
                    input.name = 'dynamic_' + field.key;
                    input.placeholder = field.placeholder || '';
                    wrapper.appendChild(input);
                }

                container.appendChild(wrapper);
            });
        } else {
            container.innerHTML = '<small class="text-secondary d-block mb-3">ກົດຢືນຢັນເພື່ອສ້າງຮູບໄດ້ເລີຍ</small>';
        }

        genModal.show();
    };

    // ==========================================
    // Helper: ສະແດງຮູບ Preview
    // ==========================================
    function showPreview(input, id) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview_box_' + id).classList.add('d-none');
                const img = document.getElementById('img_' + id);
                img.src = e.target.result;
                img.classList.remove('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ==========================================
    // 2. Global Event: Paste Image (Ctrl+V)
    // ==========================================
    window.addEventListener('paste', function(e) {
        // ເຮັດວຽກສະເພາະຕອນ Modal ເປີດຢູ່
        if (!document.getElementById('genModal').classList.contains('show')) return;
        
        // ຖ້າບໍ່ມີຊ່ອງຮູບເລີຍ -> ຈົບ
        if (!activePasteId) return;

        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                const input = document.getElementById(activePasteId);
                
                // ສ້າງ FileList ໃໝ່ຍັດໃສ່ Input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(blob);
                input.files = dataTransfer.files;

                // ສະແດງຜົນ
                showPreview(input, activePasteId);
                break; // ເອົາຮູບດຽວ
            }
        }
    });

    // ==========================================
    // 3. Submit Form
    // ==========================================
    document.getElementById('aiForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        genModal.hide();
        loadingModal.show();
        
        const formData = new FormData(this);
        
        fetch('api/process_image.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'processing') {
                startPolling(data.order_id);
            } else {
                loadingModal.hide();
                alert('ແຈ້ງເຕືອນ: ' + (data.message || 'ເກີດຂໍ້ຜິດພາດ'));
                genModal.show();
            }
        })
        .catch(err => {
            loadingModal.hide();
            alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ (Network Error)');
        });
    });

    // ==========================================
    // 4. Polling (ວົນຖາມສະຖານະ)
    // ==========================================
    function startPolling(orderId) {
        let attempts = 0;
        const maxAttempts = 100;

        const interval = setInterval(() => {
            attempts++;
            fetch(`api/check_status.php?order_id=${orderId}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'completed') {
                    clearInterval(interval);
                    loadingModal.hide();
                    showResult(data.image);
                    // Refresh ໜ້າເມື່ອປິດ Modal
                    document.getElementById('resultModal').addEventListener('hidden.bs.modal', () => location.reload(), { once: true });
                } else if(data.status === 'failed') {
                    clearInterval(interval);
                    loadingModal.hide();
                    alert('AI ແຈ້ງເຕືອນ: ສ້າງບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່');
                    location.reload();
                }
                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    loadingModal.hide();
                    alert('Timeout: ໃຊ້ເວລາດົນຜິດປົກກະຕິ');
                    location.reload();
                }
            })
            .catch(err => console.error(err));
        }, 3000);
    }

    // ==========================================
    // 5. ສະແດງຜົນລັບ
    // ==========================================
    window.showResult = function(path) {
        const noCachePath = path + '?t=' + new Date().getTime();
        document.getElementById('resultImage').src = noCachePath;
        document.getElementById('downloadBtn').href = path;
        resultModal.show();
    };
});