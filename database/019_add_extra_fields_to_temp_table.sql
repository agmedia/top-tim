ALTER TABLE `temp_table`
    ADD COLUMN `sku` VARCHAR(14) NULL AFTER `product_id`,
    ADD COLUMN `quantity` INT NULL AFTER `sku`,
    ADD COLUMN `price` decimal(15,4) NULL AFTER `quantity`;

