<?php

namespace Drupal\learning_booking\Event;

use Drupal\node\NodeInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class BookingEvent extends Event
{

    public function __construct(
        protected readonly NodeInterface $booking,
    )
    {
    }

    public function getBooking(): NodeInterface
    {
        return $this->booking;
    }

}
