<?php

declare(strict_types=1);

namespace App\Support;

final class Upload
{
    public static function image(string $field, string $folder): ?string
    {
        if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = $_FILES[$field]['tmp_name'];
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', $_FILES[$field]['name']);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeExtension = self::validatedImageExtension($tmp, (int) ($_FILES[$field]['size'] ?? 0), $extension);
        if ($safeExtension === null) {
            return null;
        }

        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $safeExtension;
        $directory = self::uploadDirectory($folder);
        if ($directory === null) {
            return null;
        }

        if (!move_uploaded_file($tmp, $directory . '/' . $fileName)) {
            return null;
        }
        return '/uploads/' . $folder . '/' . $fileName;
    }

    public static function media(string $field, string $folder): ?array
    {
        if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = $_FILES[$field]['tmp_name'];
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', $_FILES[$field]['name']);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $validated = self::validatedAdMediaExtension($tmp, (int) ($_FILES[$field]['size'] ?? 0), $extension);
        if ($validated === null) {
            return null;
        }

        [$safeExtension, $mediaType] = $validated;
        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $safeExtension;
        $directory = self::uploadDirectory($folder);
        if ($directory === null) {
            return null;
        }

        if (!move_uploaded_file($tmp, $directory . '/' . $fileName)) {
            return null;
        }
        return [
            'path' => '/uploads/' . $folder . '/' . $fileName,
            'media_type' => $mediaType,
        ];
    }

    public static function images(string $field, string $folder): array
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'] ?? null)) {
            return [];
        }

        $paths = [];
        $files = $_FILES[$field];
        foreach ($files['name'] as $index => $name) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name);
            $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
            $safeExtension = self::validatedImageExtension($files['tmp_name'][$index], (int) ($files['size'][$index] ?? 0), $extension);
            if ($safeExtension === null) {
                continue;
            }

            $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $safeExtension;
            $directory = self::uploadDirectory($folder);
            if ($directory === null) {
                continue;
            }
            if (!move_uploaded_file($files['tmp_name'][$index], $directory . '/' . $fileName)) {
                continue;
            }
            $paths[] = '/uploads/' . $folder . '/' . $fileName;
        }

        return $paths;
    }

    public static function base64Document(string $base64, string $folder, string $name = 'prescription'): ?string
    {
        $document = self::decodeBase64Document($base64);
        if ($document === null) {
            return null;
        }
        [$binary, $extension] = $document;

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name) ?: 'document';
        $fileName = date('YmdHis') . '-' . $safeName . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $directory = self::uploadDirectory($folder);
        if ($directory === null) {
            return null;
        }
        if (file_put_contents($directory . '/' . $fileName, $binary, LOCK_EX) === false) {
            return null;
        }
        return '/uploads/' . $folder . '/' . $fileName;
    }

    public static function base64Image(string $base64, string $folder, string $name = 'image'): ?string
    {
        $image = self::decodeBase64Image($base64);
        if ($image === null) {
            return null;
        }
        [$binary, $extension] = $image;

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name) ?: 'image';
        $safeName = pathinfo($safeName, PATHINFO_FILENAME) ?: 'image';
        $fileName = date('YmdHis') . '-' . $safeName . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $directory = self::uploadDirectory($folder);
        if ($directory === null) {
            return null;
        }
        if (file_put_contents($directory . '/' . $fileName, $binary, LOCK_EX) === false) {
            return null;
        }
        return '/uploads/' . $folder . '/' . $fileName;
    }

    public static function isValidBase64Document(string $base64): bool
    {
        return self::decodeBase64Document($base64) !== null;
    }

    private static function decodeBase64Document(string $base64): ?array
    {
        $base64 = trim($base64);
        if ($base64 === '') {
            return null;
        }
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }
        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary) ?: '';
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => '',
        };
        if ($extension === '') {
            return null;
        }

        return [$binary, $extension];
    }

    private static function decodeBase64Image(string $base64): ?array
    {
        $base64 = trim($base64);
        if ($base64 === '') {
            return null;
        }
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }
        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary) ?: '';
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => '',
        };
        if ($extension === '') {
            return null;
        }

        return [$binary, $extension];
    }

    private static function validatedImageExtension(string $tmp, int $size, string $extension): ?string
    {
        if ($size <= 0 || $size > 5 * 1024 * 1024 || !is_uploaded_file($tmp)) {
            return null;
        }
        $allowedByMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $safeExtension = $allowedByMime[$mime] ?? null;
        if ($safeExtension === null) {
            return null;
        }
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        return $safeExtension;
    }

    private static function uploadDirectory(string $folder): ?string
    {
        $folder = trim($folder, '/');
        if ($folder === '' || str_contains($folder, '..') || !preg_match('#^[a-zA-Z0-9/_-]+$#', $folder)) {
            return null;
        }
        $root = dirname(__DIR__, 2) . '/public/uploads';
        $directory = $root . '/' . $folder;
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            return null;
        }
        self::protectUploadDirectory($root);
        self::protectUploadDirectory($directory);
        return is_writable($directory) ? $directory : null;
    }

    private static function protectUploadDirectory(string $directory): void
    {
        $htaccess = $directory . '/.htaccess';
        $content = "Options -Indexes\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar\nRemoveType .php .phtml .php3 .php4 .php5 .php7 .php8 .phar\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|php8|phar)$\">\n    Require all denied\n</FilesMatch>\n";
        if (is_file($htaccess) && file_get_contents($htaccess) === $content) {
            return;
        }
        @file_put_contents($htaccess, $content, LOCK_EX);
    }

    private static function validatedAdMediaExtension(string $tmp, int $size, string $extension): ?array
    {
        if ($size <= 0 || $size > 50 * 1024 * 1024 || !is_uploaded_file($tmp)) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $allowed = [
            'image/jpeg' => ['jpg', 'image', ['jpg', 'jpeg']],
            'image/png' => ['png', 'image', ['png']],
            'image/webp' => ['webp', 'image', ['webp']],
            'video/mp4' => ['mp4', 'video', ['mp4']],
            'video/quicktime' => ['mov', 'video', ['mov']],
            'video/webm' => ['webm', 'video', ['webm']],
        ];
        $match = $allowed[$mime] ?? null;
        if ($match === null || !in_array($extension, $match[2], true)) {
            return null;
        }

        return [$match[0], $match[1]];
    }
}
