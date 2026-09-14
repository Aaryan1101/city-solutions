<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\Request;
use App\Support\RestaurantSchema;
use App\Support\View;
use App\Support\ZoneSchema;

final class RestaurantController
{
    public function index(): void
    {
        Auth::requireAdmin();
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();

        $restaurants = $db->prepare(
            'select restaurants.*, restaurant_categories.name as category_name, zones.name as zone_name
             from restaurants
             left join restaurant_categories on restaurant_categories.id = restaurants.category_id
             left join zones on zones.id = restaurants.zone_id
             where 1 = 1' . Auth::zoneWhere('restaurants') . '
             order by restaurants.id desc'
        );
        $restaurants->execute(Auth::zoneParams());

        $bookings = $db->prepare(
            'select restaurant_bookings.*, restaurants.name as restaurant_name,
                    restaurant_payment_transactions.id as payment_transaction_id,
                    restaurant_payment_transactions.reference as payment_reference,
                    restaurant_payment_transactions.status as transaction_status,
                    restaurant_payment_transactions.reconciled_at as payment_reconciled_at
             from restaurant_bookings
             join restaurants on restaurants.id = restaurant_bookings.restaurant_id
             left join restaurant_payment_transactions on restaurant_payment_transactions.booking_id = restaurant_bookings.id
             where 1 = 1' . Auth::zoneWhere('restaurant_bookings') . '
             order by restaurant_bookings.id desc
             limit 80'
        );
        $bookings->execute(Auth::zoneParams());

        $waitlists = $db->prepare(
            'select restaurant_waitlists.*, restaurants.name as restaurant_name
             from restaurant_waitlists
             join restaurants on restaurants.id = restaurant_waitlists.restaurant_id
             where 1 = 1' . Auth::zoneWhere('restaurant_waitlists') . '
             order by restaurant_waitlists.id desc
             limit 120'
        );
        $waitlists->execute(Auth::zoneParams());

        $foodItems = $db->prepare(
            'select restaurant_food_items.*, restaurants.name as restaurant_name, restaurant_food_categories.name as category_name
             from restaurant_food_items
             join restaurants on restaurants.id = restaurant_food_items.restaurant_id
             left join restaurant_food_categories on restaurant_food_categories.id = restaurant_food_items.category_id
             where 1 = 1' . Auth::zoneWhere('restaurants') . '
             order by restaurant_food_items.id desc'
        );
        $foodItems->execute(Auth::zoneParams());

        $foodCategories = $db->query('select * from restaurant_food_categories order by sort_order asc, id asc')->fetchAll();

        $foodOrders = $db->prepare(
            'select restaurant_food_orders.*, restaurants.name as restaurant_name
             from restaurant_food_orders
             join restaurants on restaurants.id = restaurant_food_orders.restaurant_id
             where 1 = 1' . Auth::zoneWhere('restaurant_food_orders') . '
             order by restaurant_food_orders.id desc
             limit 100'
        );
        $foodOrders->execute(Auth::zoneParams());

        View::render('admin/restaurants', [
            'title' => 'Restaurants',
            'restaurants' => $restaurants->fetchAll(),
            'bookings' => $bookings->fetchAll(),
            'waitlists' => $waitlists->fetchAll(),
            'foodItems' => $foodItems->fetchAll(),
            'foodCategories' => $foodCategories,
            'foodOrders' => $foodOrders->fetchAll(),
        ]);
    }

