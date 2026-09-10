<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends mail through Microsoft Graph's sendMail API using app-only
 * (client credentials) auth. Requires the Azure AD app registration to
 * have the Mail.Send *application* permission, admin-consented, and the
 * mailbox in config('mail.from.address') to exist on a verified domain
 * in that tenant.
 */
class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $mailbox,
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'graph';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new TransportException('The Microsoft Graph transport only supports Symfony\Component\Mime\Email messages.');
        }

        $response = Http::withToken($this->accessToken())
            ->post("https://graph.microsoft.com/v1.0/users/{$this->mailbox}/sendMail", [
                'message' => $this->buildMessagePayload($email),
                'saveToSentItems' => 'true',
            ]);

        if ($response->failed()) {
            throw new TransportException(
                "Microsoft Graph sendMail failed with status {$response->status()}: {$response->body()}"
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMessagePayload(Email $email): array
    {
        $payload = [
            'subject' => (string) $email->getSubject(),
            'body' => $this->buildBody($email),
            'toRecipients' => $this->mapAddresses($email->getTo()),
        ];

        if ($cc = $this->mapAddresses($email->getCc())) {
            $payload['ccRecipients'] = $cc;
        }

        if ($bcc = $this->mapAddresses($email->getBcc())) {
            $payload['bccRecipients'] = $bcc;
        }

        if ($replyTo = $this->mapAddresses($email->getReplyTo())) {
            $payload['replyTo'] = $replyTo;
        }

        if ($attachments = $this->buildAttachments($email)) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    private function buildBody(Email $email): array
    {
        if ($html = $email->getHtmlBody()) {
            return ['contentType' => 'HTML', 'content' => (string) $html];
        }

        return ['contentType' => 'Text', 'content' => (string) $email->getTextBody()];
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array<string, mixed>>
     */
    private function mapAddresses(array $addresses): array
    {
        return array_map(fn (Address $address): array => [
            'emailAddress' => array_filter([
                'address' => $address->getAddress(),
                'name' => $address->getName() ?: null,
            ]),
        ], $addresses);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $part) {
            $attachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $part->getFilename() ?? 'attachment',
                'contentType' => $part->getContentType(),
                'contentBytes' => base64_encode($part->getBody()),
            ];
        }

        return $attachments;
    }

    private function accessToken(): string
    {
        return Cache::remember(
            "azure-graph-token.{$this->tenantId}.{$this->clientId}",
            now()->addMinutes(50),
            function (): string {
                $response = Http::asForm()->post(
                    "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
                    [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ]
                );

                if ($response->failed() || ! $response->json('access_token')) {
                    throw new TransportException(
                        "Failed to acquire a Microsoft Graph access token (status {$response->status()}): {$response->body()}"
                    );
                }

                return $response->json('access_token');
            }
        );
    }
}
