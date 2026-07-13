<?php declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\TestCase;
use App\Http\Controller;
use App\Http\RequestHelper;
use App\ConfigRepository;
use App\Service\GmailClientFactory;
use Psr\Log\LoggerInterface;
use eftec\bladeone\BladeOne;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Response;
use Google\Client;
use Google\Service\Gmail;

class ControllerTest extends TestCase
{
    private $logger;
    private $configRepository;
    private $blade;
    private $gmailClientFactory;
    private $requestHelper;
    private $controller;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configRepository = $this->createMock(ConfigRepository::class);
        $this->blade = $this->createMock(BladeOne::class);
        $this->gmailClientFactory = $this->createMock(GmailClientFactory::class);
        $this->requestHelper = new RequestHelper();

        $this->controller = new Controller(
            $this->logger,
            $this->configRepository,
            $this->blade,
            $this->gmailClientFactory,
            $this->requestHelper,
            ''
        );

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testIndexAction(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getQueryParams')->willReturn([]);

        $this->configRepository->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $this->blade->expects($this->once())
            ->method('run')
            ->with('index', $this->callback(function($args) {
                return array_key_exists('configs', $args) && array_key_exists('csrfToken', $args);
            }))
            ->willReturn('html content');

        $response = $this->controller->handle($request);
        $this->assertEquals('html content', $response);
    }

    public function testCreateAction(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/create');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');

        $this->blade->expects($this->once())
            ->method('run')
            ->with('form', $this->callback(function($args) {
                return array_key_exists('csrfToken', $args);
            }))
            ->willReturn('form content');

        $response = $this->controller->handle($request);
        $this->assertEquals('form content', $response);
    }

    public function testStoreAction(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/store');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('POST');

        $_SESSION['csrf_token'] = 'token';
        $request->method('getParsedBody')->willReturn(['csrf_token' => 'token', 'keyword' => 'test']);

        $this->configRepository->expects($this->once())
            ->method('create')
            ->with(['keyword' => 'test']);

        $response = $this->controller->handle($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testDeleteAction(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/delete');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('POST');

        $_SESSION['csrf_token'] = 'token';
        $request->method('getParsedBody')->willReturn(['csrf_token' => 'token', 'id' => '123']);

        $this->configRepository->expects($this->once())
            ->method('delete')
            ->with('123');

        $response = $this->controller->handle($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRunCleanupAction(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/run-cleanup');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('POST');

        $_SESSION['csrf_token'] = 'token';
        $request->method('getParsedBody')->willReturn(['csrf_token' => 'token']);

        $client = $this->createMock(Client::class);
        $this->gmailClientFactory->method('create')->willReturn($client);

        // We expect handle to be called.
        // Since we can't easily mock GmailCleanupHandler (it's instantiated with 'new' in Controller),
        // this test might fail if it tries to do real things.
        // However, GmailCleanupHandler depends on $service (Gmail), which we can try to mock.
        // In Controller.php: $service = new Gmail($client);

        $this->configRepository->method('getTargets')->willReturn([]);

        $response = $this->controller->handle($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/?message=', (string)$response->getHeaderLine('Location'));
    }

    public function testRunCleanupActionWithAuthorizationHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/run-cleanup');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('POST');
        $request->method('hasHeader')->with('Authorization')->willReturn(true);
        $request->method('getParsedBody')->willReturn([]); // No CSRF token

        $client = $this->createMock(Client::class);
        $this->gmailClientFactory->method('create')->willReturn($client);
        $this->configRepository->method('getTargets')->willReturn([]);

        $response = $this->controller->handle($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/?message=', (string)$response->getHeaderLine('Location'));
    }
}
