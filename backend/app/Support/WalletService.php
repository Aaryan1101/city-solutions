<?php

declare(strict_types=1);

namespace App\Support;

final class WalletService
{
    public static function summary(string $ownerType, string $ownerKey): array
    {
        self::ensureSchemaOutsideTransaction();
        $account = self::ensureAccount($ownerType, $ownerKey);
        $ledger = Database::connection()->prepare(
            'select * from wallet_ledgers where owner_type = :owner_type and owner_key = :owner_key order by id desc'
        );
        $ledger->execute([
            'owner_type' => $ownerType,
            'owner_key' => $ownerKey,
        ]);

        return [
            'account' => $account,
            'ledger' => $ledger->fetchAll(),
        ];
    }

    public static function credit(
        string $ownerType,
        string $ownerKey,
        float $amount,
        string $entryType,
        ?string $referenceKey = null,
        string $description = ''
    ): void {
        self::addEntry($ownerType, $ownerKey, 'credit', $amount, $entryType, $referenceKey, $description);
    }

    public static function debit(
        string $ownerType,
        string $ownerKey,
        float $amount,
        string $entryType,
        ?string $referenceKey = null,
        string $description = ''
    ): void {
        self::addEntry($ownerType, $ownerKey, 'debit', $amount, $entryType, $referenceKey, $description);
    }

    public static function canDebit(string $ownerType, string $ownerKey, float $amount): bool
    {
        $account = self::ensureAccount($ownerType, $ownerKey);
        return (float) $account['balance'] >= $amount;
    }

    public static function ensureAccount(string $ownerType, string $ownerKey): array
    {
        self::ensureSchemaOutsideTransaction();
        $db = Database::connection();
        $select = $db->prepare('select * from wallet_accounts where owner_type = :owner_type and owner_key = :owner_key limit 1');
        $select->execute([
            'owner_type' => $ownerType,
            'owner_key' => $ownerKey,
        ]);
        $account = $select->fetch();
        if ($account) {
            $account['balance'] = (float) $account['balance'];
            return $account;
        }

        try {
            $insert = $db->prepare(
                'insert into wallet_accounts (owner_type, owner_key, balance, created_at, updated_at)
                 values (:owner_type, :owner_key, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'owner_type' => $ownerType,
                'owner_key' => $ownerKey,
            ]);
        } catch (\PDOException $exception) {
            $select->execute([
                'owner_type' => $ownerType,
                'owner_key' => $ownerKey,
            ]);
            $account = $select->fetch();
            if ($account) {
                $account['balance'] = (float) $account['balance'];
                return $account;
            }
            throw $exception;
        }

        return [
            'id' => (int) $db->lastInsertId(),
            'owner_type' => $ownerType,
            'owner_key' => $ownerKey,
            'balance' => 0.0,
        ];
    }

    private static function addEntry(
        string $ownerType,
        string $ownerKey,
        string $direction,
        float $amount,
        string $entryType,
        ?string $referenceKey,
        string $description
    ): void {
        if ($amount <= 0) {
            return;
        }

        self::ensureSchemaOutsideTransaction();
        $db = Database::connection();
        $account = self::ensureAccount($ownerType, $ownerKey);
        $ownsTransaction = !$db->inTransaction();

        try {
            if ($ownsTransaction) {
                $db->beginTransaction();
            }

            if ($referenceKey !== null && $referenceKey !== '') {
                $duplicate = $db->prepare(
                    'select id from wallet_ledgers where owner_type = :owner_type and owner_key = :owner_key and reference_key = :reference_key limit 1'
                );
                $duplicate->execute([
                    'owner_type' => $ownerType,
                    'owner_key' => $ownerKey,
                    'reference_key' => $referenceKey,
                ]);
                if ($duplicate->fetch()) {
                    if ($ownsTransaction) {
                        $db->commit();
                    }
                    return;
                }
            }

            $delta = $direction === 'credit' ? $amount : ($amount * -1);
            $stmt = $db->prepare(
                'insert into wallet_ledgers (wallet_account_id, owner_type, owner_key, direction, amount, entry_type, reference_key, description, created_at, updated_at)
                 values (:wallet_account_id, :owner_type, :owner_key, :direction, :amount, :entry_type, :reference_key, :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                'wallet_account_id' => $account['id'],
                'owner_type' => $ownerType,
                'owner_key' => $ownerKey,
                'direction' => $direction,
                'amount' => $amount,
                'entry_type' => $entryType,
                'reference_key' => $referenceKey,
                'description' => $description === '' ? null : $description,
            ]);

            $where = 'id = :id';
            if ($direction === 'debit') {
                $where .= ' and balance >= :amount';
            }
            $update = $db->prepare(
                'update wallet_accounts set balance = balance + :delta, updated_at = CURRENT_TIMESTAMP where ' . $where
            );
            $params = [
                'id' => $account['id'],
                'delta' => $delta,
            ];
            if ($direction === 'debit') {
                $params['amount'] = $amount;
            }
            $update->execute($params);
            if ($direction === 'debit' && $update->rowCount() === 0) {
                throw new \RuntimeException('Insufficient wallet balance');
            }

            if ($ownsTransaction) {
                $db->commit();
            }
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private static function ensureSchemaOutsideTransaction(): void
    {
        $db = Database::connection();
        if (!$db->inTransaction()) {
            WalletSchema::ensure();
        }
    }
}
