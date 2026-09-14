<?php

declare(strict_types=1);

namespace App\Support;

final class PrescriptionSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec('create table if not exists medical_prescriptions (
                id bigint unsigned primary key auto_increment,
                order_id bigint unsigned not null,
                guest_id varchar(190) null,
                customer_name varchar(190) null,
                customer_phone varchar(60) null,
                reference varchar(190) null,
                file_path varchar(255) null,
                note text null,
                status varchar(40) not null default \'pending\',
                admin_note text null,
                reviewed_by varchar(190) null,
                reviewed_at timestamp null,
                created_at timestamp null,
                updated_at timestamp null,
                index medical_prescriptions_order_index (order_id),
                index medical_prescriptions_status_index (status)
            )');
        } else {
            $db->exec('create table if not exists medical_prescriptions (
                id integer primary key autoincrement,
                order_id integer not null,
                guest_id text,
                customer_name text,
                customer_phone text,
                reference text,
                file_path text,
                note text,
                status text not null default "pending",
                admin_note text,
                reviewed_by text,
                reviewed_at text,
                created_at text,
                updated_at text
            )');
        }
    }
}
