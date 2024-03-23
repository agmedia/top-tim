ALTER TABLE `orders`
    ADD COLUMN  `payment_state` varchar(191) NOT NULL AFTER `payment_city`;

ALTER TABLE `orders`
    ADD COLUMN `shipping_state` varchar(191) NOT NULL AFTER `shipping_city`;
