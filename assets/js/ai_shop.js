// assets/js/ai_shop.js
document.addEventListener('DOMContentLoaded', function() {
    const genModal = new bootstrap.Modal(document.getElementById('genModal'));
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    const resultModal = new bootstrap.Modal(document.getElementById('resultModal'));

    // ຕົວແປເກັບໄຟລ໌ທັງໝົດ (ສຳລັບທຸກ Input ID)
    const fileStore = {}; 

    // 1. ຟັງຊັນເປີດ Modal
    window.openGenerateModal = function(id, title, price, fieldsJsonString) {
        document.getElementById('tplId').value = id;
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalPrice').innerText = new Intl.NumberFormat().format(price);
        document.getElementById('aiForm').reset();

        // ລ້າງຂໍ້ມູນໄຟລ໌ເກົ່າ
        for (let key in fileStore) delete fileStore[key];

        let fields = [];
        try {
            if (fieldsJsonString && fieldsJsonString !== 'null') fields = JSON.parse(fieldsJsonString);
        } catch (e) { console.error("JSON Error:", e); }

        const container = document.getElementById('dynamicFieldsContainer');
        container.innerHTML = ''; 

        if (fields.length > 0) {
            fields.forEach((field, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-3';
                
                // Label
                const label = document.createElement('label');
                label.className = 'form-label text-info small fw-bold mb-1';
                label.innerText = field.label || field.key;
                wrapper.appendChild(label);

                if (field.type === 'image') {
                    // --- ສ້າງ UI ອັບໂຫລດຮູບ (Multi-Upload) ---
                    const uniqueId = `file_${field.key}_${index}`;
                    
                    const uploadBox = document.createElement('div');
                    uploadBox.className = 'upload-container';
                    uploadBox.innerHTML = `
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <div class="text-white-50 small">ຄິກ ຫຼື ລາກຮູບໃສ່ນີ້ (ໄດ້ຫຼາຍຮູບ)</div>
                    `;
                    uploadBox.onclick = () => document.getElementById(uniqueId).click();

                    const input = document.createElement('input');
                    input.type = 'file';
                    input.id = uniqueId;
                    input.name = `dynamic_${field.key}[]`; // ຕ້ອງມີ [] ເພື່ອສົ່ງແບບ Array
                    input.className = 'd-none';
                    input.accept = 'image/*';
                    input.multiple = true; // ອະນຸຍາດຫຼາຍໄຟລ໌
                    input.onchange = (e) => handleFileSelect(e.target, uniqueId);

                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'preview-grid';
                    previewDiv.id = `preview_${uniqueId}`;

                    wrapper.appendChild(uploadBox);
                    wrapper.appendChild(input);
                    wrapper.appendChild(previewDiv);
                    
                    // Init FileStore
                    fileStore[uniqueId] = new DataTransfer();

                } else if (field.type === 'textarea') {
                    const input = document.createElement('textarea');
                    input.className = 'form-control form-control-dark';
                    input.name = `dynamic_${field.key}`;
                    input.rows = 2;
                    wrapper.appendChild(input);
                } else {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control form-control-dark';
                    input.name = `dynamic_${field.key}`;
                    wrapper.appendChild(input);
                }
                container.appendChild(wrapper);
            });
        }
        genModal.show();
    };

    // 2. ຈັດການເມື່ອເລືອກໄຟລ໌
    window.handleFileSelect = function(input, uniqueId) {
        const files = input.files;
        const dt = fileStore[uniqueId];

        for (let i = 0; i < files.length; i++) {
            dt.items.add(files[i]); // ເພີ່ມໄຟລ໌ເຂົ້າ Store
        }
        input.files = dt.files; // ອັບເດດ Input
        renderPreview(uniqueId);
    };

    // 3. ສະແດງຮູບຕົວຢ່າງ
    function renderPreview(uniqueId) {
        const dt = fileStore[uniqueId];
        const container = document.getElementById(`preview_${uniqueId}`);
        container.innerHTML = '';

        Array.from(dt.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}">
                    <button type="button" class="btn-remove-img" onclick="removeFile('${uniqueId}', ${index})">×</button>
                `;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    // 4. ລົບຮູບ
    window.removeFile = function(uniqueId, index) {
        const dt = fileStore[uniqueId];
        const newDt = new DataTransfer();
        
        Array.from(dt.files).forEach((file, i) => {
            if (i !== index) newDt.items.add(file);
        });
        
        fileStore[uniqueId] = newDt;
        document.getElementById(uniqueId).files = newDt.files;
        renderPreview(uniqueId);
    };

    // 5. Submit Form
    document.getElementById('aiForm').addEventListener('submit', function(e) {
        e.preventDefault();
        genModal.hide();
        loadingModal.show();
        
        const formData = new FormData(this);
        
        fetch('api/process_image.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'processing') {
                startPolling(data.order_id);
            } else {
                loadingModal.hide();
                alert('Error: ' + data.message);
                genModal.show();
            }
        })
        .catch(() => { loadingModal.hide(); alert('Network Error'); });
    });

    // 6. Polling & Show Result
    function startPolling(orderId) {
        const interval = setInterval(() => {
            fetch(`api/check_status.php?order_id=${orderId}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'completed') {
                    clearInterval(interval);
                    loadingModal.hide();
                    showResult(data.image);
                } else if(data.status === 'failed') {
                    clearInterval(interval);
                    loadingModal.hide();
                    alert('AI Failed');
                }
            });
        }, 3000);
    }

    window.showResult = function(path) {
        document.getElementById('resultImage').src = path + '?t=' + Date.now();
        document.getElementById('downloadBtn').href = path;
        resultModal.show();
    };
});