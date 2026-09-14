<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\WalletSchema;
use App\Support\WalletService;

final class WalletController
{
    public function show(): void
    {
        $customer = (new CustomerController())->walletCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }

        $guestId = 'customer-' . (int) $customer['id'];
        $summary = WalletService::summary('customer', $guestId);
        WalletSchema::ensure();
        $withdrawals = Database::connection()->prepare(
            'select * from withdrawal_requests where owner_type = :owner_type and owner_key = :owner_key order by id desc'
        );
        $withdrawals->execute([
            'owner_type' => 'customer',
            'owner_key' => $guestId,
        ]);
        Response::json([
            'data' => $summary['account'],
            'ledger' => $summary['ledger'],
            'withdrawals' => $withdrawals->fetchAll(),
        ]);
    }

    public function requestWithdrawal(): void
    {
        $customer = (new CustomerController())->walletCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }

        WalletSchema::ensure();
        $body = Request::json();
        $amount = max(0, (float) ($body['amount'] ?? 0));
        $bankDetails = trim((string) ($body['bank_details'] ?? ''));
        $note = trim((string) ($body['note'] ?? ''));
        $ownerKey = 'customer-' . (int) $customer['id'];
        if ($amount <= 0) {
            Response::json(['message' => 'Withdrawal amount is required'], 422);
            return;
        }
        if ($bankDetails === '') {
            Response::json(['message' => 'Bank details are required'], 422);
            return;
        }
        if (!WalletService::canDebit('customer', $ownerKey, $amount)) {
            Response::json(['message' => 'Insufficient wallet balance'], 422);
            return;
        }

        $stmt = Database::connection()->prepare(
            'insert into withdrawal_requests (vendor_id, owner_type, owner_key, amount, bank_details, note, status, created_at, updated_at)
             values (0, :owner_type, :owner_key, :amount, :bank_details, :note, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'owner_type' => 'customer',
            'owner_key' => $ownerKey,
            'amount' => $amount,
            'bank_details' => $bankDetails,
            'note' => $note === '' ? null : $note,
        ]);

        $this->show();
    }
}
