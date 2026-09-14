<?php

declare(strict_types=1);

namespace App\Support;

final class WishlistSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists wishlists (
                    id bigint unsigned primary key auto_increment,
                    guest_id varchar(120) not null,
                    product_id bigint unsigned not null,
                    created_at timestamp null,
                    unique key wishlists_guest_product_unique (guest_id, product_id),
                    index wishlists_guest_id_index (guest_id)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists wishlists (
                    id integer primary key autoincrement,
                    guest_id text not null,
                    product_id integer not null,
                    created_at text
                )'
            );
            $db->exec('create unique index if not exists wishlists_guest_product_unique on wishlists (guest_id, product_id)');
        }
    }
}
