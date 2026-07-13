<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Service\Gmail;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\AppConfig;
use App\ConfigRepository;
use App\GmailCleanupHandler;
use App\Query;
use eftec\bladeone\BladeOne;
use Psr\Http\Message\ResponseInterface;
use App\Http\Controller;
use App\Http\RequestHelper;
use App\Service\GmailClientFactory;

FunctionsFramework::http('main_http', 'main_http');
function main_http(ServerRequestInterface $request): string|ResponseInterface
{
    $logger = new Logger('gmail-cleanup-http');
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

    $firebaseServiceAccount = getenv('FIREBASE_SERVICE_ACCOUNT');
    if (!$firebaseServiceAccount) {
        return 'FIREBASE_SERVICE_ACCOUNT environment variable is not set.';
    }

    $firestoreConfig = json_decode($firebaseServiceAccount, true);
    $firestore = new FirestoreClient([
        'projectId' => $firestoreConfig['project_id'] ?? null,
        'keyFile' => $firestoreConfig
    ]);

    $rootCollection = AppConfig::getFirestoreRootCollection();
    $configRepository = new ConfigRepository($firestore, $rootCollection);

    $views = __DIR__ . '/views';
    $cache = '/tmp/cache';
    if (!is_dir($cache)) {
        mkdir($cache, 0777, true);
    }
    $blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
    $basePath = AppConfig::getBasePath();

    $gmailClientFactory = new GmailClientFactory();
    $requestHelper = new RequestHelper();

    $controller = new Controller(
        $logger,
        $configRepository,
        $blade,
        $gmailClientFactory,
        $requestHelper,
        $basePath
    );

    return $controller->handle($request);
}
