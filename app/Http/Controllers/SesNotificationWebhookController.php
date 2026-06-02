<?php

namespace App\Http\Controllers;

use App\Services\SesNotificationService;
use Aws\Sns\Exception\InvalidSnsMessageException;
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
        try {
            $message = Message::fromJsonString($request->getContent());
            (new MessageValidator)->validate($message);
        } catch (InvalidSnsMessageException $e) {
            Log::warning('SES SNS webhook: invalid message', ['error' => $e->getMessage()]);

            return response('Invalid SNS message', 403);
        } catch (\Throwable $e) {
            Log::error('SES SNS webhook: failed to parse or validate message', [
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response('SNS message processing error', 500);
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

    private function handleValidatedMessage(Message $message, SesNotificationService $sesNotifications): Response
    {
        $type = $message['Type'] ?? '';

        if ($type === 'SubscriptionConfirmation') {
            $subscribeUrl = $message['SubscribeURL'] ?? '';
            if ($subscribeUrl === '') {
                return response('Missing SubscribeURL', 400);
            }

            $response = Http::timeout(15)->get($subscribeUrl);
            if (! $response->successful()) {
                Log::error('SES SNS subscription confirmation failed', [
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
