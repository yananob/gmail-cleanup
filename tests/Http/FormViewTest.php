<?php declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\TestCase;
use eftec\bladeone\BladeOne;

class FormViewTest extends TestCase
{
    private BladeOne $blade;

    protected function setUp(): void
    {
        $cacheDir = sys_get_temp_dir() . '/cache_' . uniqid();
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $viewsDir = dirname(__DIR__, 2) . '/views';
        $this->blade = new BladeOne($viewsDir, $cacheDir, BladeOne::MODE_DEBUG);
    }

    public function testCreateFormRendersStoreActionAndSaveButton(): void
    {
        $html = $this->blade->run('form', [
            'basePath' => '',
            'csrfToken' => 'test-token',
            'config' => [],
        ]);

        $this->assertStringContainsString('新規ルール作成', $html);
        $this->assertStringContainsString('action="/store"', $html);
        $this->assertStringContainsString('保存する', $html);
        $this->assertStringNotContainsString('action="/update"', $html);
        $this->assertStringNotContainsString('更新する', $html);
        $this->assertStringNotContainsString('ルール編集', $html);
    }

    public function testEditFormRendersUpdateActionAndUpdateButton(): void
    {
        $html = $this->blade->run('form', [
            'basePath' => '',
            'csrfToken' => 'test-token',
            'config' => ['keyword' => 'test'],
            'id' => 'rule-123',
        ]);

        $this->assertStringContainsString('ルール編集', $html);
        $this->assertStringContainsString('action="/update"', $html);
        $this->assertStringContainsString('更新する', $html);
        $this->assertStringContainsString('<input type="hidden" name="id" value="rule-123">', $html);
        $this->assertStringNotContainsString('action="/store"', $html);
        $this->assertStringNotContainsString('新規ルール作成', $html);
    }
}
