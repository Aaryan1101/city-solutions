<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\ComplaintSchema;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class ComplaintController
{
    public function index(): void
    {
        Auth::requireAdmin();
        ComplaintSchema::ensure();
        $rows = Database::connection()->query('select * from complaints order by id desc limit 200')->fetchAll();
        View::render('admin/complaints', ['title' => 'Complaints', 'complaints' => $rows]);
    }

    public function status(int $id): void
    {
        Auth::requireAdmin();
        ComplaintSchema::ensure();
        $status = trim((string) ($_POST['status'] ?? 'pending'));
        if (!in_array($status, ['pending', 'in_review', 'resolved', 'rejected'], true)) {
            $status = 'pending';
        }
        Database::connection()->prepare(
            'update complaints set status = :status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute([
            'id' => $id,
            'status' => $status,
            'admin_note' => trim((string) ($_POST['admin_note'] ?? '')) ?: null,
        ]);
        Response::redirect('/admin/complaints');
    }
}
