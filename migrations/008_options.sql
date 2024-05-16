create table options
(
    id         bigint auto_increment
        primary key,
    `group`    varchar(191)           null,
    type       varchar(191)           null,
    value      varchar(191)           null,
    `data`     text                   null,
    sort_order int unsigned default 0 null,
    status     tinyint(1)             null,
    created_at timestamp              null,
    updated_at timestamp              null
)
    collate = utf8mb4_unicode_ci;

create table options_translations
(
    id           bigint auto_increment
        primary key,
    attribute_id bigint unsigned         not null,
    lang         varchar(2) default 'en' not null,
    group_title  varchar(191)            not null,
    title        varchar(191)            not null,
    description  text                    null,
    created_at   timestamp               null,
    updated_at   timestamp               null
)
    collate = utf8mb4_unicode_ci;

