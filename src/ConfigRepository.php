<?php declare(strict_types=1);

namespace App;

use Google\Cloud\Firestore\FirestoreClient;

/**
 * Firestoreから設定（ルール）を取得するリポジトリクラス。
 */
class ConfigRepository
{
    /**
     * @param FirestoreClient $firestore
     * @param string $rootCollection
     */
    public function __construct(
        private FirestoreClient $firestore,
        private string $rootCollection
    ) {}

    /**
     * 削除対象のルール一覧を取得します。
     *
     * @return array<int, array<string, mixed>> ルールの配列。
     */
    public function getTargets(): array
    {
        $collectionPath = sprintf('%s/configs/configs', $this->rootCollection);
        $documents = $this->firestore->collection($collectionPath)->documents();

        $targets = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $targets[] = $document->data();
            }
        }

        return $targets;
    }
}
