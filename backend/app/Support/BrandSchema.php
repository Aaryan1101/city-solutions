<?php

declare(strict_types=1);

namespace App\Support;

final class BrandSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists brands (
                    id bigint unsigned primary key auto_increment,
                    name varchar(190) not null,
                    slug varchar(220) not null,
                    image varchar(255) null,
                    status tinyint(1) not null default 1,
                    sort_order int not null default 0,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
        } else {
            $db->exec(
                'create table if not exists brands (
                    id integer primary key autoincrement,
                    name text not null,
                    slug text not null,
                    image text,
                    status integer not null default 1,
                    sort_order integer not null default 0,
                    created_at text,
                    updated_at text
                )'
            );
        }

        self::ensureProductsBrandColumn($db);
    }

    private static function ensureProductsBrandColumn(\PDO $db): void
    {
        try {
            $db->query('select brand_id from products limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer';
            $db->exec('alter table products add column brand_id ' . $type);
        }
    }
}
