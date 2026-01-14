<?php
// check_admin.php
session_start();

echo "<h3>🔍 ຜົນການກວດສອບສະຖານະ:</h3>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>❌ ບໍ່ພົບ User ID (ທ່ານຍັງບໍ່ໄດ້ Login)</p>";
    echo "<a href='login.php'>ໄປ Login ກ່ອນ</a>";
} else {
    echo "<p style='color:green;'>✅ User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>ຊື່: " . $_SESSION['fullname'] . "</p>";
    
    // ກວດສອບ Role
    if (isset($_SESSION['role'])) {
        echo "<p>ສະຖານະປັດຈຸບັນ (Role): <strong>" . $_SESSION['role'] . "</strong></p>";
        
        if ($_SESSION['role'] === 'admin') {
            echo "<h2 style='color:green;'>🎉 ຍິນດີນຳ! ທ່ານເປັນ Admin ແລ້ວ</h2>";
            echo "<a href='admin/index.php'>ຄລິກເພື່ອເຂົ້າໜ້າ Admin</a>";
        } else {
            echo "<h2 style='color:red;'>⛔ ທ່ານຍັງເປັນ User ທຳມະດາຢູ່</h2>";
            echo "<p>ວິທີແກ້: ໃຫ້ Logout ແລ້ວ Login ໃໝ່, ຫຼືແກ້ Database ແລ້ວ Refresh Dashboard ອີກຮອບ.</p>";
        }
    } else {
        echo "<p style='color:orange;'>⚠️ ບໍ່ພົບຄ່າ Role ໃນ Session (ກະລຸນາໄປໜ້າ Dashboard ເພື່ອອັບເດດ)</p>";
    }
}
?>