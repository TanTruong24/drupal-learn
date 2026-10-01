<?php

declare(strict_types=1);

namespace Drupal\learning_booking\Event;

use Drupal\node\NodeInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a Booking node is saved.
 */
final class BookingCreatedEvent extends Event {

  public const NAME = 'learning_booking.created';

  public function __construct(
    private readonly NodeInterface $booking,
    private readonly NodeInterface $property,
  ) {}

  public function getBooking(): NodeInterface {
    return $this->booking;
  }

  public function getProperty(): NodeInterface {
    return $this->property;
  }

}
