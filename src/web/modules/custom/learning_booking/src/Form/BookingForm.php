<?php

declare(strict_types=1);

namespace Drupal\learning_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\learning_booking\Service\BookingManager;
use Drupal\learning_property\Service\PropertyManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Custom booking form for an available property.
 */
final class BookingForm extends FormBase {

  private ?NodeInterface $property = NULL;

  public function __construct(
    private readonly BookingManager $bookingManager,
    private readonly PropertyManager $propertyManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('learning_booking.manager'),
      $container->get('learning_property.manager'),
    );
  }

  public function getFormId(): string {
    return 'learning_booking_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL): array {
    if (!$node || $node->bundle() !== 'property' || !$node->access('view')) {
      throw new NotFoundHttpException();
    }

    $this->property = $node;
    $form['property'] = [
      '#type' => 'item',
      '#title' => $this->t('Property'),
      '#markup' => $node->label(),
    ];
    $form['customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    $form['customer_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#rows' => 4,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create booking'),
      '#button_type' => 'primary',
    ];
    $form['#cache']['max-age'] = 0;

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!$this->property || !$this->propertyManager->isAvailable($this->property)) {
      $form_state->setErrorByName('property', $this->t('This property is no longer available.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!$this->property) {
      return;
    }

    $booking = $this->bookingManager->createBooking($this->property, [
      'customer_name' => trim((string) $form_state->getValue('customer_name')),
      'customer_email' => trim((string) $form_state->getValue('customer_email')),
      'notes' => trim((string) $form_state->getValue('notes')),
    ]);

    $this->messenger()->addStatus($this->t('Booking @id was created.', ['@id' => $booking->id()]));
    $form_state->setRedirect('learning_property.detail', ['node' => $this->property->id()]);
  }

}
