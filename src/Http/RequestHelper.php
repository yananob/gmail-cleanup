<?php declare(strict_types=1);

namespace App\Http;

class RequestHelper
{
    /**
     * 入力データをフィルタリングして、空の値を削除します。
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function filterInputData(array $data): array
    {
        $allowedKeys = ['keyword', 'from', 'to', 'subject', 'label', 'date_before'];
        $filtered = [];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $filtered[$key] = $data[$key];
            }
        }
        return $filtered;
    }

    /**
     * CSRFトークンを検証します。
     *
     * @param array<string, mixed> $body
     * @return bool
     */
    public function verifyCsrfToken(array $body): bool
    {
        if (empty($body['csrf_token']) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $body['csrf_token']);
    }
}
