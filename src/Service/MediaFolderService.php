<?php declare(strict_types=1);

namespace InReNa\Service;

use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MediaFolderService
{
    private array $folderCache = [];

    public function __construct(
        private readonly EntityRepository $mediaFolderRepository
    ) {
    }

    /**
     * Get folder ID by path (e.g., "MiniwaldExplorer/Species/Categories")
     */
    public function getFolderIdByPath(string $path, Context $context): ?string
    {
        if (isset($this->folderCache[$path])) {
            return $this->folderCache[$path];
        }

        $folder = $this->getFolderByPath($path, $context);

        if ($folder) {
            $this->folderCache[$path] = $folder->getId();

            return $folder->getId();
        }

        return null;
    }

    /**
     * Get folder entity by path
     */
    public function getFolderByPath(string $path, Context $context): ?MediaFolderEntity
    {
        $parts = explode('/', $path);
        $currentFolder = null;

        foreach ($parts as $part) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', $part));

            if ($currentFolder) {
                $criteria->addFilter(new EqualsFilter('parentId', $currentFolder->getId()));
            } else {
                $criteria->addFilter(new EqualsFilter('parentId', null));
            }

            // @phpstan-ignore shopware.noEntityRepositoryInLoop
            $result = $this->mediaFolderRepository->search($criteria, $context);

            if ($result->count() === 0) {
                return null;
            }

            /** @var MediaFolderEntity|null $currentFolder */
            $currentFolder = $result->first();
        }

        return $currentFolder;
    }

    /**
     * Clear the folder cache
     */
    public function clearCache(): void
    {
        $this->folderCache = [];
    }
}
