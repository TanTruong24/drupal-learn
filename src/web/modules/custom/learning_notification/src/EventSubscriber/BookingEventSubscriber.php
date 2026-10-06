<?php

declare(strict_types=1);

namespace Drupal\learning_notification\EventSubscriber;

use Drupal\learning_booking\Event\BookingCreatedEvent;
use Drupal\learning_booking\Event\BookingApprovedEvent;
use Drupal\learning_booking\Event\BookingCancelledEvent;
use Drupal\learning_booking\Event\BookingRejectedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs booking creation events.
 */
final class BookingEventSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BookingCreatedEvent::class => 'onCreated',
            BookingApprovedEvent::class => 'onApproved',
            BookingRejectedEvent::class => 'onRejected',
            BookingCancelledEvent::class => 'onCancelled',
        ];
    }

    public function onCreated(BookingCreatedEvent $event): void
    {
        $this->logger->notice(
            'Booking @booking created.',
            [
                '@booking' => $event->getBooking()->id()
            ],
        );
    }

    public function onApproved(BookingApprovedEvent $event): void
    {
        $booking = $event->getBooking();

        $this->logger->info(
            'Booking @id approved.',
            [
                '@id' => $booking->id()
            ]
        );
    }

    public function onRejected(BookingRejectedEvent $event): void {
        $booking = $event->getBooking();

        $this->logger->info(
            'Booking @id rejected.',
            [
                '@id' => $booking->id(),
            ]
        );
    }

    public function onCancelled(BookingCancelledEvent $event): void {
        $booking = $event->getBooking();

        $this->logger->info(
            'Booking @id cancelled.',
            [
                '@id' => $booking->id(),
            ]
        );
    }

}
