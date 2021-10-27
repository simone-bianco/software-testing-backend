<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPatient
 */
class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'heart_disease',
        'allergy',
        'immunosuppression',
        'anticoagulants',
        'covid',
        'account_id',
    ];

    protected $casts = [
        'heart_disease' => 'boolean',
        'allergy' => 'boolean',
        'immunosuppression' => 'boolean',
        'anticoagulants' => 'boolean',
        'covid' => 'boolean',
        'account_id' => 'integer',
    ];

    /**
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function getEmailAttribute(): string
    {
        return $this->account->email;
    }

    /**
     * @return Vaccine[]
     */
    public function getAllowedVaccines(): array
    {
        return Vaccine::all()->toArray();
    }

    public function hasBooking(): bool
    {
        //da prevedere anche uno stato per paziente (vaccinato o no). hasBooking restituisce true anche se stato=vaccinato
        $reservations=$this->reservations()->whereIn('state',['pending','confirmed'])->get();
        //var_dump(count($reservations));

        if(count($reservations)>0) {
            return true;
        }
        else {
            return false;
        }

    }
}
