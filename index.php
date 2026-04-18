<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;

FunctionsFramework::http('main_http', 'main_http');
function main_http(ServerRequestInterface $request): string
{
    return "Hello, World!";
}

FunctionsFramework::cloudEvent('main_event', 'main_event');
function main_event(CloudEventInterface $event): void
{
    $logger = new \Monolog\Logger('cloud_event_logger');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::INFO));
    $logger->log("Hello, World!");
}
