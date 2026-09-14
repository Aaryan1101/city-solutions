<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\Response;
use App\Support\VendorAuth;
use App\Support\View;
use App\Support\WalletSchema;
use App\Support\WalletService;

final class WalletController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        WalletSchema::ensure();
        $vendorId = VendorAuth::id();
        $summary = WalletService::summary('vendor', (string) $vendorId);
        $withdrawals = Database::connection()->prepare(
            'select * from withdrawal_requests where vendor_id = :vendor_id order by id desc'
        );
        $withdrawals->execute(['vendor_id' => $vendorId]);
        $settlements = Database::connection()->prepare(
            'select * from vendor_settlements where vendor_id = :vendor_id order by id desc'
        );
        $settlements->execute(['vendor_id' => $vendorId]);

        View::render('vendor/wallet', [
            'title' => 'Wallet',
            'account' => $summary['account'],
            'ledger' => $summary['ledger'],
            'withdrawals' => $withdrawals->fetchAll(),
            'settlements' => $settlements->fetchAll(),
        ]);
    }

    public function requestWithdrawal(): void
    {
        VendorAuth::requireVendor();
        WalletSchema::ensure();
        $vendorId = VendorAuth::id();
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $bankDetails = trim($_POST['bank_details'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $account = WalletService::ensureAccount('vendor', (string) $vendorId);
        if ($amount <= 0 || $amount > (float) $account['balance']) {
            Response::redirect('/vendor/wallet');
        }

        $stmt = Database::connection()->prepare(
            'insert into withdrawal_requests (vendor_id, owner_type, owner_key, amount, bank_details, note, status, created_at, updated_at)
             values (:vendor_id, \'vendor\', :owner_key, :amount, :bank_details, :note, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'vendor_id' => $vendorId,
            'owner_key' => (string) $vendorId,
            'amount' => $amount,
            'bank_details' => $bankDetails === '' ? null : $bankDetails,
            'note' => $note === '' ? null : $note,
            'status' => 'pending',
        ]);

        Response::redirect('/vendor/wallet');
    }
}
