<?php

declare(strict_types=1);

namespace Drupal\learning_notification\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

final class BookingNotificationService
{
    public function __construct(
        private readonly MailManagerInterface     $mailManager,
        private readonly LanguageManagerInterface $languageManager,
        private readonly LoggerInterface          $logger,
    )
    {
    }

    public function sendApproved(NodeInterface $booking): bool
    {
        return $this->send(
            'booking_approved',
            $booking,
        );
    }

    public function sendRejected(NodeInterface $booking): bool
    {
        return $this->send(
            'booking_rejected',
            $booking,
        );
    }

    public function sendCancelled(NodeInterface $booking): bool
    {
        return $this->send(
            'booking_cancelled',
            $booking,
        );
    }

    private function send(
        string        $key,
        NodeInterface $booking,
    ): bool
    {

        $owner = $booking->getOwner();

        dd($owner);

        $email = $owner->getEmail();

        if (empty($email)) {
            $this->logger->warning(
                'Unable to send @key notification for booking @booking because the customer has no email address.',
                [
                    '@key' => $key,
                    '@booking' => $booking->id(),
                ],
            );

            return FALSE;
        }

        $params = [
            'booking' => $booking,
            'customer' => $owner,
        ];

        $langcode = $owner->getPreferredLangcode()
            ?: $this->languageManager
                ->getDefaultLanguage()
                ->getId();

        try {
            $result = $this->mailManager->mail(
                'learning_notification',
                $key,
                $email,
                $langcode,
                $params,
            );
        }
        catch (\Throwable $exception) {
            $this->logger->error(
                'An exception occurred while sending @key notification for booking @booking to @email: @message',
                [
                    '@key' => $key,
                    '@booking' => $booking->id(),
                    '@email' => $email,
                    '@message' => $exception->getMessage(),
                    'exception' => $exception,
                ],
            );

            throw $exception;
        }

        if (empty($result['result'])) {
            $this->logger->error(
                'Failed to send @key notification for booking @booking to @email.',
                [
                    '@key' => $key,
                    '@booking' => $booking->id(),
                    '@email' => $email,
                ],
            );

            return FALSE;
        }

        $this->logger->info(
            'Successfully sent @key notification for booking @booking to @email.',
            [
                '@key' => $key,
                '@booking' => $booking->id(),
                '@email' => $email,
            ],
        );

        return TRUE;
    }

}
