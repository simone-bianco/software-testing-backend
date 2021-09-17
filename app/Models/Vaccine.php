<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperVaccine
 */
class Vaccine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'vaccine_doses',
    ];

    protected $casts = [
        'name' => 'string',
        'vaccine_doses' => 'integer',
    ];

    public function batches() :HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
