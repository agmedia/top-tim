ALTER TABLE `reviews`
    ADD COLUMN `lang` VARCHAR(2) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL AFTER `user_id`;