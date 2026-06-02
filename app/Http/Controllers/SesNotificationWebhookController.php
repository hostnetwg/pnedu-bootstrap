<?php

namespace App\Http\Controllers;

use App\Services\SesNotificationService;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SesNotificationWebhookController extends Controller
{
    public function __invoke(Request $request, SesNotificationService $sesNotifications): Response
    {
        $rawBody = $this->readRawBody($request);

        try {
            $message = Message::fromJsonString($rawBody);
        } catch (\Throwable $e) {
            Log::error('SES SNS webhook: failed to parse message', [
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response('Invalid SNS payload', 400);
        }

        if (! $this->isMessageTrusted($message)) {
            Log::warning('SES SNS webhook: message rejected', [
                'type' => $message['Type'] ?? null,
            ]);

            return response('Invalid SNS message', 403);
        }

        try {
            return $this->handleValidatedMessage($message, $sesNotifications);
        } catch (\Throwable $e) {
            Log::error('SES SNS webhook: unhandled error', [
                'type' => $message['Type'] ?? null,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response('SNS handler error', 500);
        }
    }

    private function readRawBody(Request $request): string
    {
        $raw = file_get_contents('php://input');

        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        return (string) $request->getContent();
    }

    private function isMessageTrusted(Message $message): bool
    {
        $validator = new MessageValidator;

        if ($validator->isValid($message)) {
            return true;
        }

        $type = (string) ($message['Type'] ?? '');

        if ($type === 'SubscriptionConfirmation' && $this->isTrustedSubscriptionConfirmation($message)) {
            Log::warning('SES SNS webhook: using TopicArn/URL fallback for subscription confirmation');

            return true;
        }

        if ($type === 'Notification' && $this->isTrustedSesNotification($message)) {
            Log::warning('SES SNS webhook: using TopicArn/payload fallback for SES notification');

            return true;
        }

        return false;
    }

    private function topicArnMatchesConfig(Message $message): bool
    {
        $expectedTopicArn = trim((string) config('services.ses.sns_topic_arn', ''));
        $topicArn = trim((string) ($message['TopicArn'] ?? ''));

        return $expectedTopicArn !== '' && $topicArn === $expectedTopicArn;
    }

    private function isTrustedSubscriptionConfirmation(Message $message): bool
    {
        if (! $this->topicArnMatchesConfig($message)) {
            return false;
        }

        $subscribeUrl = trim((string) ($message['SubscribeURL'] ?? ''));
        $token = trim((string) ($message['Token'] ?? ''));

        if ($token === '' || $subscribeUrl === '' || ! $this->isTrustedSnsHttpsUrl($subscribeUrl)) {
            return false;
        }

        return true;
    }

    private function isTrustedSesNotification(Message $message): bool
    {
        if (! $this->topicArnMatchesConfig($message)) {
            return false;
        }

        $messageId = trim((string) ($message['MessageId'] ?? ''));
        $timestamp = trim((string) ($message['Timestamp'] ?? ''));

        if ($messageId === '' || $timestamp === '') {
            return false;
        }

        $signingCertUrl = trim((string) ($message['SigningCertURL'] ?? ''));

        if ($signingCertUrl !== '' && ! $this->isTrustedSnsHttpsUrl($signingCertUrl)) {
            return false;
        }

        $payload = json_decode($message['Message'] ?? '', true);

        if (! is_array($payload)) {
            return false;
        }

        $notificationType = $payload['notificationType'] ?? null;

        if ($notificationType === 'Bounce') {
            return isset($payload['bounce']['bounceType']);
        }

        if ($notificationType === 'Complaint') {
            return isset($payload['complaint']);
        }

        return false;
    }

    private function isTrustedSnsHttpsUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if (! is_array($parsed) || ($parsed['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = (string) ($parsed['host'] ?? '');

        return (bool) preg_match('/^sns\.[a-zA-Z0-9\-]{3,}\.amazonaws\.com(\.cn)?$/', $host);
    }

    private function handleValidatedMessage(Message $message, SesNotificationService $sesNotifications): Response
    {
        $type = $message['Type'] ?? '';

        if ($type === 'SubscriptionConfirmation') {
            $subscribeUrl = $message['SubscribeURL'] ?? '';
            if ($subscribeUrl === '') {
                return response('Missing SubscribeURL', 400);
            }

            Log::info('SES SNS subscription confirmation received', [
                'subscribe_url' => $subscribeUrl,
                'topic_arn' => $message['TopicArn'] ?? null,
            ]);

            $response = Http::timeout(15)->get($subscribeUrl);
            if (! $response->successful()) {
                Log::error('SES SNS subscription confirmation failed', [
                    'subscribe_url' => $subscribeUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response('Subscription confirmation failed', 502);
            }

            Log::info('SES SNS subscription confirmed');

            return response('Subscription confirmed', 200);
        }

        if ($type === 'UnsubscribeConfirmation') {
            return response('OK', 200);
        }

        if ($type === 'Notification') {
            $payload = json_decode($message['Message'] ?? '', true);
            if (! is_array($payload)) {
                return response('Invalid notification payload', 400);
            }

            $sesNotifications->handleSesEvent($payload);

            return response('OK', 200);
        }

        return response('Unhandled SNS message type', 400);
    }
}
