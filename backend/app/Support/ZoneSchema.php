<?php

declare(strict_types=1);

namespace App\Support;

final class ZoneSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        $mysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        if ($mysql) {
            $db->exec('create table if not exists zones (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                city varchar(120) null,
                state varchar(120) null,
                pincode varchar(40) null,
                pincodes text null,
                latitude decimal(10,7) null,
                longitude decimal(10,7) null,
                radius_km decimal(8,2) not null default 0,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
        } else {
            $db->exec('create table if not exists zones (
                id integer primary key autoincrement,
                name text not null,
                city text,
                state text,
                pincode text,
                pincodes text,
                latitude real,
                longitude real,
                radius_km real not null default 0,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
        }

        foreach (['products', 'vendors', 'orders', 'hotels', 'hotel_bookings', 'services', 'service_providers', 'service_bookings'] as $table) {
            self::addColumnIfTableExists($table, 'zone_id', $mysql ? 'bigint unsigned null' : 'integer');
        }
        self::addColumnIfTableExists('zones', 'pincodes', $mysql ? 'text null' : 'text');
        self::addColumnIfTableExists('zones', 'latitude', $mysql ? 'decimal(10,7) null' : 'real');
        self::addColumnIfTableExists('zones', 'longitude', $mysql ? 'decimal(10,7) null' : 'real');
        self::addColumnIfTableExists('zones', 'radius_km', $mysql ? 'decimal(8,2) not null default 0' : 'real not null default 0');
    }

    public static function active(): array
    {
        self::ensure();
        return Database::connection()
            ->query('select id, name, city, state, pincode, pincodes, latitude, longitude, radius_km from zones where status = 1 order by sort_order asc, name asc')
            ->fetchAll();
    }

    public static function resolve(float $latitude, float $longitude, string $pincode = '', string $city = ''): ?array
    {
        self::ensure();
        $zones = self::active();
        $normalizedPincode = preg_replace('/\D+/', '', $pincode);
        if ($normalizedPincode !== '') {
            foreach ($zones as $zone) {
                $pincodes = self::zonePincodes($zone);
                if (in_array($normalizedPincode, $pincodes, true)) {
                    return $zone;
                }
            }
        }

        $best = null;
        $bestDistance = null;
        foreach ($zones as $zone) {
            $zoneLat = (float) ($zone['latitude'] ?? 0);
            $zoneLng = (float) ($zone['longitude'] ?? 0);
            $radius = (float) ($zone['radius_km'] ?? 0);
            if ($zoneLat == 0.0 || $zoneLng == 0.0 || $radius <= 0) {
                continue;
            }
            $distance = self::distanceKm($latitude, $longitude, $zoneLat, $zoneLng);
            if ($distance <= $radius && ($bestDistance === null || $distance < $bestDistance)) {
                $best = $zone;
                $bestDistance = $distance;
            }
        }
        if ($best !== null) {
            return $best;
        }

        $normalizedCity = strtolower(trim($city));
        if ($normalizedCity !== '') {
            foreach ($zones as $zone) {
                if (strtolower(trim((string) ($zone['city'] ?? ''))) === $normalizedCity) {
                    return $zone;
                }
            }
        }

        return null;
    }

    private static function zonePincodes(array $zone): array
    {
        $values = [(string) ($zone['pincode'] ?? '')];
        $extra = preg_split('/[\s,;|]+/', (string) ($zone['pincodes'] ?? '')) ?: [];
        $values = array_merge($values, $extra);
        return array_values(array_unique(array_filter(array_map(
            static fn (string $value): string => preg_replace('/\D+/', '', $value),
            $values
        ))));
    }

    private static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function requestZoneId(): int
    {
        return max(0, (int) ($_GET['zone_id'] ?? $_POST['zone_id'] ?? 0));
    }

    public static function sql(string $alias): string
    {
        return self::requestZoneId() > 0 ? ' and (' . $alias . '.zone_id is null or ' . $alias . '.zone_id = :zone_id)' : '';
    }

    public static function inlineSql(string $alias): string
    {
        $zoneId = self::requestZoneId();
        return $zoneId > 0 ? ' and (' . $alias . '.zone_id is null or ' . $alias . '.zone_id = ' . $zoneId . ')' : '';
    }

    public static function params(): array
    {
        $zoneId = self::requestZoneId();
        return $zoneId > 0 ? ['zone_id' => $zoneId] : [];
    }

    private static function addColumnIfTableExists(string $table, string $column, string $definition): void
    {
        $db = Database::connection();
        $mysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        try {
            if ($mysql) {
                $exists = $db->prepare('select count(*) from information_schema.tables where table_schema = database() and table_name = :table');
                $exists->execute(['table' => $table]);
                if ((int) $exists->fetchColumn() === 0) {
                    return;
                }
                $stmt = $db->prepare('select count(*) from information_schema.columns where table_schema = database() and table_name = :table and column_name = :column');
                $stmt->execute(['table' => $table, 'column' => $column]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $definition);
                }
                return;
            }

            $exists = $db->query("select name from sqlite_master where type='table' and name='" . str_replace("'", "''", $table) . "'")->fetch();
            if (!$exists) {
                return;
            }
            foreach ($db->query('pragma table_info(' . $table . ')')->fetchAll() as $existing) {
                if (($existing['name'] ?? '') === $column) {
                    return;
                }
            }
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $definition);
        } catch (\Throwable) {
            // Zone support should not break an older partially uploaded backend.
        }
    }
}
