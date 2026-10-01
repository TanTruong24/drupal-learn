<?php

declare(strict_types=1);

namespace Drupal\learning_notification\EventSubscriber;

use Drupal\learning_booking\Event\BookingCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs booking creation events.
 */
final class BookingCreatedSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly LoggerInterface $logger,
  ) {}

  public static function getSubscribedEvents(): array {
    return [
      BookingCreatedEvent::NAME => 'onBookingCreated',
    ];
  }

  public function onBookingCreated(BookingCreatedEvent $event): void {
    $this->logger->notice(
      'Booking @booking created for property @property; notification recorded.',
      [
        '@booking' => $event->getBooking()->id(),
        '@property' => $event->getProperty()->id(),
      ],
    );
  }

}
