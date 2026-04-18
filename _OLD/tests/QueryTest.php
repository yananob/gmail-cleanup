<?php declare(strict_types=1);

use MyApp\Query;

final class QueryTest extends PHPUnit\Framework\TestCase
{
    public static function buildDataProvider(): array
    {
        $date_p1m = (new \DateTime())->sub(new \DateInterval("P1M"))->format('Y/m/d');
        $date_p2m = (new \DateTime())->sub(new \DateInterval("P2M"))->format('Y/m/d');
        $date_p3m = (new \DateTime())->sub(new \DateInterval("P3M"))->format('Y/m/d');
        $date_p6m = (new \DateTime())->sub(new \DateInterval("P6M"))->format('Y/m/d');

        return [
            // message => [keyword, from, to, subject, label, date_before, expected]
            "keyword + before 1M" => ["hogehoge", null, null, null, null, "P1M", "hogehoge before:" . $date_p1m],
            "from + subject" => [null, "me", null, "error alert", null, null, "from:me subject:error alert"],
            "to + before 2M" => [null, null, "hogeo@hoge.com", null, null, "P2M", "to:hogeo@hoge.com before:" . $date_p2m],
            "label + before 3M" => [null, null, null, null, "mailmag", "P3M", "label:mailmag before:" . $date_p3m],
            "before 6M" => [null, null, null, null, null, "P6M", "before:" . $date_p6m],
        ];
    }

    /**
     * @dataProvider buildDataProvider
     */
    public function testBuild($keyword, $from, $to, $subject, $label, $date_before, $expected): void
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
        $query = new Query();
        $this->assertEquals(ltrim($query->build($target)), ltrim($expected));
    }
}
