CREATE TABLE `jobs` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `type` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL,
        `target` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL,
        `time` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL,
        `success` TINYINT(1) NOT NULL,
        `payload` LONGTEXT NOT NULL,
        `response` TEXT NOT NULL,
        `send_report` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`id`));