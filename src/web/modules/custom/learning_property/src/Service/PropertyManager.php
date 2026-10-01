<?php

declare(strict_types=1);

namespace Drupal\learning_property\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Loads and queries Property nodes.
 */
final class PropertyManager {

  private const AVAILABLE_CACHE_ID = 'learning_property:available_ids';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheBackendInterface $cache,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Returns published, available properties.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Property nodes keyed by node ID.
   */
  public function getAvailableProperties(int $limit = 20): array {
    $limit = max(1, min($limit, 100));
    $cid = self::AVAILABLE_CACHE_ID . ':' . $limit;
    $cached = $this->cache->get($cid);

    if ($cached) {
      $ids = $cached->data;
      $this->logger->debug('Available property IDs loaded from cache.');
    }
    else {
      $ids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'property')
        ->condition('status', NodeInterface::PUBLISHED)
        ->condition('field_available', 1)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      $this->cache->set($cid, array_values($ids), time() + 300, ['node_list:property']);
      $this->logger->info('Queried available properties and cached the result.');
    }

    return $ids
      ? $this->entityTypeManager->getStorage('node')->loadMultiple($ids)
      : [];
  }

  public function isAvailable(NodeInterface $property): bool {
    return $property->bundle() === 'property'
      && $property->isPublished()
      && !$property->get('field_available')->isEmpty()
      && (bool) $property->get('field_available')->value;
  }

}
