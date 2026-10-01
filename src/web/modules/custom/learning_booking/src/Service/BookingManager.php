<?php

declare(strict_types=1);

namespace Drupal\learning_booking\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\learning_booking\Event\BookingCreatedEvent;
use Drupal\learning_property\Service\PropertyManager;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Creates Booking nodes and dispatches booking events.
 */
final class BookingManager {

  public function __construct(
    private readonly PropertyManager $propertyManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Creates a booking for an available property.
   *
   * @param array{customer_name: string, customer_email: string, notes?: string} $values
   *   Validated booking values.
   */
  public function createBooking(NodeInterface $property, array $values): NodeInterface {
    if (!$this->propertyManager->isAvailable($property)) {
      throw new \InvalidArgumentException('The selected property is not available.');
    }

    $booking = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'booking',
      'title' => sprintf('Booking: %s - %s', $property->label(), $values['customer_name']),
      'status' => NodeInterface::NOT_PUBLISHED,
      'field_booking_property' => ['target_id' => $property->id()],
      'field_customer_name' => $values['customer_name'],
      'field_customer_email' => $values['customer_email'],
      'field_booking_notes' => $values['notes'] ?? '',
    ]);
    $booking->save();

    $this->logger->info('Booking @booking created for property @property.', [
      '@booking' => $booking->id(),
      '@property' => $property->id(),
    ]);
    $this->eventDispatcher->dispatch(
      new BookingCreatedEvent($booking, $property),
      BookingCreatedEvent::NAME,
    );

    return $booking;
  }

}
