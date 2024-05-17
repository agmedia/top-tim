create table product_option
(
    id         bigint unsigned auto_increment
        primary key,
    product_id bigint unsigned not null,
    option_id  bigint unsigned not null,
    sku        varchar(191)   not null,
    quantity   int unsigned default 0 not null,
    price      decimal(15, 4) not null,
    `data`     text null,
    status     tinyint(1) default 0 not null,
    created_at timestamp null,
    updated_at timestamp null
) collate = utf8mb4_unicode_ci;

