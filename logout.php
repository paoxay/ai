<?php
session_start();
session_destroy(); // ລ້າງຂໍ້ມູນທຸກຢ່າງ
header("Location: login.php"); // ກັບໄປໜ້າ Login
exit;
?>