<?php declare(strict_types=1);

namespace App;

use CloudEvents\V1\CloudEventInterface;
use Google\Service\Gmail;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * Gmailの整理処理を規定するハンドラクラス。
 */
class GmailCleanupHandler
{
    /**
     * @param LoggerInterface $logger
     * @param Gmail $service
     * @param Query $query
     * @param ConfigRepository $configRepository
     * @param array<int, string> $filteredWords
     */
    public function __construct(
        private LoggerInterface $logger,
        private Gmail $service,
        private Query $query,
        private ConfigRepository $configRepository,
        private array $filteredWords = ['exception']
    ) {}

    /**
     * CloudEventを処理し、Firestoreから取得したターゲットに基づいてメールを処理します。
     *
     * @param CloudEventInterface $event
     * @return void
     */
    public function handle(CloudEventInterface $event): void
    {
        $user = 'me';
        $targets = $this->configRepository->getTargets();

        if (empty($targets)) {
            $this->logger->info("No targets found in Firestore.");
            return;
        }

        $gmailAppService = new GmailService($this->service);
        $currentDayOfWeek = Carbon::now()->dayOfWeek; // 0 (Sun) - 6 (Sat)

        foreach ($targets as $target) {
            $this->logger->info($this->filterLogMessage("Processing target: " . json_encode($target)));
            $searchQuery = $this->query->build($target);
            $params = [
                "maxResults" => 20,
                "q" => $searchQuery,
                "includeSpamTrash" => false,
            ];
            $this->logger->info($this->filterLogMessage("Listing messages: " . json_encode($params)));

            try {
                $results = $this->service->users_messages->listUsersMessages($user, $params);
                $messages = $results->getMessages();

                if (empty($messages)) {
                    $this->logger->info("No results found.");
                    continue;
                }

                foreach ($messages as $message) {
                    $messageDetail = $this->service->users_messages->get($user, $message->id);
                    $this->logger->info($this->filterLogMessage("Processing [{$message->id}] {$messageDetail->snippet}"));

                    // 1. Forward
                    $forwardTo = $target['forward_to'] ?? null;
                    $forwardDays = $target['forward_days'] ?? null;
                    if ($forwardTo) {
                        $shouldForward = true;
                        if ($forwardDays !== null) {
                            $shouldForward = in_array((string)$currentDayOfWeek, (array)$forwardDays);
                        }

                        if ($shouldForward) {
                            $this->logger->info("Forwarding to $forwardTo");
                            $gmailAppService->forward($message->id, $forwardTo);
                        } else {
                            $this->logger->info("Skipping forward (day $currentDayOfWeek not in configured days)");
                        }
                    }

                    // 2. Mark as read
                    if (!empty($target['mark_as_read'])) {
                        $this->logger->info("Marking as read");
                        $gmailAppService->markAsRead($message->id);
                    }

                    // 3. Trash
                    if (!isset($target['is_trash']) || !empty($target['is_trash'])) {
                        $this->logger->info("Moving to trash");
                        $this->service->users_messages->trash($user, $message->id);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error("Error processing target: " . $e->getMessage());
            }
        }

        $this->logger->info("Succeeded.");
    }

    /**
     * ログメッセージから特定のワードをフィルタリングします。
     *
     * @param string $message オリジナルのログメッセージ。
     * @return string フィルタリングされたログメッセージ。
     */
    private function filterLogMessage(string $message): string
    {
        foreach ($this->filteredWords as $word) {
            $message = str_ireplace($word, '[MASKED]', $message);
        }
        return $message;
    }
}
