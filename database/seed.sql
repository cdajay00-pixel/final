USE `adssu_lams`;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`) VALUES
(1, 'Brendan', 'brendan12345', 'Brendan F. Garcia', 'brendan17@gmail.com', 'User', '2026-05-25 18:11:15'),
(2, 'Cm', 'cm12345', 'Cm G. Dajay', 'cm12@gmail.com', 'Admin', '2026-05-25 18:33:42');

INSERT INTO `assets` (`id`, `asset_name`, `category`, `quantity`, `available_quantity`, `description`, `condition`, `status`, `created_at`) VALUES
(1, 'Printer', 'Electronics', 13, 11, NULL, 'Good', 'Available', '2026-05-25 18:43:39'),
(2, 'Router', 'Electronics', 20, 20, NULL, 'Good', 'Available', '2026-05-25 22:12:00'),
(3, 'Projector', 'Electronics', 12, 10, NULL, 'Good', 'Available', '2026-05-25 22:12:52'),
(4, 'Monitor', 'Electronics', 50, 49, NULL, 'Good', 'Available', '2026-05-25 22:13:12'),
(5, 'System Unit', 'Electronics', 7, 7, NULL, 'Good', 'Available', '2026-05-25 22:14:58'),
(7, 'UTP Cable', 'Accessories', 25, 24, NULL, 'Good', 'Available', '2026-05-25 22:16:55'),
(8, 'RJ 45', 'Accessories', 25, 24, NULL, 'Good', 'Available', '2026-05-25 22:17:24'),
(9, 'Table', 'Accessories', 13, 13, NULL, 'Good', 'Available', '2026-05-25 22:17:49'),
(10, 'CPU', 'Componets', 24, 24, NULL, 'Good', 'Available', '2026-05-25 22:18:45'),
(11, 'Digital multimeter', 'Measuring Instrument', 5, 5, NULL, 'Good', 'Available', '2026-06-03 01:26:06');
