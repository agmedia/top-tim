CREATE TABLE `sizeguide` (
                       `id` bigint UNSIGNED NOT NULL,
                       `group` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                       `sort_order` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                       `status` tinyint(1) DEFAULT NULL,
                       `created_at` timestamp NULL DEFAULT NULL,
                       `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `sizeguide`
    ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `faq`
--
ALTER TABLE `sizeguide`
    MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;



CREATE TABLE `sizeguide_translations` (
                                    `id` bigint UNSIGNED NOT NULL,
                                    `sizeguide_id` bigint UNSIGNED NOT NULL,
                                    `lang` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
                                    `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                                    `image` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                                    `created_at` timestamp NULL DEFAULT NULL,
                                    `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `sizeguide_translations`
    ADD PRIMARY KEY (`id`),
  ADD KEY `sizeguide_translations_sizeguide_id_index` (`sizeguide_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `faq_translations`
--
ALTER TABLE `sizeguide_translations`
    MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `faq_translations`
--
ALTER TABLE `sizeguide_translations`
    ADD CONSTRAINT `sizeguide_translations_sizeguide_id_foreign` FOREIGN KEY (`sizeguide_id`) REFERENCES `sizeguide` (`id`) ON DELETE CASCADE;
COMMIT;
