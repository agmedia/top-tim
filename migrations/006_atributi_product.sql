ALTER TABLE `products` ADD `sastojci` TEXT  NULL AFTER `description`;
ALTER TABLE `products` ADD `podaci` TEXT  NULL AFTER `description`;
ALTER TABLE `products` ADD `vegan` tinyint(1) NOT NULL DEFAULT '0' AFTER `description`;
ALTER TABLE `products` ADD `vegetarian` tinyint(1) NOT NULL DEFAULT '0' AFTER `description`;
ALTER TABLE `products` ADD `glutenfree` tinyint(1) NOT NULL DEFAULT '0' AFTER `description`;
