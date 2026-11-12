<?php declare(strict_types=1);

namespace App;

use Carbon\Carbon;

/**
 * Gmail検索クエリを構築するためのクラス。
 */
final class Query
{
    /**
     * 指定された条件に基づいてGmail検索クエリ文字列を構築します。
     *
     * @param array<string, mixed> $target 検索条件。
     * @return string 生成された検索クエリ文字列。
     */
    public function build(array $target): string
    {
        $parts = [];

        if (!empty($target['keyword'])) {
            $trimmed = trim((string)$target['keyword']);
            if ($trimmed !== '') {
                $parts[] = $trimmed;
            }
        }

        if (!empty($target['from'])) {
            $trimmed = trim((string)$target['from']);
            if ($trimmed !== '') {
                $parts[] = 'from:' . $trimmed;
            }
        }

        if (!empty($target['to'])) {
            $trimmed = trim((string)$target['to']);
            if ($trimmed !== '') {
                $parts[] = 'to:' . $trimmed;
            }
        }

        if (!empty($target['subject'])) {
            $trimmed = trim((string)$target['subject']);
            if ($trimmed !== '') {
                $parts[] = 'subject:' . $trimmed;
            }
        }

        if (!empty($target['label'])) {
            $trimmed = trim((string)$target['label']);
            if ($trimmed !== '') {
                $parts[] = 'label:' . $trimmed;
            }
        }

        if (!empty($target['date_before'])) {
            try {
                // 現在の日時から指定された期間を引いた日付を計算 (例: P30D -> 30日前)
                $targetDate = Carbon::now()->sub((string)$target['date_before'])->format('Y/m/d');
                $parts[] = 'before:' . $targetDate;
            } catch (\Throwable) {
                // 不正なフォーマット文字列（例: 無効な期間文字列）の場合は安全に無視する
            }
        }

        if (!empty($target['only_unread'])) {
            $parts[] = 'is:unread';
        }

        return implode(' ', $parts);
    }

    /**
     * Gmailフィルター用の条件に基づいて検索クエリ文字列を構築します。
     *
     * @param array<string, mixed> $criteria フィルター条件。
     * @return string 生成された検索クエリ文字列。
     */
    public function buildFilterQuery(array $criteria): string
    {
        $queryParts = [];

        if (!empty($criteria['from'])) {
            $trimmed = trim((string)$criteria['from']);
            if ($trimmed !== '') {
                $queryParts[] = 'from:' . $trimmed;
            }
        }

        if (!empty($criteria['to'])) {
            $trimmed = trim((string)$criteria['to']);
            if ($trimmed !== '') {
                $queryParts[] = 'to:' . $trimmed;
            }
        }

        if (!empty($criteria['subject'])) {
            $trimmed = trim((string)$criteria['subject']);
            if ($trimmed !== '') {
                $queryParts[] = 'subject:' . $trimmed;
            }
        }

        if (!empty($criteria['hasAttachment'])) {
            $queryParts[] = 'has:attachment';
        }

        if (!empty($criteria['query'])) {
            $trimmed = trim((string)$criteria['query']);
            if ($trimmed !== '') {
                $queryParts[] = $trimmed;
            }
        }

        if (!empty($criteria['negatedQuery'])) {
            $negated = trim((string)$criteria['negatedQuery']);
            if ($negated !== '') {
                if (str_contains($negated, ' ')) {
                    $queryParts[] = '-{' . $negated . '}';
                } else {
                    $queryParts[] = '-' . $negated;
                }
            }
        }

        return implode(' ', $queryParts);
    }
}
