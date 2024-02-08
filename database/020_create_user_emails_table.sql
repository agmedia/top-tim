CREATE TABLE `user_emails` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `user_id` bigint(20) NOT NULL,
                               `target` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
                               `key` varchar(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                               `mail_data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
                               `sent_at` timestamp NULL DEFAULT NULL,
                               `clicked` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                               `comment` text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
