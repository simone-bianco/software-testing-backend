<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $email
 * @property Account $account
 * @property Structure $structure
 * @mixin IdeHelperResponsible
 */
class Responsible extends Model
{
    use HasFactory;

    protected $fillable = [
      "structure_id",
      "account_id",
    ];

    protected $casts = [
        "structure_id" => "integer",
        "account_id" => "integer",
    ];

    /**
     * @return string
     */
    public function getEmailAttribute(): string
    {
        return $this->account->email;
    }

    /**
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
