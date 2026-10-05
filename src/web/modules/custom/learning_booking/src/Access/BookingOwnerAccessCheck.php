<?php

namespace Drupal\learning_booking\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

class BookingOwnerAccessCheck implements AccessInterface
{

    public function access(
        Route            $route,
        AccountInterface $account,
        ?NodeInterface   $node = NULL,
    ): AccessResultInterface
    {

        if (!$node || $node->bundle() !== 'booking') {
            return AccessResult::forbidden();
        }

        $isOwner = (int)$node->getOwnerId() === (int)$account->id();

        return AccessResult::allowedIf($isOwner)
            ->addCacheContexts(['user'])
            ->addCacheableDependency($node);
    }

}
