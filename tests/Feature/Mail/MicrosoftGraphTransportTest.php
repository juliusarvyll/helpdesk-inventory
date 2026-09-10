<?php

use App\Mail\Transport\MicrosoftGraphTransport;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;

function makeGraphTransport(): MicrosoftGraphTransport
{
    return new MicrosoftGraphTransport(
        tenantId: 'tenant-123',
        clientId: 'client-123',
        clientSecret: 'secret-123',
        mailbox: 'noreply@example.com',
    );
}

it('sends the email through the Graph sendMail endpoint with the acquired token', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600], 200),
        'graph.microsoft.com/v1.0/users/noreply@example.com/sendMail' => Http::response('', 202),
    ]);

    $email = (new Email)
        ->from('noreply@example.com')
        ->to('requester@example.com')
        ->subject('Ticket created')
        ->html('<p>Body</p>');

    makeGraphTransport()->send($email);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'login.microsoftonline.com/tenant-123')
        && $request['client_id'] === 'client-123'
        && $request['client_secret'] === 'secret-123'
        && $request['scope'] === 'https://graph.microsoft.com/.default'
        && $request['grant_type'] === 'client_credentials');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'graph.microsoft.com/v1.0/users/noreply@example.com/sendMail')) {
            return false;
        }

        expect($request->hasHeader('Authorization', 'Bearer fake-token'))->toBeTrue();
        expect($request['message']['subject'])->toBe('Ticket created');
        expect($request['message']['body'])->toBe(['contentType' => 'HTML', 'content' => '<p>Body</p>']);
        expect($request['message']['toRecipients'])->toBe([
            ['emailAddress' => ['address' => 'requester@example.com']],
        ]);

        return true;
    });
});

it('caches the access token instead of requesting it on every send', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600], 200),
        'graph.microsoft.com/v1.0/users/noreply@example.com/sendMail' => Http::response('', 202),
    ]);

    $email = (new Email)->from('noreply@example.com')->to('requester@example.com')->subject('x')->text('x');

    $transport = makeGraphTransport();
    $transport->send($email);
    $transport->send($email);

    Http::assertSentCount(3); // 1 token request + 2 sendMail requests
});

it('throws a TransportException when the access token request fails', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    $email = (new Email)->from('noreply@example.com')->to('requester@example.com')->subject('x')->text('x');

    expect(fn () => makeGraphTransport()->send($email))
        ->toThrow(TransportException::class, 'Failed to acquire a Microsoft Graph access token');
});

it('throws a TransportException when Graph rejects the sendMail request', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600], 200),
        'graph.microsoft.com/v1.0/users/noreply@example.com/sendMail' => Http::response(['error' => 'Forbidden'], 403),
    ]);

    $email = (new Email)->from('noreply@example.com')->to('requester@example.com')->subject('x')->text('x');

    expect(fn () => makeGraphTransport()->send($email))
        ->toThrow(TransportException::class, 'Microsoft Graph sendMail failed with status 403');
});
