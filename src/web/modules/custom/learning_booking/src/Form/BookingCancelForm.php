<?php

namespace Drupal\learning_booking\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\learning_booking\Service\BookingManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BookingCancelForm extends ConfirmFormBase {

    protected NodeInterface $booking;

    public function __construct(
        private readonly BookingManager $bookingManager,
        private readonly AccountProxyInterface $currentUser,
    ) {}

    public static function create(
        ContainerInterface $container
    ): static {
        return new static(
            $container->get('learning_booking.manager'),
            $container->get('current_user'),
        );
    }

    public function getFormId(): string {
        return 'learning_booking_cancel_form';
    }

    public function buildForm(
        array $form,
        FormStateInterface $form_state,
        ?NodeInterface $node = NULL,
    ): array {

        if (!$node || $node->bundle() !== 'booking') {
            throw new \InvalidArgumentException(
                'Invalid booking.'
            );
        }

        $this->booking = $node;

        return parent::buildForm(
            $form,
            $form_state
        );
    }

    public function getQuestion(): string {
        return $this->t(
            'Are you sure you want to cancel booking @booking?',
            [
                '@booking' => $this->booking->label(),
            ]
        );
    }

    public function getConfirmText(): string {
        return $this->t('Cancel booking');
    }

    public function getCancelUrl(): Url {
        return Url::fromRoute(
            'view.my_bookings.page_1'
        );
    }

    public function submitForm(
        array &$form,
        FormStateInterface $form_state
    ): void {

        $this->bookingManager->cancel(
            $this->booking,
            (int) $this->currentUser->id(),
        );

        $this->messenger()->addStatus(
            $this->t('Your booking has been cancelled.')
        );

        $form_state->setRedirect(
            'view.my_bookings.page_1'
        );
    }

}
