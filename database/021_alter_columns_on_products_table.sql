ALTER TABLE `products`
    CHANGE COLUMN `decrease` `decrease` INT(11) NULL DEFAULT '1' AFTER `shipping_time`,
    CHANGE COLUMN `goodreads_book_url` `goodreads_book_url` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
    CHANGE COLUMN `goodreads_author_url` `goodreads_author_url` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
    CHANGE COLUMN `youtube_product_url` `youtube_product_url` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
    CHANGE COLUMN `youtube_channel` `youtube_channel` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
    CHANGE COLUMN `wiki_url` `wiki_url` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
    CHANGE COLUMN `serial_web_url` `serial_web_url` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
    CHANGE COLUMN `author_web_url` `author_web_url` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
    CHANGE COLUMN `shipping_time` `shipping_time` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ;