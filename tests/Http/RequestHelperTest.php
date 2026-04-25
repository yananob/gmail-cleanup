<?php declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\TestCase;
use App\Http\RequestHelper;

class RequestHelperTest extends TestCase
{
    private RequestHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new RequestHelper();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testFilterInputData(): void
    {
        $input = [
            'keyword' => 'test',
            'from' => '',
            'to' => null,
            'subject' => 'hello',
            'unknown' => 'value'
        ];
        $expected = [
            'keyword' => 'test',
            'subject' => 'hello'
        ];
        $this->assertEquals($expected, $this->helper->filterInputData($input));
    }

    public function testVerifyCsrfToken(): void
    {
        $_SESSION['csrf_token'] = 'valid_token';

        $this->assertTrue($this->helper->verifyCsrfToken(['csrf_token' => 'valid_token']));
        $this->assertFalse($this->helper->verifyCsrfToken(['csrf_token' => 'invalid_token']));
        $this->assertFalse($this->helper->verifyCsrfToken([]));
    }
}
