ALTER TABLE `products`
    ADD COLUMN `shipping_time` VARCHAR(191) NOT NULL  AFTER `year`,
    ADD COLUMN `author_web_url` VARCHAR(191) NOT NULL AFTER `year`,
    ADD COLUMN `serial_web_url` VARCHAR(191) NOT NULL AFTER `year`,
    ADD COLUMN `wiki_url` VARCHAR(191) NOT NULL AFTER `year`,
    ADD COLUMN `youtube_channel` VARCHAR(191) NOT NULL AFTER `year`,
    ADD COLUMN `youtube_product_url` VARCHAR(191) NOT NULL AFTER `year`,
    ADD COLUMN `goodreads_author_url` VARCHAR(191) NOT NULL AFTER `year`,
    ADD COLUMN `goodreads_book_url` VARCHAR(191) NOT NULL AFTER `year`;

