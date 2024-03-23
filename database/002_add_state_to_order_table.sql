ALTER TABLE `orders`
    ADD COLUMN  `payment_state` varchar(191) NOT NULL AFTER `payment_city`;

ALTER TABLE `orders`
    ADD COLUMN `shipping_state` varchar(191) NOT NULL AFTER `shipping_city`;

ALTER TABLE `orders` CHANGE `company` `company` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `orders` CHANGE `oib` `oib` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
