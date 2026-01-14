// assets/js/ai_shop.js
// ລະບົບຈັດການໜ້າຮ້ານ AI Shop (Dynamic Form & Auto Polling)

document.addEventListener('DOMContentLoaded', function() {
    // ປະກາດຕົວແປ Modal ໄວ້ໃຊ້ງານ
    const genModal = new bootstrap.Modal(document.getElementById('genModal'));
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    const resultModal = new bootstrap.Modal(document.getElementById('resultModal'));

    // ==========================================
    // 1. ຟັງຊັນເປີດ Modal ແລະ ສ້າງ Input ແບບ Dynamic
    // ==========================================
    window.openGenerateModal = function(id, title, price, fieldsJsonString) {
        // ຕັ້ງຄ່າຂໍ້ມູນພື້ນຖານ
        document.getElementById('tplId').value = id;
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalPrice').innerText = new Intl.NumberFormat().format(price);
        document.getElementById('aiForm').reset();

        // 1. ແປງ JSON String ທີ່ສົ່ງມາຈາກ PHP ໃຫ້ເປັນ Object
        let fields = [];
        try {
            // ຖ້າເປັນ string ວ່າງ ຫຼື null ໃຫ້ເປັນ array ວ່າງ
            if (fieldsJsonString && fieldsJsonString !== 'null') {
                fields = JSON.parse(fieldsJsonString);
            }
        } catch (e) {
            console.error("JSON Parse Error:", e);
            fields = [];
        }

        // 2. ເຄລຍ Input ເກົ່າ ແລະ ເລີ່ມສ້າງໃໝ່
        const container = document.getElementById('dynamicFieldsContainer');
        container.innerHTML = ''; 

        if (fields.length > 0) {
            // ຫົວຂໍ້ແບ່ງສ່ວນ
            const header = document.createElement('div');
            header.className = 'text-warning small mb-2 border-bottom border-secondary pb-1';
            header.innerText = '-- ປັບແຕ່ງຂໍ້ມູນ (Inputs) --';
            container.appendChild(header);

            // ວົນລູບສ້າງ Input ຕາມ Config
            fields.forEach(field => {
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-3';

                // ສ້າງ Label
                const label = document.createElement('label');
                label.className = 'form-label text-info small fw-bold';
                label.innerText = field.label || field.key;
                wrapper.appendChild(label);

                let input;

                // ກວດສອບປະເພດ Input (Type)
                if (field.type === 'select') {
                    // ແບບເລືອກ (Dropdown)
                    input = document.createElement('select');
                    input.className = 'form-select form-select-dark';
                    (field.options || []).forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt;
                        option.innerText = opt;
                        if (opt === field.default) option.selected = true;
                        input.appendChild(option);
                    });
                } 
                else if (field.type === 'image') {
                    // 🔥 ແບບອັບໂຫລດຮູບ (File)
                    input = document.createElement('input');
                    input.type = 'file';
                    input.className = 'form-control form-control-dark';
                    input.accept = 'image/png, image/jpeg, image/jpg';
                } 
                else if (field.type === 'textarea') {
                    // ແບບຂໍ້ຄວາມຍາວ
                    input = document.createElement('textarea');
                    input.className = 'form-control form-control-dark';
                    input.rows = 3;
                    if(field.placeholder) input.placeholder = field.placeholder;
                    if(field.default) input.value = field.default;
                }
                else {
                    // ປົກກະຕິ (Text, Number)
                    input = document.createElement('input');
                    input.type = field.type || 'text';
                    input.className = 'form-control form-control-dark';
                    if(field.placeholder) input.placeholder = field.placeholder;
                    if(field.default) input.value = field.default;
                }

                // *** ສຳຄັນ: ຕັ້ງຊື່ dynamic_{key} ໃຫ້ກົງກັບ PHP ***
                input.name = 'dynamic_' + field.key;
                
                // ເພີ່ມເຂົ້າໃນ Form
                wrapper.appendChild(input);
                container.appendChild(wrapper);
            });
        } else {
            container.innerHTML = '<small class="text-secondary d-block mb-3">ບໍ່ມີການປັບແຕ່ງເພີ່ມເຕີມ</small>';
        }

        genModal.show();
    };

    // ==========================================
    // 2. ຈັດການ Submit Form (ສົ່ງຂໍ້ມູນໄປ API)
    // ==========================================
    document.getElementById('aiForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // ປິດຟອມ ແລ້ວເປີດ Loading
        genModal.hide();
        loadingModal.show();
        
        const formData = new FormData(this); // ເກັບຂໍ້ມູນທັງໝົດໃນຟອມ (ລວມທັງຮູບ)
        
        fetch('api/process_image.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'processing') {
                console.log('Order Created ID:', data.order_id);
                // ໄດ້ Order ID ແລ້ວ -> ເລີ່ມວົນຖາມສະຖານະ
                startPolling(data.order_id);
            } else {
                loadingModal.hide();
                alert('ເກີດຂໍ້ຜິດພາດ: ' + (data.message || 'Unknown Error'));
                // ເປີດ Modal ຄືນຖ້າຜິດພາດ
                genModal.show();
            }
        })
        .catch(err => {
            loadingModal.hide();
            console.error(err);
            alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ (Network Error)');
        });
    });

    // ==========================================
    // 3. ລະບົບ Polling (ວົນຖາມສະຖານະ)
    // ==========================================
    function startPolling(orderId) {
        let attempts = 0;
        const maxAttempts = 100; // ປະມານ 5 ນາທີ (3s * 100)

        const interval = setInterval(() => {
            attempts++;
            
            fetch(`api/check_status.php?order_id=${orderId}`)
            .then(res => res.json())
            .then(data => {
                console.log(`Polling #${attempts}:`, data.status);

                if(data.status === 'completed') {
                    // ✅ ສຳເລັດ
                    clearInterval(interval);
                    loadingModal.hide();
                    showResult(data.image);

                    // ເມື່ອປິດ Modal ໃຫ້ Refresh ໜ້າເວັບເພື່ອອັບເດດເຄຣດິດ/ປະຫວັດ
                    document.getElementById('resultModal').addEventListener('hidden.bs.modal', function () {
                        location.reload();
                    }, { once: true }); // once: true ເພື່ອບໍ່ໃຫ້ bind ຊ້ຳ

                } else if(data.status === 'failed') {
                    // ❌ ລົ້ມເຫຼວ
                    clearInterval(interval);
                    loadingModal.hide();
                    alert('AI ແຈ້ງເຕືອນ: ' + (data.message || 'ສ້າງຮູບບໍ່ສຳເລັດ'));
                    location.reload();
                }

                // ⏰ ໝົດເວລາ
                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    loadingModal.hide();
                    alert('Timeout: ໃຊ້ເວລາດົນຜິດປົກກະຕິ ກະລຸນາກວດສອບທີ່ປະຫວັດພາຍຫຼັງ');
                    location.reload();
                }
            })
            .catch(err => {
                console.error("Polling Error:", err);
                // ບໍ່ຢຸດ interval ເພື່ອໃຫ້ໂອກາດລອງໃໝ່
            });
        }, 3000); // ຖາມທຸກໆ 3 ວິນາທີ
    }

    // ==========================================
    // 4. ຟັງຊັນສະແດງຜົນລັບ (Helper)
    // ==========================================
    window.showResult = function(path) {
        // ຕື່ມ random query string ເພື່ອປ້ອງກັນ Cache
        const noCachePath = path + '?t=' + new Date().getTime();
        document.getElementById('resultImage').src = noCachePath;
        document.getElementById('downloadBtn').href = path; // ລິ້ງດາວໂຫລດໃຊ້ path ປົກກະຕິ
        resultModal.show();
    };
});