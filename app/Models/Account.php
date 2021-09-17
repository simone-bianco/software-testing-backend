<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperAccount
 */
class Account extends Model
{
    use HasFactory;

    public const GENDER_MALE = 0;
    public const GENDER_FEMALE = 1;
    public const GENDER_NOT_SPECIFIED = 2;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'fiscal_code',
        'address',
        'city',
        'cap',
        'mobile_phone',
        'user_id',
    ];

    protected $casts = [
        'first_name' => 'string',
        'last_name' => 'string',
        'date_of_birth' => 'date:Y-m-d',
        'gender' => 'integer',
        'fiscal_code' => 'string',
        'city' => 'string',
        'cap' => 'string',
        'mobile_phone' => 'string',
        'address' => 'string',
        'user_id' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * @return HasOne
     */
    public function responsible(): HasOne
    {
        return $this->hasOne(Responsible::class);
    }

    /**
     * @return HasOne
     */
    public function director(): HasOne
    {
        return $this->hasOne(Director::class);
    }

    /**
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getEmailAttribute(): string
    {
        return $this->user->email;
    }
}
