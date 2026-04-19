<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Client;
use Google\Service\Gmail;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\AppConfig;
use App\ConfigRepository;
use App\GmailCleanupHandler;
use App\GmailService;
use App\Query;
use eftec\bladeone\BladeOne;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

FunctionsFramework::http('main_http', 'main_http');
function main_http(ServerRequestInterface $request): string|ResponseInterface
{
    $firebaseServiceAccount = getenv('FIREBASE_SERVICE_ACCOUNT');
    if (!$firebaseServiceAccount) {
        return 'FIREBASE_SERVICE_ACCOUNT environment variable is not set.';
    }

    $firestoreConfig = json_decode($firebaseServiceAccount, true);
    $firestore = new FirestoreClient([
        'keyFile' => $firestoreConfig
    ]);

    $rootCollection = AppConfig::getFirestoreRootCollection();
    $configRepository = new ConfigRepository($firestore, $rootCollection);

    $logger = new Logger('gmail-cleanup-http');
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

    $views = __DIR__ . '/views';
    $cache = '/tmp/cache';
    if (!is_dir($cache)) {
        mkdir($cache, 0777, true);
    }
    $blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
    $basePath = AppConfig::getBasePath();

    $uri = $request->getUri()->getPath();
    // Remove basePath from URI for routing if it exists
    if ($basePath !== '' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }
    if ($uri === '') $uri = '/';

    $method = $request->getMethod();
    $queryParams = $request->getQueryParams();
    $body = (array)$request->getParsedBody();
    $message = $queryParams['message'] ?? null;

    // CSRF Protection
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    try {
        if ($method === 'GET' && $uri === '/') {
            $configs = $configRepository->getAll();
            return $blade->run('index', ['configs' => $configs, 'basePath' => $basePath, 'message' => $message, 'csrfToken' => $csrfToken]);
        }

        if ($method === 'GET' && $uri === '/create') {
            return $blade->run('form', ['basePath' => $basePath, 'csrfToken' => $csrfToken]);
        }

        if ($method === 'POST' && $uri === '/store') {
            if (!verify_csrf_token($body)) return 'Invalid CSRF token';
            $data = filter_input_data($body);
            $configRepository->create($data);
            return new Response(302, ['Location' => $basePath . '/?message=' . urlencode('作成しました')]);
        }

        if ($method === 'GET' && $uri === '/edit') {
            $id = $queryParams['id'] ?? null;
            if (!$id) return 'ID is required';
            $config = $configRepository->find($id);
            if (!$config) return 'Config not found';
            return $blade->run('form', ['config' => $config, 'id' => $id, 'basePath' => $basePath, 'csrfToken' => $csrfToken]);
        }

        if ($method === 'POST' && $uri === '/update') {
            if (!verify_csrf_token($body)) return 'Invalid CSRF token';
            $id = $body['id'] ?? null;
            if (!$id) return 'ID is required';
            $data = filter_input_data($body);
            unset($data['id']);
            $configRepository->update((string)$id, $data);
            return new Response(302, ['Location' => $basePath . '/?message=' . urlencode('更新しました')]);
        }

        if ($method === 'POST' && $uri === '/delete') {
            if (!verify_csrf_token($body)) return 'Invalid CSRF token';
            $id = $body['id'] ?? null;
            if (!$id) return 'ID is required';
            $configRepository->delete((string)$id);
            return new Response(302, ['Location' => $basePath . '/?message=' . urlencode('削除しました')]);
        }

        if ($method === 'POST' && $uri === '/preview') {
            if (!verify_csrf_token($body)) {
                $logger->error('Preview failed: Invalid CSRF token');
                return new Response(403, ['Content-Type' => 'application/json'], json_encode(['error' => 'Invalid CSRF token']));
            }

            try {
                // Google Client for Gmail API
                $client = new Client();
                $client->setAuthConfig($firestoreConfig);
                $client->addScope(Gmail::GMAIL_MODIFY);
                if ($userEmail = AppConfig::getGmailUserEmail()) {
                    $client->setSubject($userEmail);
                }
                $gmailService = new Gmail($client);
                $gmailAppService = new GmailService($gmailService);

                $data = filter_input_data($body);
                $queryBuilder = new Query();
                $query = $queryBuilder->build($data);
                $logger->info('Preview request', ['query' => $query]);

                $messages = $gmailAppService->listMessages($query, 100);
                return new Response(200, ['Content-Type' => 'application/json'], json_encode($messages));
            } catch (\Exception $e) {
                $logger->error('Preview failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Internal Server Error']));
            }
        }
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }

    return "Gmail Cleanup Service is running. Path: " . $uri;
}

/**
 * 入力データをフィルタリングして、空の値を削除します。
 */
function filter_input_data(array $data): array
{
    $allowedKeys = ['keyword', 'from', 'to', 'subject', 'label', 'date_before'];
    $filtered = [];
    foreach ($allowedKeys as $key) {
        if (!empty($data[$key])) {
            $filtered[$key] = $data[$key];
        }
    }
    return $filtered;
}

/**
 * CSRFトークンを検証します。
 */
function verify_csrf_token(array $body): bool
{
    if (empty($body['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $body['csrf_token']);
}


FunctionsFramework::cloudEvent('main_event', 'main_event');
function main_event(CloudEventInterface $event): void
{
    $logger = new Logger('gmail-cleanup');
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

    $firebaseServiceAccount = getenv('FIREBASE_SERVICE_ACCOUNT');
    if (!$firebaseServiceAccount) {
        $logger->error('FIREBASE_SERVICE_ACCOUNT environment variable is not set.');
        return;
    }

    $firestoreConfig = json_decode($firebaseServiceAccount, true);
    $firestore = new FirestoreClient([
        'keyFile' => $firestoreConfig
    ]);

    // Google Client for Gmail API
    $client = new Google\Client();
    $client->setAuthConfig($firestoreConfig);
    $client->addScope(Gmail::GMAIL_MODIFY);
    if ($userEmail = AppConfig::getGmailUserEmail()) {
        $client->setSubject($userEmail);
    }
    $service = new Gmail($client);

    $rootCollection = AppConfig::getFirestoreRootCollection();
    $configRepository = new ConfigRepository($firestore, $rootCollection);
    $query = new Query();

    $handler = new GmailCleanupHandler($logger, $service, $query, $configRepository);
    $handler->handle($event);
}
