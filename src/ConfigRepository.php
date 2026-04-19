<?php declare(strict_types=1);

namespace App;

use Google\Cloud\Firestore\FirestoreClient;

/**
 * Firestoreから設定（ルール）を取得・管理するリポジトリクラス。
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
     * 削除対象のルールデータ一覧を取得します。
     *
     * @return array<int, array<string, mixed>> ルールデータの配列。
     */
    public function getTargets(): array
    {
        $configs = $this->getAll();
        return array_map(fn($config) => $config['data'], $configs);
    }

    /**
     * ID付きの全てのルールを取得します。
     *
     * @return array<int, array{id: string, data: array<string, mixed>}>
     */
    public function getAll(): array
    {
        $collectionPath = sprintf('%s/configs/configs', $this->rootCollection);
        $documents = $this->firestore->collection($collectionPath)->documents();

        $configs = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $configs[] = [
                    'id' => $document->id(),
                    'data' => $document->data()
                ];
            }
        }

        return $configs;
    }

    /**
     * 指定されたIDのルールを取得します。
     *
     * @param string $id
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $documentPath = sprintf('%s/configs/configs/%s', $this->rootCollection, $id);
        $snapshot = $this->firestore->document($documentPath)->snapshot();

        if ($snapshot->exists()) {
            return $snapshot->data();
        }

        return null;
    }

    /**
     * 新しいルールを作成します。
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function create(array $data): void
    {
        $collectionPath = sprintf('%s/configs/configs', $this->rootCollection);
        $this->firestore->collection($collectionPath)->add($data);
    }

    /**
     * ルールを更新します。
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return void
     */
    public function update(string $id, array $data): void
    {
        $documentPath = sprintf('%s/configs/configs/%s', $this->rootCollection, $id);
        $this->firestore->document($documentPath)->set($data, ['merge' => false]);
    }

    /**
     * ルールを削除します。
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void
    {
        $documentPath = sprintf('%s/configs/configs/%s', $this->rootCollection, $id);
        $this->firestore->document($documentPath)->delete();
    }
}
