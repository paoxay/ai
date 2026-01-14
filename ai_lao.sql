-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 14, 2026 at 10:44 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ai_lao`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_templates`
--

CREATE TABLE `ai_templates` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `model_key` varchar(50) DEFAULT 'nano-banana-pro',
  `system_prompt` text DEFAULT NULL,
  `text_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`text_config`)),
  `price` decimal(10,2) DEFAULT 5000.00,
  `image_preview` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `preview_image` varchar(255) DEFAULT NULL,
  `form_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`form_config`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_templates`
--

INSERT INTO `ai_templates` (`id`, `title`, `cost_price`, `model_key`, `system_prompt`, `text_config`, `price`, `image_preview`, `is_active`, `preview_image`, `form_config`) VALUES
(3, 'ແຕ່ງປ້າຍລາຄາເກມ', 890.00, 'nano-banana-pro', 'แต่งรูปป้ายแบนเนอ์ ads เกม {{gamename}}\r\nข้อความประกาศ {{topup-center}}\r\nสินค้าราคาแพคเกจ {{input3}}\r\nข้อความปิดท้าย {{input6}}\r\n\r\n\r\n*แต่งรูปป้ายราคาเกม แบบมืออาชีพ*\r\n*ในป้ายเป็นภาษาลาว,นำข้อความจาก input user ให้มา นำมาป้อนไปแต่งใส่เนื้อหาในรูป*\r\n* stye ให้ ai ใส่ stye ตามใจที่เหมาะสม กับหน้างานนั้นๆ และ สังเกตดู logo ร้านค้าของ  user ', '{}', 5000.00, NULL, 1, 'assets/images/1768314542_1768272821480-r7ouq9c4zoo.png', '[{\"label\":\"ຊື່ເກມ\",\"key\":\"gamename\",\"type\":\"text\"},{\"label\":\"ຫົວຂໍ້(ຫນັງສືຕົວໜາໃຫຍ່ກາງປ້າຍ)\",\"key\":\"topup-center\",\"type\":\"text\"},{\"label\":\"ເນື້ອຫາ\",\"key\":\"input3\",\"type\":\"textarea\"},{\"label\":\"ຂໍ້ຄວາມປິດທ້າຍ\",\"key\":\"input6\",\"type\":\"text\"},{\"label\":\"ຮູບພາບ logo\",\"key\":\"key_5\",\"type\":\"image\",\"placeholder\":\"\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `task_id` varchar(100) DEFAULT NULL,
  `user_inputs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_inputs`)),
  `final_image_path` varchar(255) DEFAULT NULL,
  `status` enum('processing','completed','failed') DEFAULT 'processing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_logo_path` varchar(255) DEFAULT NULL,
  `user_text_title` varchar(255) DEFAULT NULL,
  `user_text_price` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `template_id`, `task_id`, `user_inputs`, `final_image_path`, `status`, `created_at`, `user_logo_path`, `user_text_title`, `user_text_price`) VALUES
