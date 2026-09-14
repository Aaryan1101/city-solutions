<?php

declare(strict_types=1);

namespace App\Support;

final class FloatingAdSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $db->exec(
                'create table if not exists floating_ads (
                    id bigint unsigned auto_increment primary key,
                    title varchar(180) null,
                    message text null,
                    media_type varchar(20) not null default \'image\',
                    media_url text null,
                    cta_text varchar(80) null,
                    target_module varchar(40) not null default \'mart\',
                    target_type varchar(40) not null default \'none\',
                    target_value varchar(180) null,
                    link_url text null,
                    status tinyint(1) not null default 1,
                    priority int not null default 0,
                    starts_at datetime null,
                    ends_at datetime null,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
            return;
        }

        $db->exec(
            'create table if not exists floating_ads (
                id integer primary key autoincrement,
                title varchar(180) null,
                message text null,
                media_type varchar(20) not null default \'image\',
                media_url text null,
                cta_text varchar(80) null,
                target_module varchar(40) not null default \'mart\',
                target_type varchar(40) not null default \'none\',
                target_value varchar(180) null,
                link_url text null,
                status integer not null default 1,
                priority integer not null default 0,
                starts_at datetime null,
                ends_at datetime null,
                created_at timestamp null,
                updated_at timestamp null
            )'
        );
    }
}
