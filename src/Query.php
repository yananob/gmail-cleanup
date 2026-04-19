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
            $parts[] = $target['keyword'];
        }

        if (!empty($target['from'])) {
            $parts[] = 'from:' . $target['from'];
        }

        if (!empty($target['to'])) {
            $parts[] = 'to:' . $target['to'];
        }

        if (!empty($target['subject'])) {
            $parts[] = 'subject:' . $target['subject'];
        }

        if (!empty($target['label'])) {
            $parts[] = 'label:' . $target['label'];
        }

        if (!empty($target['date_before'])) {
            // 現在の日時から指定された期間を引いた日付を計算 (例: P30D -> 30日前)
            $targetDate = Carbon::now()->sub($target['date_before'])->format('Y/m/d');
            $parts[] = 'before:' . $targetDate;
        }

        return implode(' ', $parts);
    }
}
