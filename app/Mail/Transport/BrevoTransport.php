<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    public function __construct(
        protected string $apiKey,
        protected int $timeout = 10,
        protected string $endpoint = 'https://api.brevo.com/v3/smtp/email',
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new TransportException('Brevo API key is not configured.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $payload = [
            'sender' => $this->formatAddress($envelope->getSender()),
            'to' => $this->formatAddresses($this->getRecipients($email, $envelope)),
            'subject' => (string) $email->getSubject(),
        ];

        if ($cc = $email->getCc()) {
            $payload['cc'] = $this->formatAddresses($cc);
        }

        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = $this->formatAddresses($bcc);
        }

        if ($replyTo = $email->getReplyTo()) {
            $payload['replyTo'] = $this->formatAddress($replyTo[0]);
        }

        if (($html = $email->getHtmlBody()) !== null && $html !== '') {
            $payload['htmlContent'] = (string) $html;
        }

        if (($text = $email->getTextBody()) !== null && $text !== '') {
            $payload['textContent'] = (string) $text;
        }

        if ($attachments = $email->getAttachments()) {
            $payload['attachment'] = array_map(function ($attachment) {
                $filename = $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename')
                    ?? $attachment->getName()
                    ?? 'attachment';

                return [
                    'name' => $filename,
                    'content' => base64_encode($attachment->getBody()),
                ];
            }, $attachments);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders(['api-key' => $this->apiKey])
                ->post($this->endpoint, $payload);
        } catch (\Throwable $exception) {
            throw new TransportException(
                sprintf('Request to Brevo API failed: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        if ($response->failed()) {
            throw new TransportException(
                sprintf('Request to Brevo API failed (%d): %s', $response->status(), $response->body())
            );
        }

        if ($messageId = $response->json('messageId')) {
            $email->getHeaders()->addHeader('X-Brevo-Message-ID', (string) $messageId);
        }
    }

    /**
     * @return list<Address>
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        $ccAndBcc = array_merge($email->getCc(), $email->getBcc());

        return array_values(array_filter(
            $envelope->getRecipients(),
            fn (Address $address): bool => ! in_array($address, $ccAndBcc, true)
        ));
    }

    /**
     * @param  iterable<Address>  $addresses
     * @return list<array{email: string, name?: string}>
     */
    protected function formatAddresses(iterable $addresses): array
    {
        $formatted = [];
        foreach ($addresses as $address) {
            $formatted[] = $this->formatAddress($address);
        }

        return $formatted;
    }

    /**
     * @return array{email: string, name?: string}
     */
    protected function formatAddress(Address $address): array
    {
        $entry = ['email' => $address->getAddress()];
        if ($address->getName() !== '') {
            $entry['name'] = $address->getName();
        }

        return $entry;
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
