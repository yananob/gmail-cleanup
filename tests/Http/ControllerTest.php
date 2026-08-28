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

    public function testFiltersIndexAction(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/filters');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getQueryParams')->willReturn([]);

        $clientMock = $this->createMock(Client::class);
        $gmailMock = $this->createMock(Gmail::class);
        $usersSettingsFiltersMock = $this->createMock(\Google\Service\Gmail\Resource\UsersSettingsFilters::class);
        $listResponse = new \Google\Service\Gmail\ListFiltersResponse();
        $listResponse->setFilter([]);
        $usersSettingsFiltersMock->method('listUsersSettingsFilters')->willReturn($listResponse);
        $gmailMock->users_settings_filters = $usersSettingsFiltersMock;

        $this->gmailClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->gmailClientFactory->expects($this->once())
            ->method('createGmailService')
            ->with($clientMock)
            ->willReturn($gmailMock);

        $this->blade->expects($this->once())
            ->method('run')
            ->with('filters_index', $this->callback(function($args) {
                return array_key_exists('filters', $args) && array_key_exists('csrfToken', $args);
            }))
            ->willReturn('filters index html');

        $response = $this->controller->handle($request);
        $this->assertEquals('filters index html', $response);
    }

    public function testFiltersCreateAction(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/filters/create');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');

        $this->blade->expects($this->once())
            ->method('run')
            ->with('filters_form', $this->callback(function($args) {
                return array_key_exists('csrfToken', $args);
            }))
            ->willReturn('filters form html');

        $response = $this->controller->handle($request);
        $this->assertEquals('filters form html', $response);
    }
}
