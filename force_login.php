<?php
// force_login.php
session_start();
require_once 'config/database.php';

// 1. ກຳນົດ ID ທີ່ທ່ານຕ້ອງການໃຊ້ (ຕ້ອງມີໃນ Database)
$my_user_id = 1; 

// 2. ສ້າງຂໍ້ມູນ User ປອມໃນ Database ຖ້າຍັງບໍ່ມີ (ກັນພາດ)
// ກວດສອບກ່ອນວ່າມີ User ນີ້ບໍ່
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$my_user_id]);
$user = $stmt->fetch();

if (!$user) {
    // ຖ້າບໍ່ມີ ໃຫ້ສ້າງ Admin ຂຶ້ນມາເລີຍ
    $sql = "INSERT INTO users (id, google_id, fullname, email, role, credit, avatar) 
            VALUES (?, 'dev_admin', 'Developer Admin', 'admin@laoai.com', 'admin', 999999, 'https://ui-avatars.com/api/?name=Admin')";
    $pdo->prepare($sql)->execute([$my_user_id]);
    echo "<h3>✅ ສ້າງ User ID $my_user_id (Admin) ລົງ Database ແລ້ວ!</h3>";
} else {
    // ຖ້າມີແລ້ວ ໃຫ້ອັບເດດເປັນ Admin
    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$my_user_id]);
    echo "<h3>✅ ອັບເດດ User ID $my_user_id ໃຫ້ເປັນ Admin ແລ້ວ!</h3>";
}

// 3. ຍັດຄ່າໃສ່ Session (ບັງຄັບ Login)
$_SESSION['user_id'] = $my_user_id;
$_SESSION['fullname'] = 'Developer Admin';
$_SESSION['role'] = 'admin';  // <--- ຈຸດສຳຄັນ!
$_SESSION['avatar'] = 'https://ui-avatars.com/api/?name=Admin';
$_SESSION['credit'] = 999999;

echo "<hr>";
echo "<h1 style='color:green'>🎉 Force Login ສຳເລັດ!</h1>";
echo "<h3>ສະຖານະຕອນນີ້: <span style='color:blue'>ADMIN</span></h3>";
echo "<br>";
echo "<a href='admin/index.php' style='font-size: 20px; font-weight: bold;'>👉 ຄລິກບ່ອນນີ້ເພື່ອເຂົ້າ Admin Dashboard</a>";
?>