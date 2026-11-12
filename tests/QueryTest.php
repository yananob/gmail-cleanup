<?php declare(strict_types=1);

namespace Tests;

use App\Query;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Carbon\Carbon;

final class QueryTest extends TestCase
{
    public static function buildDataProvider(): array
    {
        $date_p1m = Carbon::now()->sub('P1M')->format('Y/m/d');
        $date_p2m = Carbon::now()->sub('P2M')->format('Y/m/d');
        $date_p3m = Carbon::now()->sub('P3M')->format('Y/m/d');
        $date_p6m = Carbon::now()->sub('P6M')->format('Y/m/d');

        return [
            // message => [keyword, from, to, subject, label, date_before, only_unread, expected]
            "keyword + before 1M" => ["hogehoge", null, null, null, null, "P1M", null, "hogehoge before:" . $date_p1m],
            "from + subject" => [null, "me", null, "error alert", null, null, null, "from:me subject:error alert"],
            "to + before 2M" => [null, null, "hogeo@hoge.com", null, null, "P2M", null, "to:hogeo@hoge.com before:" . $date_p2m],
            "label + before 3M" => [null, null, null, null, "mailmag", "P3M", null, "label:mailmag before:" . $date_p3m],
            "before 6M" => [null, null, null, null, null, "P6M", null, "before:" . $date_p6m],
            "only unread" => [null, null, null, null, null, null, 1, "is:unread"],
            "keyword + only unread" => ["test", null, null, null, null, null, "1", "test is:unread"],
        ];
    }

    #[DataProvider('buildDataProvider')]
    public function testBuild($keyword, $from, $to, $subject, $label, $date_before, $only_unread, $expected): void
    {
        $target = [];
        if (!is_null($keyword)) {
            $target["keyword"] = $keyword;
        }
        if (!is_null($from)) {
            $target["from"] = $from;
        }
        if (!is_null($to)) {
            $target["to"] = $to;
        }
        if (!is_null($subject)) {
            $target["subject"] = $subject;
        }
        if (!is_null($label)) {
            $target["label"] = $label;
        }
        if (!is_null($date_before)) {
            $target["date_before"] = $date_before;
        }
        if (!is_null($only_unread)) {
            $target["only_unread"] = $only_unread;
        }
        $query = new Query();
        $this->assertEquals(trim($expected), trim($query->build($target)));
    }

    public function testBuildHandlesInvalidDateBefore(): void
    {
        $query = new Query();
        $result = $query->build([
            'keyword' => 'test',
            'date_before' => 'invalid_period_string'
        ]);
        $this->assertEquals('test', $result);
    }

    public function testBuildTrimsInputs(): void
    {
        $query = new Query();
        $result = $query->build([
            'keyword' => '  hello world  ',
            'from' => '  user@example.com  ',
            'to' => '  dest@example.com  ',
            'subject' => '  urgent alert  ',
            'label' => '  inbox  ',
        ]);
        $this->assertEquals('hello world from:user@example.com to:dest@example.com subject:urgent alert label:inbox', $result);
    }

    public function testBuildFilterQuery(): void
    {
        $query = new Query();

        // 空の条件
        $this->assertSame('', $query->buildFilterQuery([]));

        // 全条件の組み合わせ
        $criteria = [
            'from' => ' sender@example.com ',
            'to' => ' recipient@example.com ',
            'subject' => ' Monthly Report ',
            'hasAttachment' => true,
            'query' => ' quarterly results ',
            'negatedQuery' => ' confidential draft ',
        ];
        $expected = 'from:sender@example.com to:recipient@example.com subject:Monthly Report has:attachment quarterly results -{confidential draft}';
        $this->assertSame($expected, $query->buildFilterQuery($criteria));

        // 否定クエリが1単語の場合
        $criteriaSingleWordNegation = [
            'negatedQuery' => 'spam',
        ];
        $this->assertSame('-spam', $query->buildFilterQuery($criteriaSingleWordNegation));
    }
}
