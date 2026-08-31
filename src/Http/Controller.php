<?php declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\ConfigRepository;
use App\GmailService;
use App\GmailFilterService;
use App\Query;
use eftec\bladeone\BladeOne;
use GuzzleHttp\Psr7\Response;
use Google\Service\Gmail;
use App\Service\GmailClientFactory;

class Controller
{
    public function __construct(
        private LoggerInterface $logger,
        private ConfigRepository $configRepository,
        private BladeOne $blade,
        private GmailClientFactory $gmailClientFactory,
        private RequestHelper $requestHelper,
        private string $basePath
    ) {}

    public function handle(ServerRequestInterface $request): string|ResponseInterface
    {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Remove basePath from URI for routing if it exists
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }
        if ($uri === '') $uri = '/';

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
                $this->logger->info('Fetching all configs');
                $configs = $this->configRepository->getAll();
                return $this->blade->run('index', [
                    'configs' => $configs,
                    'basePath' => $this->basePath,
                    'message' => $message,
                    'csrfToken' => $csrfToken
                ]);
            }

            if ($method === 'GET' && $uri === '/create') {
                $this->logger->info('Displaying create form');
                $initialConfig = [];
                if (!empty($queryParams['from'])) {
                    $initialConfig['from'] = (string)$queryParams['from'];
                }
                if (!empty($queryParams['subject'])) {
                    $initialConfig['subject'] = (string)$queryParams['subject'];
                }
                return $this->blade->run('form', [
                    'basePath' => $this->basePath,
                    'csrfToken' => $csrfToken,
                    'config' => $initialConfig,
                ]);
            }

            if ($method === 'POST' && $uri === '/store') {
                $this->logger->info('Storing new config');
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->warning('Store failed: Invalid CSRF token');
                    return 'Invalid CSRF token';
                }
                $data = $this->requestHelper->filterInputData($body);
                $this->configRepository->create($data);
                $this->logger->info('Config created successfully', ['data' => $data]);
                return new Response(302, ['Location' => $this->basePath . '/?message=' . urlencode('作成しました')]);
            }

            if ($method === 'GET' && $uri === '/edit') {
                $id = $queryParams['id'] ?? null;
                $this->logger->info('Displaying edit form', ['id' => $id]);
                if (!$id) return 'ID is required';
                $config = $this->configRepository->find((string)$id);
                if (!$config) {
                    $this->logger->warning('Config not found for edit', ['id' => $id]);
                    return 'Config not found';
                }
                return $this->blade->run('form', [
                    'config' => $config,
                    'id' => $id,
                    'basePath' => $this->basePath,
                    'csrfToken' => $csrfToken
                ]);
            }

            if ($method === 'POST' && $uri === '/update') {
                $id = $body['id'] ?? null;
                $this->logger->info('Updating config', ['id' => $id]);
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->warning('Update failed: Invalid CSRF token');
                    return 'Invalid CSRF token';
                }
                if (!$id) return 'ID is required';
                $data = $this->requestHelper->filterInputData($body);
                unset($data['id']);
                $this->configRepository->update((string)$id, $data);
                $this->logger->info('Config updated successfully', ['id' => $id]);
                return new Response(302, ['Location' => $this->basePath . '/?message=' . urlencode('更新しました')]);
            }

            if ($method === 'POST' && $uri === '/delete') {
                $id = $body['id'] ?? null;
                $this->logger->info('Deleting config', ['id' => $id]);
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->warning('Delete failed: Invalid CSRF token');
                    return 'Invalid CSRF token';
                }
                if (!$id) return 'ID is required';
                $this->configRepository->delete((string)$id);
                $this->logger->info('Config deleted successfully', ['id' => $id]);
                return new Response(302, ['Location' => $this->basePath . '/?message=' . urlencode('削除しました')]);
            }

            if ($method === 'POST' && $uri === '/preview') {
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->error('Preview failed: Invalid CSRF token');
                    return new Response(403, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Invalid CSRF token']));
                }

                try {
                    $client = $this->gmailClientFactory->create();
                    $gmailService = $this->gmailClientFactory->createGmailService($client);
                    $gmailAppService = new GmailService($gmailService);

                    $data = $this->requestHelper->filterInputData($body);
                    $queryBuilder = new Query();
                    $query = $queryBuilder->build($data);
                    $this->logger->info('Preview request', ['query' => $query]);

                    $messages = $gmailAppService->listMessages($query, 20);
                    return new Response(200, ['Content-Type' => 'application/json'], (string)json_encode($messages));
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    if (str_contains($errorMsg, 'unauthorized_client') || str_contains($errorMsg, 'invalid_grant')) {
                        $this->logger->error('Preview failed: Authentication error. If using Domain-Wide Delegation, check Admin Console settings. If using OAuth, your refresh token may be invalid.', ['error' => $errorMsg]);
                        return new Response(401, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Authentication Error: ' . $errorMsg]));
                    } else {
                        $this->logger->error('Preview failed', ['error' => $errorMsg, 'trace' => $e->getTraceAsString()]);
                    }
                    return new Response(500, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Internal Server Error: ' . $errorMsg]));
                }
            }

            if ($method === 'GET' && $uri === '/filters') {
                $this->logger->info('Fetching all Gmail filters');
                $client = $this->gmailClientFactory->create();
                $gmailService = $this->gmailClientFactory->createGmailService($client);
                $gmailFilterService = new GmailFilterService($gmailService);

                $filters = $gmailFilterService->listFilters();
                return $this->blade->run('filters_index', [
                    'filters' => $filters,
                    'basePath' => $this->basePath,
                    'message' => $message,
                    'csrfToken' => $csrfToken
                ]);
            }

            if ($method === 'GET' && $uri === '/filters/create') {
                $this->logger->info('Displaying Gmail filter create form');
                return $this->blade->run('filters_form', [
                    'basePath' => $this->basePath,
                    'csrfToken' => $csrfToken
                ]);
            }

            if ($method === 'GET' && $uri === '/filters/edit') {
                $id = $queryParams['id'] ?? null;
                $this->logger->info('Displaying Gmail filter edit form', ['id' => $id]);
                if (!$id) return 'ID is required';

                $client = $this->gmailClientFactory->create();
                $gmailService = $this->gmailClientFactory->createGmailService($client);
                $gmailFilterService = new GmailFilterService($gmailService);

                $filter = $gmailFilterService->getFilter((string)$id);
                if (!$filter) {
                    $this->logger->warning('Filter not found for edit', ['id' => $id]);
                    return 'Filter not found';
                }

                return $this->blade->run('filters_form', [
                    'filter' => $filter,
                    'id' => $id,
                    'basePath' => $this->basePath,
                    'csrfToken' => $csrfToken
                ]);
            }

            if ($method === 'POST' && $uri === '/filters/preview') {
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->error('Filter preview failed: Invalid CSRF token');
                    return new Response(403, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Invalid CSRF token']));
                }

                try {
                    $client = $this->gmailClientFactory->create();
                    $gmailService = $this->gmailClientFactory->createGmailService($client);
                    $gmailAppService = new GmailService($gmailService);

                    $queryParts = [];
                    if (!empty($body['from'])) {
                        $queryParts[] = 'from:' . trim((string)$body['from']);
                    }
                    if (!empty($body['to'])) {
                        $queryParts[] = 'to:' . trim((string)$body['to']);
                    }
                    if (!empty($body['subject'])) {
                        $queryParts[] = 'subject:' . trim((string)$body['subject']);
                    }
                    if (!empty($body['hasAttachment'])) {
                        $queryParts[] = 'has:attachment';
                    }
                    if (!empty($body['query'])) {
                        $queryParts[] = trim((string)$body['query']);
                    }
                    if (!empty($body['negatedQuery'])) {
                        $negated = trim((string)$body['negatedQuery']);
                        if (str_contains($negated, ' ')) {
                            $queryParts[] = '-{' . $negated . '}';
                        } else {
                            $queryParts[] = '-' . $negated;
                        }
                    }

                    $query = implode(' ', $queryParts);
                    $this->logger->info('Filter preview request', ['query' => $query]);

                    $messages = $gmailAppService->listMessages($query, 20);
                    return new Response(200, ['Content-Type' => 'application/json'], (string)json_encode($messages));
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    if (str_contains($errorMsg, 'unauthorized_client') || str_contains($errorMsg, 'invalid_grant')) {
                        $this->logger->error('Filter preview failed: Authentication error', ['error' => $errorMsg]);
                        return new Response(401, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Authentication Error: ' . $errorMsg]));
                    } else {
                        $this->logger->error('Filter preview failed', ['error' => $errorMsg, 'trace' => $e->getTraceAsString()]);
                    }
                    return new Response(500, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Internal Server Error: ' . $errorMsg]));
                }
            }

            if ($method === 'POST' && $uri === '/filters/store') {
                $this->logger->info('Storing new Gmail filter');
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->warning('Filter store failed: Invalid CSRF token');
                    return 'Invalid CSRF token';
                }

                $client = $this->gmailClientFactory->create();
                $gmailService = $this->gmailClientFactory->createGmailService($client);
                $gmailFilterService = new GmailFilterService($gmailService);

                $criteria = [];
                if (!empty($body['from'])) $criteria['from'] = trim((string)$body['from']);
                if (!empty($body['to'])) $criteria['to'] = trim((string)$body['to']);
                if (!empty($body['subject'])) $criteria['subject'] = trim((string)$body['subject']);
                if (!empty($body['query'])) $criteria['query'] = trim((string)$body['query']);
                if (!empty($body['negatedQuery'])) $criteria['negatedQuery'] = trim((string)$body['negatedQuery']);
                if (!empty($body['hasAttachment'])) $criteria['hasAttachment'] = true;

                $action = [];
                $addLabels = $this->extractLabels($body['addLabel'] ?? null);
                if (!empty($addLabels)) {
                    $action['addLabelIds'] = $addLabels;
                }
                $removeLabels = $this->extractLabels($body['removeLabel'] ?? null);
                if (!empty($removeLabels)) {
                    $action['removeLabelIds'] = $removeLabels;
                }
                if (!empty($body['forward'])) $action['forward'] = trim((string)$body['forward']);

                $gmailFilterService->createFilter($criteria, $action);
                $this->logger->info('Filter created successfully');
                return new Response(302, ['Location' => $this->basePath . '/filters?message=' . urlencode('フィルターを作成しました')]);
            }

            if ($method === 'POST' && $uri === '/filters/update') {
                $id = $body['id'] ?? null;
                $this->logger->info('Updating Gmail filter', ['id' => $id]);
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->warning('Filter update failed: Invalid CSRF token');
                    return 'Invalid CSRF token';
                }
                if (!$id) return 'ID is required';

                $client = $this->gmailClientFactory->create();
                $gmailService = $this->gmailClientFactory->createGmailService($client);
                $gmailFilterService = new GmailFilterService($gmailService);

                $criteria = [];
                if (!empty($body['from'])) $criteria['from'] = trim((string)$body['from']);
                if (!empty($body['to'])) $criteria['to'] = trim((string)$body['to']);
                if (!empty($body['subject'])) $criteria['subject'] = trim((string)$body['subject']);
                if (!empty($body['query'])) $criteria['query'] = trim((string)$body['query']);
                if (!empty($body['negatedQuery'])) $criteria['negatedQuery'] = trim((string)$body['negatedQuery']);
                if (!empty($body['hasAttachment'])) $criteria['hasAttachment'] = true;

                $action = [];
                $addLabels = $this->extractLabels($body['addLabel'] ?? null);
                if (!empty($addLabels)) {
                    $action['addLabelIds'] = $addLabels;
                }
                $removeLabels = $this->extractLabels($body['removeLabel'] ?? null);
                if (!empty($removeLabels)) {
                    $action['removeLabelIds'] = $removeLabels;
                }
                if (!empty($body['forward'])) $action['forward'] = trim((string)$body['forward']);

                $gmailFilterService->createFilter($criteria, $action);
                $gmailFilterService->deleteFilter((string)$id);
                $this->logger->info('Filter updated successfully', ['id' => $id]);
                return new Response(302, ['Location' => $this->basePath . '/filters?message=' . urlencode('フィルターを更新しました')]);
            }

            if ($method === 'POST' && $uri === '/filters/delete') {
                $id = $body['id'] ?? null;
                $this->logger->info('Deleting Gmail filter', ['id' => $id]);
                if (!$this->requestHelper->verifyCsrfToken($body)) {
                    $this->logger->warning('Filter delete failed: Invalid CSRF token');
                    return 'Invalid CSRF token';
                }
                if (!$id) return 'ID is required';

                $client = $this->gmailClientFactory->create();
                $gmailService = $this->gmailClientFactory->createGmailService($client);
                $gmailFilterService = new GmailFilterService($gmailService);

                $gmailFilterService->deleteFilter((string)$id);
                $this->logger->info('Filter deleted successfully', ['id' => $id]);
                return new Response(302, ['Location' => $this->basePath . '/filters?message=' . urlencode('フィルターを削除しました')]);
            }
        } catch (\Exception $e) {
            $this->logger->error('An error occurred in Controller', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'Error: ' . $e->getMessage();
        }

        return "Gmail Cleanup Service is running. Path: " . $uri;
    }

    /**
     * @param mixed $input
     * @return string[]
     */
    private function extractLabels(mixed $input): array
    {
        if (empty($input)) {
            return [];
        }
        if (is_array($input)) {
            $labels = array_map(fn($v) => trim((string)$v), $input);
        } else {
            $labels = array_map('trim', explode(',', (string)$input));
        }
        return array_values(array_filter($labels, fn($v) => $v !== ''));
    }
}
