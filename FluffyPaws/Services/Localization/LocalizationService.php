<?php

namespace FluffyPaws\Services\Localization;

use FluffyPaws\Data\Entities\Localization\LocaleResourceEntity;
use FluffyPaws\Data\Entities\Localization\LocaleResourceEntityMap;
use FluffyPaws\Data\Repositories\LocaleResourceRepository;
use Fluffy\Swoole\Cache\CacheManager;

class LocalizationService
{
    private ?array $resources = null;

    public function __construct(
        protected LocaleResourceRepository $localeResources,
        protected CacheManager $cache
    ) {
    }

    public function getResources(int $languageId)
    {
        $cacheKey = sprintf(LocaleResourceEntityMap::CACHE_KEY, $languageId);
        $models = $this->cache->get($cacheKey);
        if ($models === null) {
            $models = $this->cache->set($cacheKey, function () use ($languageId) {
                $where = [
                    [LocaleResourceEntityMap::PROPERTY_LanguageId, $languageId]
                ];
                $entities = $this->localeResources->search($where, [LocaleResourceEntityMap::PROPERTY_CreatedOn => 1], 1, null, false);
                $models = [];
                $resources = $entities['list'];
                $models = array_reduce($resources, function (array $models, LocaleResourceEntity $entity) {
                    $models[$entity->Name] = $entity->Value;
                    return $models;
                }, $models);
                return $models;
            });
        }
        // var_export($models);
        return $models;
    }

    public function localize(string $key, ?array $params = null)
    {
        if ($this->resources === null) {
            $this->resources = $this->getResources(1);
        }
        $text = $this->resources[$key] ?? $key;
        if ($params !== null) {
            foreach ($params as $key => $value) {
                $text = str_replace("{{$key}}", $value, $text);
            }
        }
        return $text;
    }
}
