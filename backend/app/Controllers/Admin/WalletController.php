<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\HotelSchema;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\Response;
use App\Support\VendorSchema;
use App\Support\View;
use App\Support\WalletSchema;
use App\Support\WalletService;

final class WalletController
{
    public function index(): void
    {
        Auth::requireAdmin();
        WalletSchema::ensure();
        VendorSchema::ensure();
        HotelSchema::ensure();
        $db = Database::connection();

        $accounts = $db->query('select * from wallet_accounts order by updated_at desc, id desc')->fetchAll();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $customerJoin = $driver === 'mysql'
            ? 'withdrawal_requests.owner_key = concat(\'customer-\', customers.id)'
            : 'withdrawal_requests.owner_key = (\'customer-\' || customers.id)';
        $hotelOwnerJoin = $driver === 'mysql'
            ? 'withdrawal_requests.owner_key = concat(\'hotel-owner-\', hotel_owners.id)'
            : 'withdrawal_requests.owner_key = (\'hotel-owner-\' || hotel_owners.id)';
        $withdrawals = $db->query(
            'select withdrawal_requests.*, vendors.shop_name,
                    customers.name as customer_name, customers.phone as customer_phone,
                    hotel_owners.name as hotel_owner_name, hotel_owners.phone as hotel_owner_phone
             from withdrawal_requests
             left join vendors on vendors.id = withdrawal_requests.vendor_id
             left join customers on withdrawal_requests.owner_type = \'customer\' and ' . $customerJoin . '
             left join hotel_owners on withdrawal_requests.owner_type = \'hotel_owner\' and ' . $hotelOwnerJoin . '
             order by withdrawal_requests.id desc'
        )->fetchAll();

        View::render('admin/wallets', [
            'title' => 'Wallets',
            'accounts' => $accounts,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function withdrawalStatus(int $id): void
    {
        Auth::requireAdmin();
        WalletSchema::ensure();
        NotificationSchema::ensure();
        $status = trim($_POST['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'approved', 'paid', 'rejected'], true)) {
            $status = 'pending';
        }

        $db = Database::connection();
        $lookup = $db->prepare('select * from withdrawal_requests where id = :id limit 1');
        $lookup->execute(['id' => $id]);
        $request = $lookup->fetch();
        if (!$request) {
            Response::redirect('/admin/wallets');
        }

        $adminNote = trim($_POST['admin_note'] ?? '');
        $update = $db->prepare(
            'update withdrawal_requests set status = :status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $update->execute([
            'id' => $id,
            'status' => $status,
            'admin_note' => $adminNote === '' ? null : $adminNote,
        ]);

        if ($status === 'paid') {
            $ownerType = (string) ($request['owner_type'] ?? 'vendor');
            $ownerKey = (string) ($request['owner_key'] ?: $request['vendor_id']);
            WalletService::debit(
                $ownerType,
                $ownerKey,
                (float) $request['amount'],
                'withdrawal_paid',
                'withdrawal:' . $id,
                'Withdrawal paid by admin'
            );
            if ($ownerType === 'vendor') {
                $settlement = $db->prepare(
                    'insert into vendor_settlements (vendor_id, withdrawal_id, amount, commission_amount, payment_reference, note, status, settled_at, created_at, updated_at)
                     values (:vendor_id, :withdrawal_id, :amount, 0, :payment_reference, :note, \'paid\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $settlement->execute([
                    'vendor_id' => (int) $request['vendor_id'],
                    'withdrawal_id' => $id,
                    'amount' => (float) $request['amount'],
                    'payment_reference' => trim($_POST['payment_reference'] ?? '') ?: null,
                    'note' => $adminNote === '' ? 'Withdrawal paid by admin' : $adminNote,
                ]);
            }
        }

        if (($request['owner_type'] ?? 'vendor') === 'vendor') {
            NotificationLog::record(
                'vendor',
                (int) $request['vendor_id'],
                null,
                'Withdrawal updated',
                'Your withdrawal request is now ' . $status . '.',
                null
            );
        }

        Response::redirect('/admin/wallets');
    }
}
