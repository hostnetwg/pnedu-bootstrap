<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClosedTrainingCase extends Model
{
    public const SOURCE_OFFER_FORM = 'offer_form';

    public const SOURCE_GENERAL_FORM = 'general_form';

    public const STATUS_NEW = 'new';

    protected $connection = 'pneadm';

    protected $table = 'closed_training_cases';

    protected $fillable = [
        'source',
        'training_offer_id',
        'status',
        'topic',
        'offer_url',
        'preferred_format',
        'contact_name',
        'contact_email',
        'contact_phone',
        'institution',
        'message',
        'consent_at',
    ];

    protected $casts = [
        'consent_at' => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(ClosedTrainingCaseEvent::class);
    }
}
