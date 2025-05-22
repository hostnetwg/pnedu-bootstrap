<?php

namespace App\Models;

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
    protected $connection = 'admpnedu';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'instructors';

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
        'photo',
        'signature',
        'is_active',
    ];

    /**
     * Get the full name of the instructor.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the courses associated with the instructor.
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }
}