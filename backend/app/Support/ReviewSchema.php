<?php

declare(strict_types=1);

namespace App\Support;

final class ReviewSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists product_reviews (
                    id bigint unsigned primary key auto_increment,
                    product_id bigint unsigned not null,
                    vendor_id bigint unsigned null,
                    guest_id varchar(120) not null,
                    customer_name varchar(190) null,
                    rating tinyint unsigned not null default 5,
                    comment text null,
                    reply text null,
                    status tinyint(1) not null default 1,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index product_reviews_product_id_index (product_id),
                    index product_reviews_vendor_id_index (vendor_id)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists product_reviews (
                    id integer primary key autoincrement,
                    product_id integer not null,
                    vendor_id integer,
                    guest_id text not null,
                    customer_name text,
                    rating integer not null default 5,
                    comment text,
                    reply text,
                    status integer not null default 1,
                    created_at text,
                    updated_at text
                )'
            );
        }
    }
}
