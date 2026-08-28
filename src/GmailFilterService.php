<?php declare(strict_types=1);

namespace App;

use Google\Service\Gmail;
use Google\Service\Gmail\Filter;
use Google\Service\Gmail\FilterCriteria;
use Google\Service\Gmail\FilterAction;

/**
 * Gmail APIのユーザーフィルター設定とのやり取りを担当するサービスクラス。
 */
class GmailFilterService
{
    public function __construct(
        private Gmail $service
    ) {}

    /**
     * Gmailユーザーのフィルター一覧を取得します。
     *
     * @return array<int, array{
     *     id: string,
     *     criteria: array{from?: string, to?: string, subject?: string, query?: string, negatedQuery?: string, hasAttachment?: bool},
     *     action: array{addLabelIds?: string[], removeLabelIds?: string[], forward?: string}
     * }>
     */
    public function listFilters(): array
    {
        $response = $this->service->users_settings_filters->listUsersSettingsFilters('me');
        $filters = $response->getFilter();

        if (empty($filters)) {
            return [];
        }

        $result = [];
        foreach ($filters as $filter) {
            $criteriaObj = $filter->getCriteria();
            $actionObj = $filter->getAction();

            $criteria = [];
            if ($criteriaObj) {
                if ($criteriaObj->getFrom()) $criteria['from'] = $criteriaObj->getFrom();
                if ($criteriaObj->getTo()) $criteria['to'] = $criteriaObj->getTo();
                if ($criteriaObj->getSubject()) $criteria['subject'] = $criteriaObj->getSubject();
                if ($criteriaObj->getQuery()) $criteria['query'] = $criteriaObj->getQuery();
                if ($criteriaObj->getNegatedQuery()) $criteria['negatedQuery'] = $criteriaObj->getNegatedQuery();
                if ($criteriaObj->getHasAttachment()) $criteria['hasAttachment'] = $criteriaObj->getHasAttachment();
            }

            $action = [];
            if ($actionObj) {
                if (!empty($actionObj->getAddLabelIds())) $action['addLabelIds'] = $actionObj->getAddLabelIds();
                if (!empty($actionObj->getRemoveLabelIds())) $action['removeLabelIds'] = $actionObj->getRemoveLabelIds();
                if ($actionObj->getForward()) $action['forward'] = $actionObj->getForward();
            }

            $result[] = [
                'id' => (string)$filter->getId(),
                'criteria' => $criteria,
                'action' => $action,
            ];
        }

        return $result;
    }

    /**
     * 新しいフィルターを作成します。
     *
     * @param array{from?: string, to?: string, subject?: string, query?: string, negatedQuery?: string, hasAttachment?: bool} $criteriaData
     * @param array{addLabelIds?: string[], removeLabelIds?: string[], forward?: string} $actionData
     * @return Filter
     */
    public function createFilter(array $criteriaData, array $actionData): Filter
    {
        $criteria = new FilterCriteria();
        if (!empty($criteriaData['from'])) $criteria->setFrom($criteriaData['from']);
        if (!empty($criteriaData['to'])) $criteria->setTo($criteriaData['to']);
        if (!empty($criteriaData['subject'])) $criteria->setSubject($criteriaData['subject']);
        if (!empty($criteriaData['query'])) $criteria->setQuery($criteriaData['query']);
        if (!empty($criteriaData['negatedQuery'])) $criteria->setNegatedQuery($criteriaData['negatedQuery']);
        if (isset($criteriaData['hasAttachment'])) $criteria->setHasAttachment((bool)$criteriaData['hasAttachment']);

        $action = new FilterAction();
        if (!empty($actionData['addLabelIds'])) $action->setAddLabelIds($actionData['addLabelIds']);
        if (!empty($actionData['removeLabelIds'])) $action->setRemoveLabelIds($actionData['removeLabelIds']);
        if (!empty($actionData['forward'])) $action->setForward($actionData['forward']);

        $filter = new Filter();
        $filter->setCriteria($criteria);
        $filter->setAction($action);

        return $this->service->users_settings_filters->create('me', $filter);
    }

    /**
     * 指定されたIDのフィルターを削除します。
     *
     * @param string $id
     * @return void
     */
    public function deleteFilter(string $id): void
    {
        $this->service->users_settings_filters->delete('me', $id);
    }
}
