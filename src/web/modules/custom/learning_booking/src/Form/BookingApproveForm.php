<?php

namespace Drupal\learning_booking\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\learning_booking\Service\BookingManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BookingApproveForm extends ConfirmFormBase
{

    protected NodeInterface $booking;

    public function __construct(
        private readonly BookingManager $bookingManager,
    )
    {
    }

    public static function create(
        ContainerInterface $container
    ): static
    {

        return new static(
            $container->get(
                'learning_booking.manager'
            ),
        );
    }

    public function getFormId(): string
    {
        return 'learning_booking_approve_form';
    }

    public function buildForm(
        array              $form,
        FormStateInterface $form_state,
        ?NodeInterface     $node = NULL,
    ): array
    {

        if (!$node || $node->bundle() !== 'booking') {
            throw new \InvalidArgumentException('Invalid booking.');
        }

        $this->booking = $node;

        return parent::buildForm($form, $form_state);
    }

    public function getQuestion(): string
    {
        return $this->t(
            'Approve booking @id?',
            ['@id' => $this->booking->id()]
        );
    }

    public function getCancelUrl(): Url
    {
        return Url::fromRoute('view.bookings.page_1');
    }

    public function submitForm(
        array              &$form,
        FormStateInterface $form_state,
    ): void
    {
        $this->bookingManager
            ->approve($this->booking);

        $this->messenger()->addStatus(
            $this->t('Booking approved.')
        );

        $form_state->setRedirect(
            'view.bookings.page_1'
        );
    }
}
