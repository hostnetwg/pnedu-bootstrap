<?php

namespace App\Services;

use App\Models\ClosedTrainingCase;
use App\Models\ClosedTrainingCaseEvent;
use App\Models\TrainingOffer;
use Illuminate\Support\Facades\DB;

class ClosedTrainingInquiryRecorder
{
    public function fromOffer(TrainingOffer $offer, array $data, string $offerUrl): ClosedTrainingCase
    {
        return $this->store(
            source: ClosedTrainingCase::SOURCE_OFFER_FORM,
            topic: $offer->title,
            offerTitle: $offer->title,
            offerUrl: $offerUrl,
            trainingOfferId: $offer->id,
            data: $data,
            eventBody: 'Zapytanie z formularza oferty na pnedu.pl.',
        );
    }

    public function fromGeneralForm(array $data): ClosedTrainingCase
    {
        $topic = filled($data['offer_topic'] ?? null)
            ? $data['offer_topic']
            : '(temat nie określony)';

        return $this->store(
            source: ClosedTrainingCase::SOURCE_GENERAL_FORM,
            topic: $topic,
            offerTitle: null,
            offerUrl: null,
            trainingOfferId: null,
            data: $data,
            eventBody: 'Zapytanie z ogólnego formularza na pnedu.pl.',
        );
    }

    private function store(
        string $source,
        string $topic,
        ?string $offerTitle,
        ?string $offerUrl,
        ?int $trainingOfferId,
        array $data,
        string $eventBody,
    ): ClosedTrainingCase {
        return DB::connection('pneadm')->transaction(function () use ($source, $topic, $offerTitle, $offerUrl, $trainingOfferId, $data, $eventBody) {
            $snapshot = ClosedTrainingCase::submittedSnapshot([
                'contact_name' => $data['name'],
                'contact_email' => $data['email'],
                'contact_phone' => $data['phone'] ?? null,
                'institution' => $data['institution'] ?? null,
                'institution_type' => $data['institution_type'],
                'institution_type_other' => $data['institution_type_other'] ?? null,
                'preferred_format' => $data['preferred_format'] ?? null,
                'training_offer_id' => $trainingOfferId,
                'offer_title' => $offerTitle,
                'topic' => $topic,
                'message' => $data['message'],
            ]);

            $case = ClosedTrainingCase::query()->create([
                'source' => $source,
                'training_offer_id' => $trainingOfferId,
                'status' => ClosedTrainingCase::STATUS_NEW,
                'topic' => $topic,
                'offer_url' => $offerUrl,
                'preferred_format' => $data['preferred_format'] ?? null,
                'contact_name' => $data['name'],
                'contact_email' => $data['email'],
                'contact_phone' => $data['phone'] ?? null,
                'institution' => $data['institution'] ?? null,
                'institution_type' => $data['institution_type'],
                'institution_type_other' => $data['institution_type_other'] ?? null,
                'message' => $data['message'],
                'submitted_payload' => $snapshot,
                'consent_at' => now(),
            ]);

            $case->events()->create([
                'user_id' => null,
                'event_type' => ClosedTrainingCaseEvent::TYPE_CREATED,
                'body' => $eventBody,
                'happened_at' => now(),
            ]);

            return $case;
        });
    }
}
