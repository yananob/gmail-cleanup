<?php declare(strict_types=1);

namespace App;

use Google\Service\Gmail;

/**
 * Gmail APIとのやり取りを担当するサービスクラス。
 */
class GmailService
{
    /**
     * @param Gmail $service
     */
    public function __construct(
        private Gmail $service
    ) {}

    /**
     * 指定されたクエリに一致するメッセージのリストを取得します。
     *
     * @param string $query Gmail検索クエリ。
     * @param int $maxResults 最大取得件数。
     * @return array<int, array{id: string, snippet: string, subject: string, date: string, from: string}> メッセージの詳細リスト。
     */
    public function listMessages(string $query, int $maxResults = 20): array
    {
        $user = 'me';
        $params = [
            'q' => $query,
            'maxResults' => $maxResults,
            'includeSpamTrash' => false,
        ];

        try {
            $results = $this->service->users_messages->listUsersMessages($user, $params);
            $messages = $results->getMessages();

            if (empty($messages)) {
                return [];
            }

            $list = [];
            foreach ($messages as $message) {
                $msg = $this->service->users_messages->get($user, $message->id);
                $payload = $msg->getPayload();
                $headers = $payload->getHeaders();

                $subject = '';
                $date = '';
                $from = '';
                foreach ($headers as $header) {
                    if ($header->getName() === 'Subject') {
                        $subject = $header->getValue();
                    }
                    if ($header->getName() === 'Date') {
                        $date = $header->getValue();
                    }
                    if ($header->getName() === 'From') {
                        $from = $header->getValue();
                    }
                }

                $list[] = [
                    'id' => $message->id,
                    'snippet' => $msg->getSnippet(),
                    'subject' => $subject,
                    'date' => $date,
                    'from' => $from,
                ];
            }

            return $list;
        } catch (\Exception $e) {
            // エラーが発生した場合は空配列を返すか、必要に応じて例外を再スローします
            // ここではログ記録などが本来望ましいですが、呼び出し元で制御することを想定します
            throw $e;
        }
    }
}
