CREATE TABLE `reviews` (
                           `id` bigint(20) UNSIGNED NOT NULL,
                           `product_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
                           `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
                           `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
                           `lang` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hr',
                           `fname` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
                           `lname` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                           `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                           `avatar` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media/avatar.jpg',
                           `message` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
                           `stars` decimal(4,2) NOT NULL DEFAULT '0.00',
                           `sort_order` int(11) NOT NULL DEFAULT '0',
                           `featured` tinyint(1) NOT NULL DEFAULT '0',
                           `status` tinyint(1) NOT NULL DEFAULT '1',
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `reviews` (`id`, `product_id`, `order_id`, `user_id`, `lang`, `fname`, `lname`, `email`, `avatar`, `message`, `stars`, `sort_order`, `featured`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 0, 'hr', 'Filip', 'Jankoski', 'filip.jankoski@gmail.com', 'media/avatar.jpg', '<p>Neka rečenica koja u listi ne bi trebala biti dulja od 100 znakova. Mada je 100 znakova tek tu negdje.</p>', '3.60', 0, 1, 1, '2022-09-16 20:44:34', '2022-09-17 15:22:17');


ALTER TABLE `reviews`
    ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_product_id_index` (`product_id`);


ALTER TABLE `reviews`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
