<?php

declare(strict_types=1);

namespace Drupal\learning_property\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\learning_property\Service\PropertyManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Displays Property listing and detail pages.
 */
final class PropertyController extends ControllerBase {

  public function __construct(
    private readonly PropertyManager $propertyManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('learning_property.manager'));
  }

  public function listing(): array {
    return [
      '#theme' => 'learning_property_list',
      '#properties' => $this->propertyManager->getAvailableProperties(),
      '#empty_message' => $this->t('No available properties were found.'),
      '#cache' => [
        'tags' => ['node_list:property'],
        'contexts' => ['user.node_grants:view', 'user.permissions'],
        'max-age' => 300,
      ],
    ];
  }

  public function detail(NodeInterface $node): array {
    $this->assertProperty($node);

    return [
      '#theme' => 'learning_property_detail',
      '#property' => $node,
      '#booking_url' => $this->bookingUrl($node),
      '#cache' => [
        'tags' => $node->getCacheTags(),
        'contexts' => ['user.node_grants:view', 'user.permissions'],
      ],
    ];
  }

  public function title(NodeInterface $node): string {
    $this->assertProperty($node);
    return $node->label();
  }

  private function assertProperty(NodeInterface $node): void {
    if ($node->bundle() !== 'property' || !$node->access('view')) {
      throw new NotFoundHttpException();
    }
  }

  private function bookingUrl(NodeInterface $node): ?Url {
    return $this->moduleHandler()->moduleExists('learning_booking')
      && $this->propertyManager->isAvailable($node)
      ? Url::fromRoute('learning_booking.form', ['node' => $node->id()])
      : NULL;
  }

}
