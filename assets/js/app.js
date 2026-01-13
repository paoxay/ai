// assets/js/app.js

let genModal;
let resultModal;

document.addEventListener('DOMContentLoaded', function() {
    genModal = new bootstrap.Modal(document.getElementById('genModal'));
    resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
});

// ເປີດຟອມເມື່ອຄລິກສ້າງ
function openGenerateModal(id, title, price) {
    document.getElementById('tplId').value = id;
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalPrice').innerText = new Intl.NumberFormat().format(price);
    genModal.show();
}

// ຈັດການເມື່ອກົດ Submit
document.getElementById('aiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    genModal.hide();
    document.getElementById('loader').style.display = 'flex';

    // FormData ຈະດຶງທັງ Text ແລະ File ໄປໃຫ້ອັດຕະໂນມັດ
    const formData = new FormData(this); 

    fetch('api/process_image.php', {
        method: 'POST',
        body: formData // ບໍ່ຕ້ອງໃສ່ Header Content-Type, ລະບົບຈັດການເອງ
    })
    .then(response => response.json())
    .then(data => {
        // ປິດ Loading
        document.getElementById('loader').style.display = 'none';

        if (data.status === 'success') {
            // ສະແດງຮູບ
            document.getElementById('finalImage').src = data.image;
            document.getElementById('downloadBtn').href = data.image;
            
            // ອັບເດດຍອດເງິນທັນທີ
            let currentCredit = parseInt(document.getElementById('userCredit').innerText.replace(/,/g, ''));
            let cost = parseInt(document.getElementById('modalPrice').innerText.replace(/,/g, ''));
            document.getElementById('userCredit').innerText = new Intl.NumberFormat().format(currentCredit - cost);

            resultModal.show();
        } else {
            alert('ເກີດຂໍ້ຜິດພາດ: ' + data.message);
        }
    })
    .catch(error => {
        document.getElementById('loader').style.display = 'none';
        alert('ການເຊື່ອມຕໍ່ຜິດພາດ');
        console.error('Error:', error);
    });
});