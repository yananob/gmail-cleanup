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

FunctionsFramework::http('main_http', 'main_http');
function main_http(ServerRequestInterface $request): string
{
    return "Gmail Cleanup Service is running.";
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
    // Note: In Cloud Functions, it's often better to use Application Default Credentials
    // but here we follow the pattern of using a service account if provided or expected.
    $client = new Google\Client();
    $client->setAuthConfig($firestoreConfig);
    $client->addScope(Gmail::GMAIL_MODIFY);
    $service = new Gmail($client);

    $rootCollection = AppConfig::getFirestoreRootCollection();
    $configRepository = new ConfigRepository($firestore, $rootCollection);
    $query = new Query();

    $handler = new GmailCleanupHandler($logger, $service, $query, $configRepository);
    $handler->handle($event);
}
