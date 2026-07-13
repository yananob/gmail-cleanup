<?php declare(strict_types=1);

namespace App;

use CloudEvents\V1\CloudEventInterface;
use Google\Service\Gmail;
use Psr\Log\LoggerInterface;

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
     * CloudEventを処理し、Firestoreから取得したターゲットに基づいてメールを削除（ゴミ箱へ移動）します。
     *
     * @param CloudEventInterface|null $event
     * @return void
     */
    public function handle(?CloudEventInterface $event = null): void
    {
        $user = 'me';
        $targets = $this->configRepository->getTargets();

        if (empty($targets)) {
            $this->logger->info("No targets found in Firestore.");
            return;
        }

        foreach ($targets as $target) {
            $this->logger->info($this->filterLogMessage("Processing target: " . json_encode($target)));
            $params = [
                "maxResults" => 20,
                "q" => $this->query->build($target),
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

                $this->logger->info("Deleting messages:");
                foreach ($messages as $message) {
                    $messageDetail = $this->service->users_messages->get($user, $message->id);
                    $this->logger->info($this->filterLogMessage("[{$message->id}] {$messageDetail->snippet}"));
                    $this->service->users_messages->trash($user, $message->id);
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
