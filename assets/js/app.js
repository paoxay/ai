// assets/js/app.js

let genModal;
let resultModal;
let pollingInterval = null; // ໂຕແປສຳລັບເກັບ Loop ການກວດສອບ

document.addEventListener('DOMContentLoaded', function() {
    genModal = new bootstrap.Modal(document.getElementById('genModal'));
    resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
});

function openGenerateModal(id, title, price) {
    document.getElementById('tplId').value = id;
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalPrice').innerText = new Intl.NumberFormat().format(price);
    genModal.show();
}

document.getElementById('aiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    genModal.hide();
    
    // ສະແດງ Loading
    const loader = document.getElementById('loader');
    loader.style.display = 'flex';
    
    const formData = new FormData(this);

    fetch('api/process_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'processing') {
            // == 1. ໄດ້ຮັບ Order ID ແລ້ວ ເລີ່ມການກວດສອບ (Polling) ==
            console.log('Order received:', data.order_id);
            startPolling(data.order_id);
            
            // ຕັດເງິນທີ່ສະແດງ (Optional: ເຮັດຫຼືບໍ່ກໍໄດ້ ເພາະ Database ຕັດໄປແລ້ວ)
            updateUserCredit(); 
        } else if (data.status === 'error') {
            loader.style.display = 'none';
            alert('ເກີດຂໍ້ຜິດພາດ: ' + data.message);
        }
    })
    .catch(error => {
        loader.style.display = 'none';
        alert('ການເຊື່ອມຕໍ່ຜິດພາດ');
        console.error('Error:', error);
    });
});

// ຟັງຊັນວົນຖາມສະຖານະ
function startPolling(orderId) {
    let attempts = 0;
    const maxAttempts = 60; // ຖາມສູງສຸດ 60 ຄັ້ງ (ປະມານ 2 ນາທີ)
    
    // ເຄລຍ Interval ເກົ່າຖ້າມີ
    if (pollingInterval) clearInterval(pollingInterval);

    pollingInterval = setInterval(() => {
        attempts++;
        
        // ສົ່ງ order_id ໄປຖາມ check_status.php
        fetch(`api/check_status.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Checking status:', data.status);
            
            if (data.status === 'completed') {
                // == ສຳເລັດ! ==
                clearInterval(pollingInterval);
                document.getElementById('loader').style.display = 'none';
                
                // ສະແດງຮູບ
                document.getElementById('finalImage').src = data.image; // Path ຮູບຈາກ Database
                document.getElementById('downloadBtn').href = data.image;
                resultModal.show();
                
            } else if (data.status === 'failed') {
                // == ລົ້ມເຫຼວ ==
                clearInterval(pollingInterval);
                document.getElementById('loader').style.display = 'none';
                alert('ການສ້າງຮູບລົ້ມເຫຼວ: ' + (data.message || 'Unknown error'));
            }
            
            // ຖ້າ status === 'processing' ກໍວົນຕໍ່ໄປ...
        })
        .catch(err => console.error('Polling error:', err));

        // ຖ້າດົນເກີນໄປ ໃຫ້ຢຸດ
        if (attempts >= maxAttempts) {
            clearInterval(pollingInterval);
            document.getElementById('loader').style.display = 'none';
            alert('ໃຊ້ເວລາສ້າງດົນຜິດປົກກະຕິ ກະລຸນາກວດສອບທີ່ປະຫວັດການສັ່ງຊື້');
        }

    }, 2000); // ຖາມທຸກໆ 2 ວິນາທີ
}

function updateUserCredit() {
    let cost = parseInt(document.getElementById('modalPrice').innerText.replace(/,/g, ''));
    let creditElem = document.getElementById('userCredit');
    let currentCredit = parseInt(creditElem.innerText.replace(/,/g, ''));
    creditElem.innerText = new Intl.NumberFormat().format(currentCredit - cost);
}