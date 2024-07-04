CREATE TABLE `user_group` (
                           `id` bigint auto_increment PRIMARY KEY UNSIGNED NOT NULL,
                           `parent_id` bigint NOT NULL,
                           `status` tinyint(1) DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
