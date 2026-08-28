<?php declare(strict_types=1);

namespace App\Service;

use Google\Client;
use Google\Service\Gmail;
use App\AppConfig;

class GmailClientFactory
{
    /**
     * Gmail API用の認可済みクライアントを取得します。
     */
    public function create(): Client
    {
        return $this->createClient();
    }

    /**
     * Gmail API用の認可済みクライアントを取得します。
     */
    public function createClient(): Client
    {
        $client = new Client();
        $client->setApplicationName('MyCFApp');
        $client->setScopes([
            Gmail::MAIL_GOOGLE_COM,  // Full access to Gmail
            Gmail::GMAIL_MODIFY,      // Modify Gmail labels and messages
        ]);
        $client->setAccessType('offline');

        $authConfig = getenv('GOOGLE_API_CLIENT_SECRET');
        if ($authConfig) {
            $client->setAuthConfig(json_decode($authConfig, true));
        }
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $token = getenv('GOOGLE_API_TOKEN');
        if ($token) {
            $client->setAccessToken(json_decode($token, true));
        }

        if ($userEmail = AppConfig::getGmailUserEmail()) {
            $client->setSubject($userEmail);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                try {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                } catch (\Exception $e) {
                    // Log but don't crash here, as we might be able to recover or use service account
                    error_log('Failed to refresh token: ' . $e->getMessage());
                }
            }
        }

        return $client;
    }

    /**
     * Gmailサービスインスタンスを作成します。
     */
    public function createGmailService(Client $client): Gmail
    {
        return new Gmail($client);
    }
}