(6, 1, 1, '5904cb3b11708b24546c513f4a1a4329', NULL, 'assets/images/final_6.png', 'completed', '2026-01-13 11:41:01', 'assets/uploads/logo_1768304460_557.jpg', 'free fire', '517ເພັດ 10,000ກີບ'),
(7, 1, 1, 'ef85c54dd025d9dffadcbb742226d5e2', NULL, 'assets/images/final_7.png', 'completed', '2026-01-13 12:00:47', 'assets/uploads/logo_1768305646_833.jpg', 'ໂປຣໂມຊັນຟີຟາຍ', '517ເພັດ 10,000ກີບ'),
(8, 1, 3, 'a6a1c748dd6e815295f6105f4c2489ba', NULL, 'assets/images/final_8.png', 'completed', '2026-01-13 14:37:27', NULL, '{\"gamename\":\"ZEPETO\",\"topup-center\":\"ອັບເດດລາຄາລ່າສຸດ\",\"input3\":\"ລາຄາເລິ້ມຕົ້ນ11,000ກີບ ຮັບ  7 ZEM \",\"input4\":\"\",\"input5\":\"\",\"input6\":\"ສັ່ງຊື້ໄດ້ໃນເວັບ https:\\/\\/ppshope.com\\/\"}', NULL),
(9, 1, 3, 'd8780853dfe20dff1ef654d145d1161f', NULL, 'assets/images/final_9.png', 'completed', '2026-01-13 14:40:10', NULL, '{\"gamename\":\"ZEPETO\",\"topup-center\":\"ອັບເດດລາຄາລ່າສຸດ\",\"input3\":\"ລາຄາເລິ້ມຕົ້ນ11,000ກີບ ຮັບ  7 ZEM \",\"input4\":\"\",\"input5\":\"\",\"input6\":\"ສັ່ງຊື້ໄດ້ໃນເວັບ https:\\/\\/ppshope.com\\/\"}', NULL),
(10, 1, 3, '1d6ede352273068d0cbb125d8b011fbe', NULL, 'assets/images/final_10.png', 'completed', '2026-01-13 14:52:10', NULL, '{\"gamename\":\"ZEPETO\",\"topup-center\":\"ອັບເດດລາຄາລ່າສຸດ\",\"input3\":\"ລາຄາເລິ້ມຕົ້ນ11,000ກີບ ຮັບ  7 ZEM\",\"input6\":\"ສັ່ງຊື້ໄດ້ໃນເວັບ https:\\/\\/ppshope.com\\/\",\"input7\":\"http:\\/\\/localhost:8080\\/assets\\/uploads\\/user_inputs\\/img_1768315929_9332.jpg\"}', NULL),
(11, 1, 3, '6380cae28d55b554fc1842c049dba041', NULL, 'assets/images/final_11.png', 'completed', '2026-01-13 14:57:42', NULL, '{\"gamename\":\"ZEPETO\",\"topup-center\":\"ອັບເດດລາຄາລ່າສຸດ\",\"input3\":\"ລາຄາເລິ້ມຕົ້ນ11,000ກີບ ຮັບ  7 ZEM\",\"input6\":\"ສັ່ງຊື້ໄດ້ໃນເວັບ https:\\/\\/ppshope.com\\/\",\"input7\":\"http:\\/\\/localhost:8080\\/assets\\/uploads\\/user_inputs\\/img_1768316261_4753.jpg\"}', NULL),
(12, 1, 3, '8f7822628f2bb251aa1b18b9c3a62b19', NULL, 'assets/images/final_12.png', 'completed', '2026-01-13 15:03:13', NULL, '{\"gamename\":\"ZEPETO\",\"topup-center\":\"ອັບເດດລາຄາລ່າສຸດ\",\"input3\":\"ລາຄາເລິ້ມຕົ້ນ11,000ກີບ ຮັບ  7 ZEM\",\"input6\":\"ສັ່ງຊື້ໄດ້ໃນເວັບ https:\\/\\/ppshope.com\\/\",\"input7\":\"http:\\/\\/localhost:8080\\/assets\\/uploads\\/user_inputs\\/img_1768316592_5155.jpg\"}', NULL),
(13, 4, 3, 'fcf4c5278c5f70b395219acf444da14d', NULL, 'assets/images/final_13.png', 'completed', '2026-01-14 12:59:01', NULL, '{\"gamename\":\"free fire\",\"topup-center\":\"ໂປຣໂມຊັນພິເສດ\",\"input3\":\"517 ເພັດ ລາຄາ 59,000ກີບ\",\"input6\":\"ເຕິມໄດ້ທີ່ເວັບ https:\\/\\/ppshope.com\\/\",\"input7\":\"http:\\/\\/localhost:8080\\/assets\\/uploads\\/user_inputs\\/img_1768395541_8178.jpg\"}', NULL),
(14, 4, 3, 'a3c0bb0035968cbc6f9b9a627a6b0d15', NULL, 'assets/images/final_14.png', 'completed', '2026-01-14 13:45:02', NULL, '{\"gamename\":\"free fire\",\"topup-center\":\"ໂປຣໂມຊັນພິເສດ\",\"input3\":\"517 ເພັດ ລາຄາ 59,000ກີບ\",\"input6\":\"ເຕິມໄດ້ທີ່ເວັບ https:\\/\\/ppshope.com\\/\"}', NULL),
(15, 4, 3, '2d5e832c89bf7800d46cca2124b920ed', '{\"gamename\":\"Genshin Impact\",\"topup-center\":\"ເຕິມແລ້ວຄຸ້ມແນ່ນອນ\",\"input3\":\"welkin moon ລາຄາພຽງ 78,999 ກີບ\",\"input6\":\"ເຕິມໄດ້ທີ່: https:\\/\\/ppshope.com\",\"input7\":\"http:\\/\\/localhost:8080\\/paoxay\\/ai\\/paoxay-ai-e392c411f55f74204028a5ea50406ed432928638\\/assets\\/uploads\\/user_inputs\\/img_1768400153_9608.jpg\"}', 'assets/images/final_15.png', 'completed', '2026-01-14 14:15:54', NULL, NULL, NULL),
(16, 4, 3, '1fc772a19afbe4cc1c433b760869cedf', '{\"gamename\":\"Genshin Impact\",\"topup-center\":\"\\u0ec0\\u0e95\\u0eb4\\u0ea1\\u0ec1\\u0ea5\\u0ec9\\u0ea7\\u0e84\\u0eb8\\u0ec9\\u0ea1\\u0ec1\\u0e99\\u0ec8\\u0e99\\u0ead\\u0e99\",\"input3\":\"8080 \\u0ec0\\u0eab\\u0ebc\\u0eb7\\u0ead\\u0e9e\\u0ebd\\u0e87 799,000 \\u0e81\\u0eb5\\u0e9a \\u0ec0\\u0e97\\u0ebb\\u0ec8\\u0eb2\\u0e99\\u0eb1\\u0ec9\\u0e99\",\"input6\":\"\\u0ec0\\u0e95\\u0eb4\\u0ea1\\u0ec4\\u0e94\\u0ec9\\u0e97\\u0eb5\\u0ec8: https:\\/\\/ppshope.com\",\"input7\":\"http:\\/\\/localhost:8080\\/ai\\/assets\\/uploads\\/user_inputs\\/6967b1cd02a04_0.jpg\"}', 'assets/images/final_16.png', 'completed', '2026-01-14 15:10:06', NULL, NULL, NULL),
(17, 4, 3, '2dd47ad20f768d807c2eb10e81013366', '{\"gamename\":\"Genshin Impact\",\"topup-center\":\"ເຕິມແລ້ວຄຸ້ມແນ່ນອນ\",\"input3\":\"8080 ລາຄາ 55,000 ກີບ\",\"input6\":\"ເຕິມໄດ້ທີ່: https:\\/\\/ppshope.com\",\"input7\":\"http:\\/\\/localhost:8080\\/ai\\/assets\\/uploads\\/user_inputs\\/6967b478e0703_0.jpg\"}', NULL, 'failed', '2026-01-14 15:21:29', NULL, NULL, NULL),
(18, 4, 3, '8a65edd3e4d06b2b8bf6f77a03892c31', '{\"gamename\":\"Genshin Impact\",\"topup-center\":\"ເຕິມແລ້ວຄຸ້ມແນ່ນອນ\",\"input3\":\"1582 ລາຄາ 15,000 ກີບ\",\"input6\":\"ເຕິມໄດ້ທີ່: https:\\/\\/ppshope.com\",\"input7\":\"http:\\/\\/localhost:8080\\/ai\\/assets\\/uploads\\/user_inputs\\/6967b53f353a1_0.jpg\"}', 'assets/images/final_18.png', 'completed', '2026-01-14 15:24:48', NULL, NULL, NULL),
(19, 4, 3, 'e2094bebe8b0754b46f337701bf0a922', '{\"gamename\":\"Genshin Impact\",\"topup-center\":\"ເຕິມແລ້ວຄຸ້ມແນ່ນອນ\",\"input3\":\"8050 ລາຄາ 15,000 ກີບ\",\"input6\":\"ເຕິມໄດ້ທີ່: https:\\/\\/ppshope.com\",\"input7\":\"http:\\/\\/localhost:8080\\/ai\\/assets\\/uploads\\/user_inputs\\/6967b6002de96_0.jpg\"}', 'assets/images/final_19.png', 'completed', '2026-01-14 15:28:01', NULL, NULL, NULL),
(20, 4, 3, 'e91390be5f5b7f04242c1c421bd223f4', '{\"gamename\":\"Genshin Impact\",\"topup-center\":\"\",\"input3\":\"\",\"input6\":\"ເຕິມໄດ້ທີ່: https:\\/\\/ppshope.com\",\"key_5\":\"\"}', 'assets/images/final_20.png', 'completed', '2026-01-14 15:37:58', NULL, NULL, NULL),
(21, 4, 3, '83615d386ab49f5e18aa10049fdcf872', '{\"gamename\":\"Genshin Impact\",\"topup-center\":\"ເຕິມແລ້ວຄຸ້ມແນ່ນອນ\",\"input3\":\"ສະບາຍດີປີໄຫມ່\",\"input6\":\"ເຕິມໄດ້ທີ່: https:\\/\\/ppshope.com\",\"key_5\":\"\"}', 'assets/images/final_21.png', 'completed', '2026-01-14 15:38:59', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `credit` decimal(10,2) DEFAULT 0.00,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `fullname`, `email`, `avatar`, `credit`, `role`, `created_at`) VALUES
(1, 'test_google_id', 'ເປົາ ໄຊຍະສານ', 'admin@test.com', 'https://ui-avatars.com/api/?name=Admin', 9940000.00, 'admin', '2026-01-13 04:43:54'),
(2, '109920582961441899285', 'ເປົາ ໄຊຍະສານ', 'paoskapb@gmail.com', 'https://lh3.googleusercontent.com/a/ACg8ocKqDNKwHs5PiXpxdzKNJGngurLGysT3hVL8JjlMEokovwG9Gbqk=s96-c', 0.00, 'user', '2026-01-14 12:43:38'),
(3, '104222309198341393924', 'PAO XAYYASAN', 'paoxayyasan@gmail.com', 'https://lh3.googleusercontent.com/a/ACg8ocJiZP8Tr8n_42KeBz-pkm1WA-nNCyXc06yJntAwvZhT7lnpPQ=s96-c', 0.00, 'admin', '2026-01-14 12:44:55'),
(4, '104333455458839254527', 'yai xai', 'xaykloo103@gmail.com', 'https://lh3.googleusercontent.com/a/ACg8ocLOPfMh76aD--UwHdhkIGVx7kIcGagQgW7Pcu5BT4IEvbNUJw=s96-c', 35000.00, 'user', '2026-01-14 12:49:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_templates`
--
ALTER TABLE `ai_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_templates`
--
ALTER TABLE `ai_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
