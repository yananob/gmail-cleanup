<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Google\Service\Gmail;
use Google\Service\Gmail\Resource\UsersSettingsFilters;
use Google\Service\Gmail\ListFiltersResponse;
use Google\Service\Gmail\Filter;
use Google\Service\Gmail\FilterCriteria;
use Google\Service\Gmail\FilterAction;
use App\GmailFilterService;

class GmailFilterServiceTest extends TestCase
{
    public function testListFilters(): void
    {
        $filter1 = new Filter();
        $filter1->setId('filter-123');

        $criteria = new FilterCriteria();
        $criteria->setFrom('test@example.com');
        $criteria->setSubject('Hello');
        $filter1->setCriteria($criteria);

        $action = new FilterAction();
        $action->setAddLabelIds(['TRASH']);
        $filter1->setAction($action);

        $listResponse = new ListFiltersResponse();
        $listResponse->setFilter([$filter1]);

        $usersSettingsFiltersMock = $this->createMock(UsersSettingsFilters::class);
        $usersSettingsFiltersMock->expects($this->once())
            ->method('listUsersSettingsFilters')
            ->with('me')
            ->willReturn($listResponse);

        $gmailMock = $this->createMock(Gmail::class);
        $gmailMock->users_settings_filters = $usersSettingsFiltersMock;

        $service = new GmailFilterService($gmailMock);
        $result = $service->listFilters();

        $expected = [
            [
                'id' => 'filter-123',
                'criteria' => [
                    'from' => 'test@example.com',
                    'subject' => 'Hello',
                ],
                'action' => [
                    'addLabelIds' => ['TRASH'],
                ],
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testCreateFilter(): void
    {
        $createdFilter = new Filter();
        $createdFilter->setId('new-filter-id');

        $usersSettingsFiltersMock = $this->createMock(UsersSettingsFilters::class);
        $usersSettingsFiltersMock->expects($this->once())
            ->method('create')
            ->with('me', $this->isInstanceOf(Filter::class))
            ->willReturn($createdFilter);

        $gmailMock = $this->createMock(Gmail::class);
        $gmailMock->users_settings_filters = $usersSettingsFiltersMock;

        $service = new GmailFilterService($gmailMock);
        $res = $service->createFilter(
            ['from' => 'spam@example.com'],
            ['addLabelIds' => ['TRASH']]
        );

        $this->assertEquals('new-filter-id', $res->getId());
    }

    public function testGetFilter(): void
    {
        $filter = new Filter();
        $filter->setId('filter-123');

        $criteria = new FilterCriteria();
        $criteria->setFrom('test@example.com');
        $filter->setCriteria($criteria);

        $action = new FilterAction();
        $action->setAddLabelIds(['TRASH']);
        $filter->setAction($action);

        $usersSettingsFiltersMock = $this->createMock(UsersSettingsFilters::class);
        $usersSettingsFiltersMock->expects($this->once())
            ->method('get')
            ->with('me', 'filter-123')
            ->willReturn($filter);

        $gmailMock = $this->createMock(Gmail::class);
        $gmailMock->users_settings_filters = $usersSettingsFiltersMock;

        $service = new GmailFilterService($gmailMock);
        $result = $service->getFilter('filter-123');

        $expected = [
            'id' => 'filter-123',
            'criteria' => [
                'from' => 'test@example.com',
            ],
            'action' => [
                'addLabelIds' => ['TRASH'],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetFilterNotFound(): void
    {
        $usersSettingsFiltersMock = $this->createMock(UsersSettingsFilters::class);
        $usersSettingsFiltersMock->expects($this->once())
            ->method('get')
            ->with('me', 'non-existent')
            ->willThrowException(new \Exception('Not found'));

        $gmailMock = $this->createMock(Gmail::class);
        $gmailMock->users_settings_filters = $usersSettingsFiltersMock;

        $service = new GmailFilterService($gmailMock);
        $result = $service->getFilter('non-existent');

        $this->assertNull($result);
    }

    public function testDeleteFilter(): void
    {
        $usersSettingsFiltersMock = $this->createMock(UsersSettingsFilters::class);
        $usersSettingsFiltersMock->expects($this->once())
            ->method('delete')
            ->with('me', 'filter-to-delete');

        $gmailMock = $this->createMock(Gmail::class);
        $gmailMock->users_settings_filters = $usersSettingsFiltersMock;

        $service = new GmailFilterService($gmailMock);
        $service->deleteFilter('filter-to-delete');
    }
}