    public function storeFoodItem(): void
    {
        Auth::requireAdmin();
        RestaurantSchema::ensure();
        $restaurantId = (int) Request::input('restaurant_id');
        $categoryId = (int) Request::input('category_id');
        $name = trim((string) Request::input('name'));
        if ($restaurantId <= 0 || $name === '') {
            $this->back('/admin/restaurants#food-items');
        }
        $price = max(0, (float) Request::input('price'));
        $discount = trim((string) Request::input('discount_price'));
        Database::connection()->prepare(
            'insert into restaurant_food_items
             (restaurant_id, category_id, name, slug, description, price, discount_price, image, prep_time_minutes, is_veg, stock, status, is_featured, created_at, updated_at)
             values
             (:restaurant_id, :category_id, :name, :slug, :description, :price, :discount_price, :image, :prep_time_minutes, :is_veg, :stock, :status, :is_featured, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        )->execute([
            'restaurant_id' => $restaurantId,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'name' => $name,
            'slug' => $this->slug($name),
            'description' => trim((string) Request::input('description')) ?: null,
            'price' => $price,
            'discount_price' => $discount === '' ? null : max(0, (float) $discount),
            'image' => trim((string) Request::input('image')) ?: null,
            'prep_time_minutes' => max(5, (int) Request::input('prep_time_minutes', 25)),
            'is_veg' => Request::input('is_veg') ? 1 : 0,
            'stock' => max(0, (int) Request::input('stock', 100)),
            'status' => Request::input('status') ? 1 : 0,
            'is_featured' => Request::input('is_featured') ? 1 : 0,
        ]);
        $this->back('/admin/restaurants#food-items');
    }

    public function foodOrderStatus(int $id): void
    {
        Auth::requireAdmin();
        RestaurantSchema::ensure();
        NotificationSchema::ensure();
        $status = $this->cleanStatus((string) Request::input('order_status'), [
            'pending',
            'confirmed',
            'preparing',
            'out_for_delivery',
            'delivered',
            'cancelled',
            'rejected',
        ], 'pending');
        $paymentStatus = $this->cleanStatus((string) Request::input('payment_status'), ['unpaid', 'pending_verification', 'paid', 'failed', 'refunded'], 'unpaid');
        $fields = 'order_status = :status, payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP';
        if ($status === 'confirmed') {
            $fields .= ', confirmed_at = CURRENT_TIMESTAMP';
        }
        if ($status === 'delivered') {
            $fields .= ', delivered_at = CURRENT_TIMESTAMP';
        }
        if (in_array($status, ['cancelled', 'rejected'], true)) {
            $fields .= ', cancelled_at = CURRENT_TIMESTAMP';
        }
        $db = Database::connection();
        $orderStmt = $db->prepare('select * from restaurant_food_orders where id = :id limit 1');
        $orderStmt->execute(['id' => $id]);
        $order = $orderStmt->fetch();
        if (!$order) {
            $this->back('/admin/restaurants#food-orders');
        }
        $db->prepare('update restaurant_food_orders set ' . $fields . ' where id = :id')
            ->execute(['id' => $id, 'status' => $status, 'payment_status' => $paymentStatus]);
        NotificationLog::record('customer', null, (string) ($order['guest_id'] ?? ''), 'Food order updated', ($order['order_number'] ?? 'Food order') . ' is now ' . $status . '.', $id, 'restaurant');
        $this->back('/admin/restaurants#food-orders');
    }

    public function bookingStatus(int $id): void
    {
        Auth::requireAdmin();
        RestaurantSchema::ensure();
        NotificationSchema::ensure();
        $status = $this->cleanStatus((string) Request::input('booking_status'), [
            'pending',
            'confirmed',
            'seated',
            'completed',
            'cancel_requested',
            'cancelled',
            'rejected',
            'no_show',
        ], 'pending');
        $db = Database::connection();
        $bookingStmt = $db->prepare(
            'select restaurant_bookings.*, restaurants.name as restaurant_name
             from restaurant_bookings
             join restaurants on restaurants.id = restaurant_bookings.restaurant_id
             where restaurant_bookings.id = :id
             limit 1'
        );
        $bookingStmt->execute(['id' => $id]);
        $booking = $bookingStmt->fetch();
        if (!$booking) {
            $this->back('/admin/restaurants#bookings');
        }
        $fields = 'booking_status = :status, payment_status = :payment_status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP';
        if ($status === 'confirmed') {
            $fields .= ', confirmed_at = CURRENT_TIMESTAMP';
        }
        if (in_array($status, ['cancelled', 'rejected', 'no_show'], true)) {
            $fields .= ', cancelled_at = CURRENT_TIMESTAMP';
        }
        if ($status === 'completed') {
            $fields .= ', completed_at = CURRENT_TIMESTAMP';
        }
        $paymentStatus = $this->cleanStatus((string) Request::input('payment_status'), ['unpaid', 'pending_verification', 'paid', 'failed', 'refunded'], 'unpaid');
        $stmt = $db->prepare('update restaurant_bookings set ' . $fields . ' where id = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ]);
        $transactionStatus = match ($paymentStatus) {
            'unpaid' => 'pending',
            'pending_verification' => 'pending_verification',
            'paid' => 'verified',
            'failed' => 'rejected',
            'refunded' => 'refunded',
            default => 'pending',
        };
        $verifiedSql = $transactionStatus === 'verified' ? ', verified_at = CURRENT_TIMESTAMP' : '';
        $db->prepare(
            'update restaurant_payment_transactions
             set status = :status, note = :note, reconciled_at = CURRENT_TIMESTAMP,
                 reconciled_by = :reconciled_by' . $verifiedSql . ', updated_at = CURRENT_TIMESTAMP
             where booking_id = :booking_id'
        )->execute([
            'booking_id' => $id,
            'status' => $transactionStatus,
            'note' => trim((string) Request::input('admin_note')) ?: null,
            'reconciled_by' => $_SESSION['admin_name'] ?? 'Admin',
        ]);
        if (($booking['booking_status'] ?? '') !== $status || ($booking['payment_status'] ?? '') !== $paymentStatus) {
            $parts = ['status: ' . $status];
            if (($booking['payment_status'] ?? '') !== $paymentStatus) {
                $parts[] = 'payment: ' . $paymentStatus;
            }
            NotificationLog::record(
                'customer',
                null,
                (string) ($booking['guest_id'] ?? ''),
                'Restaurant booking updated',
                ($booking['booking_number'] ?? 'Booking') . ' at ' . ($booking['restaurant_name'] ?? 'restaurant') . ' updated (' . implode(', ', $parts) . ').',
                $id,
                'restaurant'
            );
            NotificationLog::record(
                'admin',
                null,
                null,
                'Restaurant booking updated',
                ($booking['booking_number'] ?? 'Booking') . ' marked ' . $status,
                $id,
                'restaurant'
            );
        }
        $this->back('/admin/restaurants#bookings');
    }

    public function waitlistStatus(int $id): void
    {
        Auth::requireAdmin();
        RestaurantSchema::ensure();
        $status = $this->cleanStatus((string) Request::input('status'), [
            'pending',
            'contacted',
            'converted',
            'expired',
            'cancelled',
        ], 'pending');
        $stmt = Database::connection()->prepare(
            'update restaurant_waitlists
             set status = :status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ]);
        NotificationLog::record('admin', null, null, 'Restaurant waitlist updated', 'Waitlist request #' . $id . ' marked ' . $status, $id, 'restaurant');
        $this->back('/admin/restaurants#waitlist');
    }

    private function cleanStatus(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
        return $slug !== '' ? $slug : 'food-' . time();
    }

    private function back(string $fallback): void
    {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $fallback));
        exit;
    }
}
