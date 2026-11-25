<?php

namespace App\Models;

use App\Support\PneadmMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'pneadm';

    protected $table = 'instructors';

    protected $casts = [
        'is_active' => 'boolean',
        'portal_access_enabled' => 'boolean',
        'sms_notifications_enabled' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'email',
        'phone',
        'bio',
        'bio_html',
        'photo',
        'signature',
        'is_active',
        'portal_access_enabled',
        'sms_notifications_enabled',
        'pnedu_user_id',
        'gender',
    ];

    /**
     * Get the full name of the instructor.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the full name with title of the instructor.
     *
     * @return string
     */
    public function getFullNameWithTitleAttribute()
    {
        $name = $this->first_name.' '.$this->last_name;
        if (! empty($this->title)) {
            $name = $this->title.' '.$name;
        }

        return $name;
    }

    public function publicPhotoUrl(): ?string
    {
        return PneadmMedia::url($this->photo);
    }

    /**
     * Get the appropriate title based on gender.
     *
     * @return string
     */
    public function getGenderTitleAttribute()
    {
        switch (strtolower($this->gender ?? '')) {
            case 'male':
            case 'mężczyzna':
            case 'm':
                return 'Prowadzący';
            case 'female':
            case 'kobieta':
            case 'f':
                return 'Prowadząca';
            default:
                return 'Trener';
        }
    }

    /**
     * Get the courses associated with the instructor.
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }
}
