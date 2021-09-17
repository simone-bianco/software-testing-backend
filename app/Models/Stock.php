<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperStock
 */
class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'structure_id',
        'quantity',
        'batch_id',
        'code',
    ];

    protected $cast = [
        'id' => 'integer',
        'structure_id' => 'integer',
        'code' => 'string',
        'quantity' => 'integer',
        'batch_id' => 'integer',
    ];

    public function structure() :BelongsTo  {
        return $this->belongsTo(Structure::class);
    }

    public function batch() :BelongsTo {
        return $this->belongsTo(Batch::class);
    }

    public function reservations() :HasMany {
        return $this->hasMany(Reservation::class);
    }
}
