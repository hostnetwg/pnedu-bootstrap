<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClosedTrainingCase extends Model
{
    public const SOURCE_OFFER_FORM = 'offer_form';

    public const SOURCE_GENERAL_FORM = 'general_form';

    public const STATUS_NEW = 'new';

    public const INSTITUTION_PRESCHOOL = 'preschool';

    public const INSTITUTION_PRIMARY = 'primary';

    public const INSTITUTION_SECONDARY = 'secondary';

    public const INSTITUTION_COMPLEX = 'complex';

    public const INSTITUTION_SPECIAL = 'special';

    public const INSTITUTION_OTHER = 'other';

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
        'institution_type',
        'institution_type_other',
        'message',
        'consent_at',
    ];

    protected $casts = [
        'consent_at' => 'datetime',
    ];

    public static function institutionTypeOptions(): array
    {
        return [
            self::INSTITUTION_PRESCHOOL => 'Przedszkole',
            self::INSTITUTION_PRIMARY => 'Szkoła podstawowa',
            self::INSTITUTION_SECONDARY => 'Szkoła ponadpodstawowa',
            self::INSTITUTION_COMPLEX => 'Zespół szkół / placówek',
            self::INSTITUTION_SPECIAL => 'Placówka specjalna',
            self::INSTITUTION_OTHER => 'Inna',
        ];
    }

    public static function formatInstitutionType(?string $type, ?string $other): string
    {
        if ($type === null || $type === '') {
            return 'Nie podano';
        }

        $label = self::institutionTypeOptions()[$type] ?? $type;

        if ($type !== self::INSTITUTION_OTHER) {
            return $label;
        }

        $detail = trim((string) $other);

        return $detail === '' ? 'Inna' : 'Inna — '.$detail;
    }

    public function events(): HasMany
    {
        return $this->hasMany(ClosedTrainingCaseEvent::class);
    }
}
