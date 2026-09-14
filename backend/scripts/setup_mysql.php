<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Support/Env.php';
require_once dirname(__DIR__) . '/app/Support/Database.php';

use App\Support\Database;

$root = dirname(__DIR__);
$pdo = Database::connection();
$pdo->exec(file_get_contents($root . '/database/schema_mysql.sql'));

$adminCount = (int) $pdo->query('select count(*) from admins')->fetchColumn();
if ($adminCount === 0) {
    $stmt = $pdo->prepare('insert into admins (name, email, password, created_at, updated_at) values (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
    $stmt->execute(['Admin', 'admin@citysolutions.local', password_hash('password', PASSWORD_DEFAULT)]);
}

$categoryCount = (int) $pdo->query('select count(*) from categories')->fetchColumn();
if ($categoryCount === 0) {
    $categories = [
        ['Fruits & Vegetables', 'fruits-vegetables', 1],
        ['Dairy & Bakery', 'dairy-bakery', 2],
        ['Grocery Staples', 'grocery-staples', 3],
    ];
    $stmt = $pdo->prepare('insert into categories (name, slug, status, sort_order, created_at, updated_at) values (?, ?, 1, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
}

$productCount = (int) $pdo->query('select count(*) from products')->fetchColumn();
if ($productCount === 0) {
    $products = [
        [1, 'Fresh Apple', 'fresh-apple', 'Crisp seasonal apples.', 'kg', 180, 160, 25, 'APL-001', 1],
        [2, 'Full Cream Milk', 'full-cream-milk', 'Daily fresh milk pack.', 'litre', 68, null, 40, 'MLK-001', 1],
        [3, 'Basmati Rice', 'basmati-rice', 'Long grain basmati rice.', '5 kg', 620, 590, 18, 'RCE-001', 0],
    ];
    $stmt = $pdo->prepare('insert into products (category_id, name, slug, description, unit, price, discount_price, stock, sku, status, is_featured, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
    foreach ($products as $product) {
        $stmt->execute($product);
    }
}

$bannerCount = (int) $pdo->query('select count(*) from banners')->fetchColumn();
if ($bannerCount === 0) {
    $stmt = $pdo->prepare('insert into banners (title, link_type, link_value, status, sort_order, created_at, updated_at) values (?, ?, ?, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
    $stmt->execute(['Fresh mart essentials', 'category', '1']);
}

echo "MySQL backend schema and seed data ready.\n";
echo "Admin: admin@citysolutions.local / password\n";
