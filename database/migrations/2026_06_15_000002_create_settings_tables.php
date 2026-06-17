<?php

declare(strict_types=1);

return [
    "create table settings (
        setting_key varchar(120) not null primary key,
        setting_value mediumtext null,
        setting_type varchar(40) not null default 'string',
        updated_by int unsigned null,
        created_at timestamp null,
        updated_at timestamp null,
        constraint settings_updated_by_fk foreign key (updated_by) references users(id) on delete set null
    ) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci",

    "create table user_settings (
        user_id int unsigned not null,
        setting_key varchar(120) not null,
        setting_value mediumtext null,
        created_at timestamp null,
        updated_at timestamp null,
        primary key (user_id, setting_key),
        constraint user_settings_user_id_fk foreign key (user_id) references users(id) on delete cascade
    ) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci",
];
