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
     * @return array<int, array{id: string, snippet: string, subject: string, date: string}> メッセージの詳細リスト。
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
                foreach ($headers as $header) {
                    if ($header->getName() === 'Subject') {
                        $subject = $header->getValue();
                    }
                    if ($header->getName() === 'Date') {
                        $date = $header->getValue();
                    }
                }

                $list[] = [
                    'id' => $message->id,
                    'snippet' => $msg->getSnippet(),
                    'subject' => $subject,
                    'date' => $date,
                ];
            }

            return $list;
        } catch (\Exception $e) {
            // エラーが発生した場合は空配列を返すか、必要に応じて例外を再スローします
            // ここではログ記録などが本来望ましいですが、呼び出し元で制御することを想定します
            throw $e;
        }
    }

    /**
     * メッセージを既読にします（UNREADラベルを削除）。
     *
     * @param string $messageId
     * @return void
     */
    public function markAsRead(string $messageId): void
    {
        $user = 'me';
        $mods = new \Google\Service\Gmail\ModifyMessageRequest();
        $mods->setRemoveLabelIds(['UNREAD']);
        $this->service->users_messages->modify($user, $messageId, $mods);
    }

    /**
     * メッセージを転送します。
     *
     * @param string $messageId
     * @param string $to
     * @return void
     */
    public function forward(string $messageId, string $to): void
    {
        $user = 'me';
        $msg = $this->service->users_messages->get($user, $messageId);
        $payload = $msg->getPayload();
        $headers = $payload->getHeaders();

        $subject = '';
        foreach ($headers as $header) {
            if (strtolower($header->getName()) === 'subject') {
                $subject = $header->getValue();
                break;
            }
        }

        $body = $this->extractBody($payload);
        if (empty($body)) {
            $body = $msg->getSnippet();
        }

        $newMessage = new \Google\Service\Gmail\Message();
        $encodedSubject = mb_encode_mimeheader("Fwd: $subject", 'UTF-8', 'B');
        $rawMessageString = "To: $to\r\n";
        $rawMessageString .= "Subject: $encodedSubject\r\n";
        $rawMessageString .= "Content-Type: text/plain; charset=utf-8\r\n";
        $rawMessageString .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $rawMessageString .= base64_encode($body);

        $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessageString));
        $newMessage->setRaw($encodedMessage);
        $this->service->users_messages->send($user, $newMessage);
    }

    /**
     * メッセージをゴミ箱に移動します。
     *
     * @param string $messageId
     * @return void
     */
    public function trash(string $messageId): void
    {
        $this->service->users_messages->trash('me', $messageId);
    }

    /**
     * メッセージの詳細を取得します。
     *
     * @param string $messageId
     * @return \Google\Service\Gmail\Message
     */
    public function get(string $messageId): \Google\Service\Gmail\Message
    {
        return $this->service->users_messages->get('me', $messageId);
    }

    /**
     * ペイロードから本文を抽出します。
     *
     * @param \Google\Service\Gmail\MessagePart $part
     * @return string
     */
    private function extractBody(\Google\Service\Gmail\MessagePart $part): string
    {
        if ($part->getMimeType() === 'text/plain' && $part->getBody()->getData()) {
            return base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
        }

        $parts = $part->getParts();
        if ($parts) {
            foreach ($parts as $partItem) {
                $body = $this->extractBody($partItem);
                if ($body) return $body;
            }
        }

        return '';
    }
}
