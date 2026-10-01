<?php

declare(strict_types=1);

namespace Drupal\learning_property\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\learning_property\Service\PropertyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a short list of available properties.
 */
#[Block(
  id: 'learning_property_available',
  admin_label: new TranslatableMarkup('Available properties'),
  category: new TranslatableMarkup('Learning'),
)]
final class AvailablePropertiesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly PropertyManager $propertyManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('learning_property.manager'),
    );
  }

  public function build(): array {
    return [
      '#theme' => 'learning_property_list',
      '#properties' => $this->propertyManager->getAvailableProperties(5),
      '#empty_message' => $this->t('No properties are currently available.'),
      '#cache' => [
        'tags' => ['node_list:property'],
        'contexts' => ['user.node_grants:view', 'user.permissions'],
        'max-age' => 300,
      ],
    ];
  }

}
