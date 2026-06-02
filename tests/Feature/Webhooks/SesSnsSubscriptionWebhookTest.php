<?php

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SesSnsSubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_confirmation_uses_trusted_fallback_when_signature_invalid(): void
    {
        config([
            'services.ses.sns_topic_arn' => 'arn:aws:sns:eu-central-1:388786438877:ses-pne-system-events',
        ]);

        Http::fake([
            'https://sns.eu-central-1.amazonaws.com/*' => Http::response('<ConfirmSubscriptionResponse/>', 200),
        ]);

        $payload = [
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => 'test-message-id',
            'Token' => 'test-token',
            'TopicArn' => 'arn:aws:sns:eu-central-1:388786438877:ses-pne-system-events',
            'Message' => 'You have chosen to subscribe to the topic.',
            'SubscribeURL' => 'https://sns.eu-central-1.amazonaws.com/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:eu-central-1:388786438877:ses-pne-system-events&Token=test-token',
            'Timestamp' => '2026-06-02T21:00:00.000Z',
            'SignatureVersion' => '1',
            'Signature' => 'invalid-signature-on-purpose',
            'SigningCertURL' => 'https://sns.eu-central-1.amazonaws.com/SimpleNotificationService-123.pem',
        ];

        $response = $this->call(
            'POST',
            route('webhooks.ses.notifications'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain; charset=UTF-8'],
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        );

        $response->assertOk();
        $response->assertSee('Subscription confirmed');
    }
}
