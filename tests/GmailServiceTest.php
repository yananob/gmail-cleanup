<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\GmailService;
use Google\Service\Gmail;
use Google\Service\Gmail\Resource\UsersMessages;
use Google\Service\Gmail\ListMessagesResponse;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePart;
use Google\Service\Gmail\MessagePartHeader;

class GmailServiceTest extends TestCase
{
    public function testListMessagesIncludesFromHeader(): void
    {
        $gmailMock = $this->createMock(Gmail::class);
        $usersMessagesMock = $this->createMock(UsersMessages::class);

        $msgObj = new Message();
        $msgObj->setId('msg-123');

        $listResponse = new ListMessagesResponse();
        $listResponse->setMessages([$msgObj]);

        $usersMessagesMock->expects($this->once())
            ->method('listUsersMessages')
            ->with('me', $this->callback(function ($params) {
                return isset($params['q']) && $params['q'] === 'before:2025/01/01';
            }))
            ->willReturn($listResponse);

        $detailMsg = new Message();
        $detailMsg->setId('msg-123');
        $detailMsg->setSnippet('This is a test snippet.');

        $payload = new MessagePart();

        $headerSubject = new MessagePartHeader();
        $headerSubject->setName('Subject');
        $headerSubject->setValue('Test Subject');

        $headerDate = new MessagePartHeader();
        $headerDate->setName('Date');
        $headerDate->setValue('Wed, 1 Jan 2025 10:00:00 +0000');

        $headerFrom = new MessagePartHeader();
        $headerFrom->setName('From');
        $headerFrom->setValue('sender@example.com');

        $payload->setHeaders([$headerSubject, $headerDate, $headerFrom]);
        $detailMsg->setPayload($payload);

        $usersMessagesMock->expects($this->once())
            ->method('get')
            ->with('me', 'msg-123')
            ->willReturn($detailMsg);

        $gmailMock->users_messages = $usersMessagesMock;

        $service = new GmailService($gmailMock);
        $result = $service->listMessages('before:2025/01/01', 10);

        $this->assertCount(1, $result);
        $this->assertEquals([
            'id' => 'msg-123',
            'snippet' => 'This is a test snippet.',
            'subject' => 'Test Subject',
            'date' => 'Wed, 1 Jan 2025 10:00:00 +0000',
            'from' => 'sender@example.com',
        ], $result[0]);
    }
}
