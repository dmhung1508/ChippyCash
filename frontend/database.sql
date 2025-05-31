-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 31, 2025 lúc 07:23 PM
-- Phiên bản máy phục vụ: 9.0.1
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `chippy`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'admin_setup_completed', NULL, NULL, 'Admin panel setup script executed successfully', '127.0.0.1', NULL, '2025-05-26 01:50:51'),
(2, 1, 'update_setting', 'setting', NULL, 'Updated site_name to: Quản Lý Thu Chi Chippy', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-05-26 10:10:35'),
(3, 1, 'update_setting', 'setting', NULL, 'Updated site_name to: Quản Lý Thu Chi Chippy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:11:36'),
(4, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 1', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-05-26 10:11:58'),
(5, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 0', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-05-26 10:15:45'),
(6, 1, 'update_setting', 'setting', NULL, 'Updated user_registration to: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:16:17'),
(7, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:37:41'),
(8, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:41:16'),
(9, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:41:24'),
(10, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:41:44'),
(11, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:44:46'),
(12, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:44:52'),
(13, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:52:51'),
(14, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:54:04'),
(15, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 10:54:15'),
(16, 1, 'database_backup', NULL, NULL, 'Backup created: backup_chippy_2025-05-26_06-33-44.sql (74275 bytes)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:33:44'),
(17, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:34:19'),
(18, 1, 'update_setting', 'setting', NULL, 'Updated user_registration to: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:34:36'),
(19, 1, 'update_setting', 'setting', NULL, 'Updated user_registration to: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:35:22'),
(20, 1, 'update_setting', 'setting', NULL, 'Updated admin_email to: admin@dinhmanhhung.net', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:35:37'),
(21, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:35:59'),
(22, 1, 'update_setting', 'setting', NULL, 'Updated admin_email to: admin@dinhmanhhung.net', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:36:10'),
(23, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:36:14'),
(24, 1, 'send_notification', 'notification', NULL, 'Sent notification: 43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:47:33'),
(25, 1, 'send_notification', 'notification', NULL, 'Sent notification: 43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 11:48:45'),
(26, 1, 'send_notification', 'notification', NULL, 'Sent notification: 43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 12:14:10'),
(27, 1, 'delete_user', 'user', 3, 'Deleted user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 00:09:09'),
(28, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 00:09:14'),
(29, 1, 'delete_user', 'user', 3, 'Deleted user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 00:16:35'),
(30, 1, 'send_notification', 'notification', 1, 'Notification to user 1: hihi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 00:17:04'),
(31, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 21:34:06'),
(32, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 21:34:10'),
(33, 1, 'send_notification', 'notification', 4, 'Notification to user 4: hi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 22:39:30'),
(34, 1, 'send_notification', 'notification', 4, 'Notification to user 4: hi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 22:54:39'),
(35, 1, 'database_backup', NULL, NULL, 'Backup created: backup_chippy_2025-05-28_08-56-31.sql (97438 bytes)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 13:56:31'),
(36, 1, 'update_settings', NULL, NULL, 'Updated system settings: maintenance_mode', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 13:56:34'),
(37, 1, 'send_notification', 'notification', 4, 'Notification to user 4: rtet', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 13:57:07'),
(38, 1, 'update_setting', 'setting', NULL, 'Updated maintenance_mode to: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 15:11:06'),
(39, 1, 'send_notification', 'notification', 4, 'Notification to user 4: aluong3@hacker2k4.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 17:11:14'),
(40, 1, 'send_notification', 'notification', 4, 'Notification to user 4: xcvxcv', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-05-29 17:12:03'),
(41, 1, 'send_notification', 'notification', 4, 'Notification to user 4: xcvxcv', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-05-29 17:13:51'),
(42, 1, 'send_notification', 'notification', 4, 'Notification to user 4: xcvxcv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 17:16:42'),
(43, 1, 'send_global_notification', 'notification', NULL, 'Global notification: aluong3@hacker2k4.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-06-01 00:08:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `backup_history`
--

CREATE TABLE `backup_history` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint DEFAULT NULL,
  `backup_type` enum('manual','scheduled') COLLATE utf8mb4_unicode_ci DEFAULT 'manual',
  `status` enum('success','failed','in_progress') COLLATE utf8mb4_unicode_ci DEFAULT 'success',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `backup_history`
--

INSERT INTO `backup_history` (`id`, `admin_id`, `filename`, `file_size`, `backup_type`, `status`, `created_at`) VALUES
(1, 1, 'backup_chippy_2025-05-26_06-33-44.sql', 74275, 'manual', 'success', '2025-05-26 11:33:44'),
(2, 1, 'backup_chippy_2025-05-28_08-56-31.sql', 97438, 'manual', 'success', '2025-05-28 13:56:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `bank_username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `user_id`, `bank_username`, `bank_password`, `account_name`, `last_sync`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 4, 'dinhhung1508', 'RGluaGh1bmcxNTA4MjRA', 'Hung', '2025-05-31 17:39:28', 1, '2025-05-27 22:34:15', NULL),
(13, 1, 'dinhhung1508', 'RGluaGh1bmcxNTA4MjRA', 'cc', '2025-06-01 00:17:16', 1, '2025-06-01 00:17:14', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `description`, `type`, `created_at`, `updated_at`) VALUES
(1, 1, 'tiền lương', 'tiền lương tháng', 'income', '2025-03-18 10:45:15', NULL),
(2, 1, 'mua đồ', 'mua đồ ăn', 'expense', '2025-03-18 11:04:01', NULL),
(3, 1, 'tiền mua đồ ăn', '', 'expense', '2025-05-24 22:19:20', NULL),
(4, 1, 'Chi phí khác', '', 'expense', '2025-05-25 23:04:05', NULL),
(18, 4, 'Lương', 'Thu nhập từ lương hàng tháng hoặc công việc chính', 'income', '2025-05-27 22:13:16', NULL),
(19, 4, 'Tiền thưởng', 'Tiền thưởng từ công việc hoặc dự án', 'income', '2025-05-27 22:13:16', NULL),
(20, 4, 'Tiền lãi', 'Tiền lãi từ các khoản đầu tư', 'income', '2025-05-27 22:13:16', NULL),
(21, 4, 'Tiền bán hàng', 'Tiền từ việc bán hàng hóa hoặc dịch vụ', 'income', '2025-05-27 22:13:16', NULL),
(22, 4, 'Tiền ăn uống', 'Tiền ăn uống hàng ngày', 'expense', '2025-05-27 22:13:16', NULL),
(23, 4, 'Tiền đi lại', 'Tiền đi lại hàng ngày', 'expense', '2025-05-27 22:13:16', NULL),
(24, 4, 'Tiền học tập', 'Tiền học tập hàng tháng', 'expense', '2025-05-27 22:13:16', NULL),
(25, 4, 'Tiền giải trí', 'Tiền giải trí hàng tháng', 'expense', '2025-05-27 22:13:16', NULL),
(26, 4, 'Tiền xăng', 'Tiền xăng hàng tháng', 'expense', '2025-05-27 22:13:16', NULL),
(27, 4, 'Tiền nước', 'Tiền nước hàng tháng', 'expense', '2025-05-27 22:13:16', NULL),
(28, 4, 'Tiền điện', 'Tiền điện hàng tháng', 'expense', '2025-05-27 22:13:16', NULL),
(29, 4, 'Tiền mua sắm', 'Tiền mua sắm hàng tháng', 'expense', '2025-05-27 22:13:16', NULL),
(30, 4, 'Tiền khác', 'Tiền khác hàng tháng', 'expense', '2025-05-27 22:13:16', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_history`
--

CREATE TABLE `chat_history` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_bot` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_history`
--

INSERT INTO `chat_history` (`id`, `user_id`, `message`, `is_bot`, `created_at`) VALUES
(1, 1, 'Quy tắc 50/30/20 là gì?', 0, '2025-03-18 10:05:44'),
(2, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:05:44'),
(3, 1, 'Làm thế nào để lập ngân sách hiệu quả?', 0, '2025-03-18 10:05:48'),
(4, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:05:48'),
(5, 1, 'Làm sao để tiết kiệm tiền?', 0, '2025-03-18 10:05:52'),
(6, 1, 'Dựa trên dữ liệu hiện tại, bạn đang chi tiêu nhiều hơn hoặc bằng thu nhập hàng tháng. Để có thể tiết kiệm, bạn cần cắt giảm chi tiêu hoặc tăng thu nhập.\n\nGợi ý: Hãy xem xét các khoản chi tiêu không cần thiết và cố gắng cắt giảm ít nhất 10-15% tổng chi tiêu.', 1, '2025-03-18 10:05:52'),
(7, 1, 'Quỹ khẩn cấp là gì?', 0, '2025-03-18 10:06:01'),
(8, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:06:01'),
(9, 1, 'Làm thế nào để theo dõi chi tiêu hiệu quả?', 0, '2025-03-18 10:06:05'),
(10, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:06:05'),
(11, 1, 'Làm sao để tiết kiệm tiền?', 0, '2025-03-18 10:49:58'),
(12, 1, 'Dựa trên dữ liệu tài chính của bạn, tôi đề xuất kế hoạch tiết kiệm như sau:\n\n- Thu nhập ước tính hàng tháng: 417₫\n- Chi tiêu ước tính hàng tháng: 0₫\n- Tiềm năng tiết kiệm hàng tháng: 417₫\n\nKế hoạch tiết kiệm đề xuất:\n1. Quỹ khẩn cấp (50%): 208₫/tháng\n2. Tiết kiệm dài hạn (30%): 125₫/tháng\n3. Quỹ giải trí/cá nhân (20%): 83₫/tháng\n\nVới kế hoạch này, sau 1 năm bạn sẽ tiết kiệm được khoảng 5.000₫.', 1, '2025-03-18 10:49:58'),
(13, 1, 'Làm thế nào để lập ngân sách hiệu quả?', 0, '2025-03-18 10:50:04'),
(14, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:50:04'),
(15, 1, 'Làm thế nào để theo dõi chi tiêu hiệu quả?', 0, '2025-03-18 10:50:11'),
(16, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:50:11'),
(17, 1, 'Quy tắc 50/30/20 là gì?', 0, '2025-03-18 10:50:13'),
(18, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:50:13'),
(19, 1, 'Làm sao để giảm chi tiêu hàng tháng?', 0, '2025-03-18 10:50:15'),
(20, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:50:15'),
(21, 1, 'Quy tắc 50/30/20 là gì?', 0, '2025-03-18 10:50:18'),
(22, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-03-18 10:50:18'),
(23, 1, 'Hôm nay tôi mua sắm hết 500k', 0, '2025-04-17 23:13:31'),
(24, 1, 'Tôi đã ghi nhận khoản chi tiêu của bạn. Bạn có thể lưu giao dịch này bằng cách nhấn nút \'Sửa giao dịch\' bên dưới.', 1, '2025-04-17 23:13:31'),
(25, 1, 'Vừa nhận lương 10 triệu', 0, '2025-04-17 23:13:53'),
(26, 1, 'Tôi đã ghi nhận khoản thu nhập của bạn. Bạn có thể lưu giao dịch này bằng cách nhấn nút \'Sửa giao dịch\' bên dưới.', 1, '2025-04-17 23:13:53'),
(27, 1, 'Hôm qua đi ăn hết 300k, đổ xăng 100k', 0, '2025-04-18 19:58:46'),
(28, 1, 'Tôi đã ghi nhận khoản chi tiêu của bạn. Bạn có thể lưu giao dịch này bằng cách nhấn nút \'Sửa giao dịch\' bên dưới.', 1, '2025-04-18 19:58:46'),
(29, 1, 'hi', 0, '2025-04-18 20:06:33'),
(30, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-04-18 20:06:33'),
(31, 1, 'bạn là ai', 0, '2025-04-18 20:06:43'),
(32, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-04-18 20:06:43'),
(33, 1, 'Hôm qua đi ăn hết 300k, đổ xăng 100k', 0, '2025-04-18 20:10:28'),
(34, 1, 'Tôi đã ghi nhận khoản chi tiêu của bạn. Bạn có thể lưu giao dịch này bằng cách nhấn nút \'Sửa giao dịch\' bên dưới.', 1, '2025-04-18 20:10:28'),
(35, 1, 'Làm thế nào để lập ngân sách hiệu quả?', 0, '2025-05-10 11:16:37'),
(36, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-05-10 11:16:37'),
(37, 1, 'Làm thế nào để lập ngân sách hiệu quả?', 0, '2025-05-10 11:16:41'),
(38, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-05-10 11:16:41'),
(39, 1, 'hi', 0, '2025-05-10 11:16:45'),
(40, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-05-10 11:16:45'),
(41, 1, 'hi', 0, '2025-05-23 11:47:10'),
(42, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-05-23 11:47:10'),
(43, 1, 'bạn là ai', 0, '2025-05-23 11:47:15'),
(44, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-05-23 11:47:15'),
(45, 1, '?', 0, '2025-05-23 11:47:17'),
(46, 1, 'Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?', 1, '2025-05-23 11:47:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','success','warning','error') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `is_global` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `is_global`, `created_at`, `read_at`) VALUES
(1, NULL, '🎉 Chào mừng đến với Admin Panel!', 'Hệ thống admin đã được cài đặt thành công. Bạn có thể quản lý users, cài đặt và giám sát hệ thống tại đây.', 'success', 0, 1, '2025-05-26 01:50:51', NULL),
(2, NULL, '🔐 Bảo mật hệ thống', 'Vui lòng đổi mật khẩu admin mặc định để đảm bảo bảo mật. Username: admin | Password: admin123', 'warning', 0, 1, '2025-05-26 01:50:51', NULL),
(6, 1, 'hihi', 'hihi', 'error', 0, 0, '2025-05-27 00:17:03', NULL),
(7, 4, 'hi', 'hihi', 'warning', 0, 0, '2025-05-27 22:39:29', NULL),
(8, 4, 'hi', 'hihi', 'warning', 0, 0, '2025-05-27 22:54:38', NULL),
(9, 4, 'rtet', 'ểtrtre', 'info', 0, 0, '2025-05-28 13:57:07', NULL),
(10, 4, 'aluong3@hacker2k4.com', 'dfg', 'success', 0, 0, '2025-05-29 17:11:13', NULL),
(11, 4, 'xcvxcv', 'cxv', 'info', 0, 0, '2025-05-29 17:12:03', NULL),
(12, 4, 'xcvxcv', 'cxv', 'info', 0, 0, '2025-05-29 17:13:50', NULL),
(13, 4, 'xcvxcv', 'cxv', 'info', 0, 0, '2025-05-29 17:16:41', NULL),
(14, 1, 'aluong3@hacker2k4.com', 'dfgdf', 'info', 0, 0, '2025-06-01 00:08:28', NULL),
(15, 2, 'aluong3@hacker2k4.com', 'dfgdf', 'info', 0, 0, '2025-06-01 00:08:29', NULL),
(16, 4, 'aluong3@hacker2k4.com', 'dfgdf', 'info', 0, 0, '2025-06-01 00:08:30', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'admin_email', 'admin@example.com', 'text', 'Email liên hệ của quản trị viên hệ thống', '2025-05-26 05:13:57', '2025-05-26 05:13:57'),
(2, 'maintenance_mode', '0', 'boolean', 'Bật/tắt chế độ bảo trì website', '2025-05-26 05:13:57', '2025-05-28 08:11:06'),
(3, 'site_description', 'Hệ thống quản lý tài chính cá nhân', 'text', 'Mô tả ngắn gọn về website của bạn', '2025-05-26 05:13:57', '2025-05-26 05:13:57'),
(4, 'user_registration', '1', 'boolean', 'Cho phép người dùng mới đăng ký tài khoản', '2025-05-26 05:13:57', '2025-05-26 05:13:57'),
(5, 'site_name', 'Quản lý Tài chính', 'text', 'Tên hiển thị của website', '2025-05-26 05:13:57', '2025-05-26 05:13:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `bank_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_bank_import` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `amount`, `description`, `type`, `category`, `date`, `created_at`, `updated_at`, `bank_transaction_id`, `is_bank_import`) VALUES
(2, 1, 500000.00, 'hi', 'expense', 'mua đồ', '2025-03-18', '2025-03-18 11:04:20', NULL, NULL, 0),
(3, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 21:09:45', NULL, NULL, 0),
(4, 1, 15000000.00, 'lương', 'income', 'tiền lương', '2025-04-18', '2025-04-18 21:09:45', NULL, NULL, 0),
(5, 1, 500000.00, 'tiền đi lại', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 21:09:45', NULL, NULL, 0),
(6, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 21:54:38', NULL, NULL, 0),
(7, 1, 15000000.00, 'lương', 'income', 'tiền lương', '2025-04-18', '2025-04-18 21:54:38', NULL, NULL, 0),
(8, 1, 500000.00, 'tiền đi lại', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 21:54:38', NULL, NULL, 0),
(9, 1, 900000.00, 'tiền đi lại', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 21:54:38', NULL, NULL, 0),
(10, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 22:09:49', NULL, NULL, 0),
(11, 1, 15000000.00, 'lương', 'income', 'tiền lương', '2025-04-18', '2025-04-18 22:09:49', NULL, NULL, 0),
(12, 1, 500000.00, 'tiền đi lại', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 22:09:49', NULL, NULL, 0),
(13, 1, 900000.00, 'tiền đi lại', 'expense', 'mua đồ', '2025-04-18', '2025-04-18 22:09:49', NULL, NULL, 0),
(15, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-04-26', '2025-04-26 16:49:26', NULL, NULL, 0),
(16, 1, 125000.00, 'Bánh mỳ bỏ lò dăm bông & phomai', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 11:39:45', NULL, NULL, 0),
(17, 1, 125000.00, 'Thịt nguội & phomai viên chiên kiểu Tây Ba Nha', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 11:39:45', NULL, NULL, 0),
(18, 1, 125000.00, 'Súp kem rau 4 mùa', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 11:39:45', NULL, NULL, 0),
(19, 1, 125000.00, 'Xúc xích Đức nướng mù tạt vàng', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 11:39:45', NULL, NULL, 0),
(20, 1, 40000.00, 'MILANO', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 11:39:45', NULL, NULL, 0),
(21, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:21:51', NULL, NULL, 0),
(22, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:22:37', NULL, NULL, 0),
(23, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:23:00', NULL, NULL, 0),
(24, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:23:14', NULL, NULL, 0),
(25, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:23:14', NULL, NULL, 0),
(26, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:23:15', NULL, NULL, 0),
(27, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:23:15', NULL, NULL, 0),
(28, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-10', '2025-05-10 19:24:08', NULL, NULL, 0),
(29, 1, 500000.00, 'tiền ăn uống', 'expense', 'mua đồ', '2025-05-23', '2025-05-23 11:52:56', NULL, NULL, 0),
(30, 1, 56000.00, 'Tiger nâu', 'expense', 'mua đồ', '2025-05-23', '2025-05-23 12:04:58', NULL, NULL, 0),
(31, 1, 9000.00, 'Bánh tráng', 'expense', 'mua đồ', '2025-05-23', '2025-05-23 12:04:58', NULL, NULL, 0),
(32, 1, 60000.00, 'Cá lóc chiên mắm xoài', 'expense', 'mua đồ', '2025-05-23', '2025-05-23 12:04:58', NULL, NULL, 0),
(33, 1, 35000.00, 'Bao tử ếch xào sả', 'expense', 'mua đồ', '2025-05-23', '2025-05-23 12:04:58', NULL, NULL, 0),
(34, 1, 12000.00, 'Hến xào', 'income', 'tiền lương', '2025-05-23', '2025-05-23 12:04:58', '2025-05-24 01:22:20', NULL, 0),
(35, 1, 500000.00, 'tiền quần áo', 'expense', 'mua đồ', '2025-05-24', '2025-05-24 15:55:46', NULL, NULL, 0),
(36, 1, 56000.00, 'Tiger nâu', 'expense', 'mua đồ', '2025-05-24', '2025-05-24 18:57:47', NULL, NULL, 0),
(37, 1, 9000.00, 'Bánh tráng', 'expense', 'mua đồ', '2025-05-24', '2025-05-24 18:57:47', NULL, NULL, 0),
(38, 1, 60000.00, 'Cá Lóc chiên mắm xoài', 'expense', 'mua đồ', '2025-05-24', '2025-05-24 18:57:47', NULL, NULL, 0),
(39, 1, 35000.00, 'Bao tử ếch xào sa', 'expense', 'mua đồ', '2025-05-24', '2025-05-24 18:57:47', NULL, NULL, 0),
(40, 1, 12000.00, 'Hến xào', 'expense', 'mua đồ', '2025-05-24', '2025-05-24 18:57:47', NULL, NULL, 0),
(41, 1, 97000.00, 'CUSTOMER KOVQR 8CHDX6  - Ma giao dich/ Trace  403637 - LE THI HONG TUYET (0947444589)', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 22:42:06', NULL, NULL, 0),
(42, 1, 40000.00, 'CUSTOMER KOVQR 8C6R8L  - Ma giao dich/ Trace  701713', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 22:42:06', NULL, NULL, 0),
(43, 1, 101000.00, 'CUSTOMER KOVQR 8A6Z6V  - Ma giao dich/ Trace  643528 - LE THI HONG TUYET (0947444589)', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 22:42:06', NULL, NULL, 0),
(44, 1, 3512000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ TB783748 - DINH THI KIM CUC (1017139246)', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 22:42:06', NULL, NULL, 0),
(45, 1, 68000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 879265 - TA VAN THAN (0937272662)', 'expense', 'Chi phí khác', '2025-05-24', '2025-05-25 22:42:06', NULL, NULL, 0),
(46, 1, 40000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ PF401104 - LE THIEN PHAT (1045693900)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(47, 1, 985000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ CZ683518 - HOANG ANH KHOA (8890280617)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(48, 1, 10000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 700648 - MOMO_BAI XE HAI ANH (99MM24159M18099610)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(49, 1, 33000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ I5880590 - BUI VAN PHUOC (107870073150)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(50, 1, 150000.00, 'LE TRAN QUOC BAO LE TRAN QUOC BAO Chuyen tien- Ma GD  ACSP/ Ur621892', 'income', 'Thu nhập khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(51, 1, 134280.00, 'CUSTOMER POSRETAIL-514304734408-5-USD-AUTHC ODE-531614-TID-7N3QWJUF-MID-SXONGQ UGQWWJUTM-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(52, 1, 120000.00, 'CUSTOMER DINH MANH HUNG chuyen tien. DEN: DINH MANH HUNG - DINH MANH HUNG  (0000968948008)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(53, 1, 120000.00, 'CUSTOMER DINH MANH HUNG chuyen tien. TU: DINH MANH HUNG - DINH MANH HUNG  (0000968948008)', 'income', 'Thu nhập khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(54, 1, 3550000.00, 'TRAN HUU DAT MBVCB.9593192710.165540.TRAN HUU DA T chuyen tien.CT tu 0161000879001 T RAN HUU DAT toi 669699669 DINH MANH  HUNG tai MB- Ma GD ACSP/ bl165540', 'income', 'Thu nhập khác', '2025-05-23', '2025-05-25 22:42:06', NULL, NULL, 0),
(55, 1, 10000.00, 'BUI VU HUY Qaawgm7172  APPMB1 1  BUI VU HUY CH UYEN TIEN- Ma GD ACSP/ DN863229', 'income', 'Thu nhập khác', '2025-05-22', '2025-05-25 22:42:06', NULL, NULL, 0),
(56, 1, 10000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215176634960 BP0001mhhl9s . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 22:42:06', NULL, NULL, 0),
(57, 1, 5000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215176520034 BP0001mhhj8m . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 22:42:06', NULL, NULL, 0),
(58, 1, 600000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ Y8500980 - DANG DINH KINH (0021000527302)', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 22:42:06', NULL, NULL, 0),
(59, 1, 1550000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 226544 - NGUYEN HONG SON (19035309130013)', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 22:42:06', NULL, NULL, 0),
(60, 1, 40000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 777195 - LUU THE VINH (0987221768)', 'expense', 'Chi phí khác', '2025-05-20', '2025-05-25 22:42:06', NULL, NULL, 0),
(61, 1, 53833.00, 'CUSTOMER POSRETAIL-514001558705-53833-VND-A UTHCODE-531613-TID-12345678-MID-00 0980020918994-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-20', '2025-05-25 22:42:06', NULL, NULL, 0),
(62, 1, 15000.00, 'CUSTOMER GM1BD64DFMG - Ma giao dich/ Trace 2 68108 - LO VAN THUONG (19073584802012)', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 22:42:06', NULL, NULL, 0),
(63, 1, 31000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ 74508037 - NGUYEN QUANG TIEN (0541000305763)', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 22:42:06', NULL, NULL, 0),
(64, 1, 55146.00, 'CUSTOMER RetailVisa-762707-55146-VND-513919 493688', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 22:42:06', NULL, NULL, 0),
(65, 1, 214666.00, 'CUSTOMER POSRETAIL-513902827066-8-USD-AUTHC ODE-531612-TID-7N3QWJUF-MID-SXONGQ UGQWWJUTM-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 22:42:06', NULL, NULL, 0),
(66, 1, 7535.00, 'CUSTOMER POSRETAIL-513815555412-450-NGN-AUT HCODE-531611-TID-99999999-MID-4204 29000211321-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-18', '2025-05-25 22:42:06', NULL, NULL, 0),
(67, 1, 20.00, 'Tra lai tien gui, so TK: 0000968948008-20250517', 'income', 'Thu nhập khác', '2025-05-18', '2025-05-25 22:42:06', NULL, NULL, 0),
(68, 1, 167.00, 'Tra lai tien gui, so TK: 646263-20250517', 'income', 'Thu nhập khác', '2025-05-18', '2025-05-25 22:42:06', NULL, NULL, 0),
(69, 1, 544.00, 'Tra lai tien gui, so TK: 669699669-20250517', 'income', 'Thu nhập khác', '2025-05-18', '2025-05-25 22:42:06', NULL, NULL, 0),
(70, 1, 97000.00, 'CUSTOMER KOVQR 8CHDX6  - Ma giao dich/ Trace  403637 - LE THI HONG TUYET (0947444589)', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 23:06:16', NULL, '25/05/2025 19:08:47_97000_CUSTOMER KOVQR 8CHDX', 1),
(71, 1, 40000.00, 'CUSTOMER KOVQR 8C6R8L  - Ma giao dich/ Trace  701713', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 23:06:16', NULL, '25/05/2025 18:22:16_40000_CUSTOMER KOVQR 8C6R8', 1),
(72, 1, 101000.00, 'CUSTOMER KOVQR 8A6Z6V  - Ma giao dich/ Trace  643528 - LE THI HONG TUYET (0947444589)', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 23:06:16', NULL, '25/05/2025 12:59:41_101000_CUSTOMER KOVQR 8A6Z6', 1),
(73, 1, 3512000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ TB783748 - DINH THI KIM CUC (1017139246)', 'expense', 'Chi phí khác', '2025-05-25', '2025-05-25 23:06:16', NULL, '25/05/2025 01:36:21_3512000_DINH MANH HUNG DINH ', 1),
(74, 1, 68000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 879265 - TA VAN THAN (0937272662)', 'expense', 'Chi phí khác', '2025-05-24', '2025-05-25 23:06:16', NULL, '24/05/2025 12:46:59_68000_CUSTOMER DINH MANH H', 1),
(75, 1, 40000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ PF401104 - LE THIEN PHAT (1045693900)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 23:55:28_40000_DINH MANH HUNG DINH ', 1),
(76, 1, 985000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ CZ683518 - HOANG ANH KHOA (8890280617)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 22:20:57_985000_DINH MANH HUNG DINH ', 1),
(77, 1, 10000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 700648 - MOMO_BAI XE HAI ANH (99MM24159M18099610)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 13:56:14_10000_CUSTOMER DINH MANH H', 1),
(78, 1, 33000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ I5880590 - BUI VAN PHUOC (107870073150)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 12:51:33_33000_DINH MANH HUNG DINH ', 1),
(79, 1, 150000.00, 'LE TRAN QUOC BAO LE TRAN QUOC BAO Chuyen tien- Ma GD  ACSP/ Ur621892', 'income', 'Thu nhập khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 11:48:44_150000_LE TRAN QUOC BAO LE ', 1),
(80, 1, 134280.00, 'CUSTOMER POSRETAIL-514304734408-5-USD-AUTHC ODE-531614-TID-7N3QWJUF-MID-SXONGQ UGQWWJUTM-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 11:47:04_134280_CUSTOMER POSRETAIL-5', 1),
(81, 1, 120000.00, 'CUSTOMER DINH MANH HUNG chuyen tien. DEN: DINH MANH HUNG - DINH MANH HUNG  (0000968948008)', 'expense', 'Chi phí khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 11:46:45_120000_CUSTOMER DINH MANH H', 1),
(82, 1, 3550000.00, 'TRAN HUU DAT MBVCB.9593192710.165540.TRAN HUU DA T chuyen tien.CT tu 0161000879001 T RAN HUU DAT toi 669699669 DINH MANH  HUNG tai MB- Ma GD ACSP/ bl165540', 'income', 'Thu nhập khác', '2025-05-23', '2025-05-25 23:06:16', NULL, '23/05/2025 00:35:47_3550000_TRAN HUU DAT MBVCB.9', 1),
(83, 1, 10000.00, 'BUI VU HUY Qaawgm7172  APPMB1 1  BUI VU HUY CH UYEN TIEN- Ma GD ACSP/ DN863229', 'income', 'Thu nhập khác', '2025-05-22', '2025-05-25 23:06:16', NULL, '22/05/2025 15:49:49_10000_BUI VU HUY Qaawgm717', 1),
(84, 1, 10000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215176634960 BP0001mhhl9s . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 23:06:16', NULL, '22/05/2025 15:17:40_10000_DINH MANH HUNG QRCOD', 1),
(85, 1, 5000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215176520034 BP0001mhhj8m . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 23:06:16', NULL, '22/05/2025 15:17:01_5000_DINH MANH HUNG QRCOD', 1),
(86, 1, 10000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215166457711 BP0001mhhk34 . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 23:06:16', NULL, '22/05/2025 15:16:39_10000_DINH MANH HUNG QRCOD', 1),
(87, 1, 10000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215146026501 BP0001mhhf2e . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 23:06:16', NULL, '22/05/2025 15:14:06_10000_DINH MANH HUNG QRCOD', 1),
(88, 1, 600000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ Y8500980 - DANG DINH KINH (0021000527302)', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 23:06:16', NULL, '22/05/2025 09:56:31_600000_DINH MANH HUNG DINH ', 1),
(89, 1, 1550000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 226544 - NGUYEN HONG SON (19035309130013)', 'expense', 'Chi phí khác', '2025-05-22', '2025-05-25 23:06:16', NULL, '22/05/2025 09:47:40_1550000_CUSTOMER DINH MANH H', 1),
(90, 1, 40000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 777195 - LUU THE VINH (0987221768)', 'expense', 'Chi phí khác', '2025-05-20', '2025-05-25 23:06:16', NULL, '20/05/2025 12:05:27_40000_CUSTOMER DINH MANH H', 1),
(91, 1, 53833.00, 'CUSTOMER POSRETAIL-514001558705-53833-VND-A UTHCODE-531613-TID-12345678-MID-00 0980020918994-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-20', '2025-05-25 23:06:16', NULL, '20/05/2025 08:05:17_53833_CUSTOMER POSRETAIL-5', 1),
(92, 1, 15000.00, 'CUSTOMER GM1BD64DFMG - Ma giao dich/ Trace 2 68108 - LO VAN THUONG (19073584802012)', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 23:06:16', NULL, '19/05/2025 20:16:49_15000_CUSTOMER GM1BD64DFMG', 1),
(93, 1, 31000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ 74508037 - NGUYEN QUANG TIEN (0541000305763)', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 23:06:16', NULL, '19/05/2025 19:04:39_31000_DINH MANH HUNG DINH ', 1),
(94, 1, 55146.00, 'CUSTOMER RetailVisa-762707-55146-VND-513919 493688', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 23:06:16', NULL, '19/05/2025 14:00:52_55146_CUSTOMER RetailVisa-', 1),
(95, 1, 214666.00, 'CUSTOMER POSRETAIL-513902827066-8-USD-AUTHC ODE-531612-TID-7N3QWJUF-MID-SXONGQ UGQWWJUTM-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-19', '2025-05-25 23:06:16', NULL, '19/05/2025 09:48:11_214666_CUSTOMER POSRETAIL-5', 1),
(96, 1, 7535.00, 'CUSTOMER POSRETAIL-513815555412-450-NGN-AUT HCODE-531611-TID-99999999-MID-4204 29000211321-TRXCODE-', 'expense', 'Chi phí khác', '2025-05-18', '2025-05-25 23:06:16', NULL, '18/05/2025 22:57:12_7535_CUSTOMER POSRETAIL-5', 1),
(97, 1, 20.00, 'Tra lai tien gui, so TK: 0000968948008-20250517', 'income', 'Thu nhập khác', '2025-05-18', '2025-05-25 23:06:16', NULL, '18/05/2025 02:33:49_20_Tra lai tien gui, so', 1),
(98, 1, 167.00, 'Tra lai tien gui, so TK: 646263-20250517', 'income', 'Thu nhập khác', '2025-05-18', '2025-05-25 23:06:16', NULL, '18/05/2025 01:38:45_167_Tra lai tien gui, so', 1),
(99, 1, 544.00, 'Tra lai tien gui, so TK: 669699669-20250517', 'income', 'Thu nhập khác', '2025-05-18', '2025-05-25 23:06:16', NULL, '18/05/2025 00:01:08_544_Tra lai tien gui, so', 1),
(100, 1, 56.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:02', NULL, NULL, 0),
(101, 1, 9.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:02', NULL, NULL, 0),
(102, 1, 60.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:02', NULL, NULL, 0),
(103, 1, 35.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:02', NULL, NULL, 0),
(104, 1, 12.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:02', NULL, NULL, 0),
(105, 1, 56.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:11', NULL, NULL, 0),
(106, 1, 9.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:11', NULL, NULL, 0),
(107, 1, 60.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:11', NULL, NULL, 0),
(108, 1, 35.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:11', NULL, NULL, 0),
(109, 1, 12.00, 'tiền ăn uống', 'expense', 'Chi phí khác', '2025-05-27', '2025-05-27 22:11:11', NULL, NULL, 0),
(110, 4, 500000.00, 'tiền ăn uống', 'expense', 'Tiền ăn uống', '2025-05-27', '2025-05-27 22:20:45', NULL, NULL, 0),
(111, 4, 500000.00, 'Tiền điện', 'expense', 'Tiền điện', '2025-05-27', '2025-05-27 22:30:26', NULL, NULL, 0),
(112, 4, 500000.00, 'lương', 'income', 'Lương', '2025-05-27', '2025-05-27 23:16:32', NULL, NULL, 0),
(113, 4, 50000.00, 'tiền điện', 'expense', 'Tiền điện', '2025-05-27', '2025-05-27 23:16:32', NULL, NULL, 0),
(114, 4, 500000.00, 'Tiền khác', 'expense', 'Tiền khác', '2025-05-27', '2025-05-27 23:40:37', NULL, NULL, 0),
(115, 4, 100000.00, 'Tiền điện', 'expense', 'Tiền điện', '2025-05-27', '2025-05-27 23:40:37', NULL, NULL, 0),
(116, 4, 500000.00, 'Lương', 'income', 'Lương', '2025-05-27', '2025-05-27 23:40:37', NULL, NULL, 0),
(117, 4, 56000.00, 'Tiền khác', 'expense', 'Tiền khác', '2025-05-27', '2025-05-27 23:40:37', NULL, NULL, 0),
(118, 4, 9000.00, 'Tiền khác', 'expense', 'Tiền khác', '2025-05-27', '2025-05-27 23:40:37', NULL, NULL, 0),
(120, 4, 500000.00, 'Lương', 'income', 'Lương', '2025-05-27', '2025-05-27 23:44:29', NULL, NULL, 0),
(121, 4, 500000.00, 'Tiền thưởng', 'income', 'Tiền thưởng', '2025-05-27', '2025-05-27 23:44:29', NULL, NULL, 0),
(122, 4, 500000.00, 'mua game', 'expense', 'Tiền ăn uống', '2025-05-27', '2025-05-27 23:45:30', NULL, NULL, 0),
(123, 4, 500000.00, 'mua game', 'expense', 'Tiền ăn uống', '2025-05-27', '2025-05-27 23:46:22', NULL, NULL, 0),
(124, 4, 500000.00, 'mua game', 'expense', 'Tiền giải trí', '2025-05-27', '2025-05-27 23:48:27', NULL, NULL, 0),
(125, 4, 500000.00, 'NGUYEN TUNG LAM NGUYEN TUNG LAM CHUYEN TIEN- Ma GD  ACSP/ wC070064', 'income', 'Lương', '2025-05-27', '2025-05-27 23:54:34', NULL, '27/05/2025 23:01:56_500000_NGUYEN TUNG LAM NGUY', 1),
(126, 4, 266000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ 55289968 - TRAN VAN HUY (0931004194560)', 'expense', 'Chung', '2025-05-27', '2025-05-28 00:05:54', NULL, '27/05/2025 11:42:17_266000_DINH MANH HUNG DINH ', 1),
(127, 4, 1300000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 092402 - NGUYEN HONG SON (19035309130013)', 'expense', 'Chung', '2025-05-27', '2025-05-28 00:05:54', NULL, '27/05/2025 10:45:00_1300000_CUSTOMER DINH MANH H', 1),
(128, 4, 500000.00, 'NGUYEN TUNG LAM ANH GUI TIEN- Ma GD ACSP/ il428002', 'income', 'Chung', '2025-05-26', '2025-05-28 00:05:54', NULL, '26/05/2025 21:41:49_500000_NGUYEN TUNG LAM ANH ', 1),
(129, 4, 97000.00, 'CUSTOMER KOVQR 8CHDX6  - Ma giao dich/ Trace  403637 - LE THI HONG TUYET (0947444589)', 'expense', 'Chung', '2025-05-25', '2025-05-28 00:05:54', NULL, '25/05/2025 19:08:47_97000_CUSTOMER KOVQR 8CHDX', 1),
(130, 4, 115000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ QX316869 - TRAN VAN HUY (0931004194560)', 'expense', 'Chung', '2025-05-26', '2025-05-28 00:05:54', NULL, '26/05/2025 11:10:06_115000_DINH MANH HUNG DINH ', 1),
(131, 4, 40000.00, 'CUSTOMER KOVQR 8C6R8L  - Ma giao dich/ Trace  701713', 'expense', 'Chung', '2025-05-25', '2025-05-28 00:05:55', NULL, '25/05/2025 18:22:16_40000_CUSTOMER KOVQR 8C6R8', 1),
(132, 4, 101000.00, 'CUSTOMER KOVQR 8A6Z6V  - Ma giao dich/ Trace  643528 - LE THI HONG TUYET (0947444589)', 'expense', 'Chung', '2025-05-25', '2025-05-28 00:05:55', NULL, '25/05/2025 12:59:41_101000_CUSTOMER KOVQR 8A6Z6', 1),
(133, 4, 3512000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ TB783748 - DINH THI KIM CUC (1017139246)', 'expense', 'Chung', '2025-05-25', '2025-05-28 00:05:55', NULL, '25/05/2025 01:36:21_3512000_DINH MANH HUNG DINH ', 1),
(134, 4, 68000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 879265 - TA VAN THAN (0937272662)', 'expense', 'Chung', '2025-05-24', '2025-05-28 00:05:55', NULL, '24/05/2025 12:46:59_68000_CUSTOMER DINH MANH H', 1),
(135, 4, 40000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ PF401104 - LE THIEN PHAT (1045693900)', 'expense', 'Chung', '2025-05-23', '2025-05-28 00:05:55', NULL, '23/05/2025 23:55:28_40000_DINH MANH HUNG DINH ', 1),
(136, 4, 985000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ CZ683518 - HOANG ANH KHOA (8890280617)', 'expense', 'Chung', '2025-05-23', '2025-05-28 00:05:55', NULL, '23/05/2025 22:20:57_985000_DINH MANH HUNG DINH ', 1),
(137, 4, 10000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 700648 - MOMO_BAI XE HAI ANH (99MM24159M18099610)', 'expense', 'Di chuyển', '2025-05-23', '2025-05-28 00:05:55', NULL, '23/05/2025 13:56:14_10000_CUSTOMER DINH MANH H', 1),
(138, 4, 33000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ I5880590 - BUI VAN PHUOC (107870073150)', 'expense', 'Chung', '2025-05-23', '2025-05-28 00:05:55', NULL, '23/05/2025 12:51:33_33000_DINH MANH HUNG DINH ', 1),
(139, 4, 134280.00, 'CUSTOMER POSRETAIL-514304734408-5-USD-AUTHC ODE-531614-TID-7N3QWJUF-MID-SXONGQ UGQWWJUTM-TRXCODE-', 'expense', 'Chung', '2025-05-23', '2025-05-28 00:05:55', NULL, '23/05/2025 11:47:04_134280_CUSTOMER POSRETAIL-5', 1),
(140, 4, 150000.00, 'LE TRAN QUOC BAO LE TRAN QUOC BAO Chuyen tien- Ma GD  ACSP/ Ur621892', 'income', 'Chung', '2025-05-23', '2025-05-28 00:05:56', NULL, '23/05/2025 11:48:44_150000_LE TRAN QUOC BAO LE ', 1),
(141, 4, 120000.00, 'CUSTOMER DINH MANH HUNG chuyen tien. DEN: DINH MANH HUNG - DINH MANH HUNG  (0000968948008)', 'expense', 'Chung', '2025-05-23', '2025-05-28 00:05:56', NULL, '23/05/2025 11:46:45_120000_CUSTOMER DINH MANH H', 1),
(142, 4, 10000.00, 'BUI VU HUY Qaawgm7172  APPMB1 1  BUI VU HUY CH UYEN TIEN- Ma GD ACSP/ DN863229', 'income', 'Chung', '2025-05-22', '2025-05-28 00:05:56', NULL, '22/05/2025 15:49:49_10000_BUI VU HUY Qaawgm717', 1),
(143, 4, 3550000.00, 'TRAN HUU DAT MBVCB.9593192710.165540.TRAN HUU DA T chuyen tien.CT tu 0161000879001 T RAN HUU DAT toi 669699669 DINH MANH  HUNG tai MB- Ma GD ACSP/ bl165540', 'income', 'Chung', '2025-05-23', '2025-05-28 00:05:56', NULL, '23/05/2025 00:35:47_3550000_TRAN HUU DAT MBVCB.9', 1),
(144, 4, 10000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215176634960 BP0001mhhl9s . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chung', '2025-05-22', '2025-05-28 00:05:56', NULL, '22/05/2025 15:17:40_10000_DINH MANH HUNG QRCOD', 1),
(145, 4, 5000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215176520034 BP0001mhhj8m . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chung', '2025-05-22', '2025-05-28 00:05:57', NULL, '22/05/2025 15:17:01_5000_DINH MANH HUNG QRCOD', 1),
(146, 4, 10000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215166457711 BP0001mhhk34 . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chung', '2025-05-22', '2025-05-28 00:05:57', NULL, '22/05/2025 15:16:39_10000_DINH MANH HUNG QRCOD', 1),
(147, 4, 10000.00, 'DINH MANH HUNG QRCODE VNPAY QRCODE DINHHUNG1508250 52215146026501 BP0001mhhf2e . DEN: VNPAY.,JSC#CTY CP GIAI PHAP THANH TOAN VN', 'expense', 'Chung', '2025-05-22', '2025-05-28 00:05:57', NULL, '22/05/2025 15:14:06_10000_DINH MANH HUNG QRCOD', 1),
(148, 4, 1550000.00, 'CUSTOMER DINH MANH HUNG chuyen tien - Ma gia o dich/ Trace 226544 - NGUYEN HONG SON (19035309130013)', 'expense', 'Chung', '2025-05-22', '2025-05-28 00:05:57', NULL, '22/05/2025 09:47:40_1550000_CUSTOMER DINH MANH H', 1),
(149, 4, 600000.00, 'DINH MANH HUNG DINH MANH HUNG chuyen tien- Ma GD A CSP/ Y8500980 - DANG DINH KINH (0021000527302)', 'expense', 'Chung', '2025-05-22', '2025-05-28 00:05:57', NULL, '22/05/2025 09:56:31_600000_DINH MANH HUNG DINH ', 1),
(150, 1, 300000.00, 'thức ăn', 'expense', 'Chi phí khác', '2025-05-28', '2025-05-28 14:44:07', NULL, NULL, 0),
(151, 1, 24.00, 'đồ uống', 'expense', 'Chi phí khác', '2025-05-28', '2025-05-28 14:44:07', NULL, NULL, 0),
(152, 1, 500000.00, 'Tiền lương', 'income', 'lương', '2025-05-31', '2025-05-31 15:45:02', NULL, NULL, 0),
(153, 1, 500000.00, 'Tiền lương', 'income', 'lương', '2025-05-31', '2025-05-31 15:49:24', NULL, NULL, 0),
(154, 1, 500000.00, 'Tiền lương', 'income', 'lương', '2025-05-31', '2025-05-31 15:50:47', NULL, NULL, 0),
(155, 1, 500000.00, 'Tiền lương', 'income', 'lương', '2025-05-31', '2025-05-31 15:52:57', NULL, NULL, 0),
(156, 1, 500000.00, 'Tiền lương', 'income', 'lương', '2025-05-31', '2025-05-31 23:57:44', NULL, NULL, 0),
(157, 1, 500000.00, 'Tiền lương', 'income', 'lương', '2025-05-31', '2025-06-01 00:19:13', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'Đinh Mạnh Hi', 'dinhhung1508', 'admin2@hacker2k4.com', '$2y$10$lWjCEKs4bpDbLh0aofAJVuDB7R2h5p6WHZ8h8D8nvOgF39yNNe4o2', 'admin', '2025-03-18 09:47:32', '2025-05-29 11:28:04', '2025-05-31 23:53:19'),
(2, 'Admin', 'admin', 'admin@example.com', '$2y$10$lWjCEKs4bpDbLh0aofAJVuDB7R2h5p6WHZ8h8D8nvOgF39yNNe4o2', 'admin', '2025-05-26 00:39:13', NULL, '2025-05-26 01:58:01'),
(4, 'Jacqueline Nickel', 'hung1234', 'aluong3@hacker2k4.com', '$2y$10$Ee.j.2zxYuXe1YiWKtv2..kYzj7FPf9yFUifAFJIpws4veR5ffTPu', 'user', '2025-05-27 22:13:16', NULL, '2025-05-31 17:13:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `logout_time` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_logs_admin_id` (`admin_id`),
  ADD KEY `idx_admin_logs_action` (`action`),
  ADD KEY `idx_admin_logs_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `backup_history`
--
ALTER TABLE `backup_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_backup_history_admin_id` (`admin_id`);

--
-- Chỉ mục cho bảng `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_bank` (`user_id`,`bank_username`),
  ADD KEY `idx_bank_accounts_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categories_user_id` (`user_id`),
  ADD KEY `idx_categories_type` (`type`);

--
-- Chỉ mục cho bảng `chat_history`
--
ALTER TABLE `chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_history_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_id` (`user_id`),
  ADD KEY `idx_notifications_is_read` (`is_read`),
  ADD KEY `idx_notifications_is_global` (`is_global`);

--
-- Chỉ mục cho bảng `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Chỉ mục cho bảng `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transactions_user_id` (`user_id`),
  ADD KEY `idx_transactions_date` (`date`),
  ADD KEY `idx_transactions_type` (`type`),
  ADD KEY `idx_bank_transaction_id` (`bank_transaction_id`),
  ADD KEY `idx_is_bank_import` (`is_bank_import`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_sessions_user_id` (`user_id`),
  ADD KEY `idx_user_sessions_session_id` (`session_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT cho bảng `backup_history`
--
ALTER TABLE `backup_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `chat_history`
--
ALTER TABLE `chat_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `backup_history`
--
ALTER TABLE `backup_history`
  ADD CONSTRAINT `backup_history_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `chat_history`
--
ALTER TABLE `chat_history`
  ADD CONSTRAINT `chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
