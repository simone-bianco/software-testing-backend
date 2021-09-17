<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperBatch
 */
class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        "vaccine_id",
        "code"
    ];

    protected $casts = [
        "vaccine_id" => 'integer',
        "code" => 'string'
    ];

    public function vaccine() :BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function stocks() : HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
