<?php

declare(strict_types=1);

return [
    "create table saved_table_views (
        id int unsigned not null auto_increment primary key,
        user_id int unsigned null,
        table_name varchar(120) not null,
        name varchar(160) not null,
        scope enum('private','shared') not null default 'private',
        filters_json json null,
        columns_json json null,
        sort_json json null,
        is_default tinyint(1) not null default 0,
        created_at timestamp null,
        updated_at timestamp null,
        constraint saved_table_views_user_id_fk foreign key (user_id) references users(id) on delete cascade,
        index saved_table_views_table_scope_idx (table_name, scope),
        index saved_table_views_user_table_idx (user_id, table_name)
    ) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci",
];
