<?php

namespace App\Models;

use Arr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Str;

/**
 * @property bool $hasRecall
 * @property Account $account
 * @property User $user
 * @property string $code
 * @property string $notes
 * @mixin IdeHelperReservation
 */
class Reservation extends Model
{
    use HasFactory;

    public const PENDING_STATE = 'pending';
    public const CONFIRMED_STATE = 'confirmed';
    public const COMPLETED_STATE = 'completed';
    public const CANCELED_STATE = 'cancelled';

    /**
     * @return string[]
     */
    public static function getStates(): array
    {
        return [
            self::PENDING_STATE,
            self::CONFIRMED_STATE,
            self::COMPLETED_STATE,
            self::CANCELED_STATE,
        ];
    }

    /**
     * @param  string  $state
     * @return string
     */
    public static function stateToLabel(string $state): string
    {
        switch ($state) {
            case self::PENDING_STATE:
                return "in attesa di conferma";
            case self::CONFIRMED_STATE:
                return "confermata";
            case self::COMPLETED_STATE:
                return "completa";
            case self::CANCELED_STATE:
                return "cancellata";
            default:
                return "non valida";
        }
    }

    protected $fillable = [
        'date',
        'updated_at',
        'time',
        'state',
        'code',
        'patient_id',
        'stock_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'time' => 'datetime:H:i',
        'state' => 'string',
        'code' => 'string',
        'notes' => 'string',
        'patient_id' => 'integer',
        'stock_id' => 'integer',
    ];

    public function stock() :BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function getHasRecallAttribute(): bool
    {
        return Reservation::wherePatientId($this->patient_id)
                ->where('state', '!=', self::CANCELED_STATE)
                ->where('date', '>', $this->date)
                ->get()
                ->count() >= 1;
    }

    /**
     * @return string[]
     */
    public static function getFilters(): array
    {
        return ['search', 'sort_order', 'sort_field', 'items_per_page', 'current_page', 'state_filters', 'from_date',
            'to_date', 'from_time', 'to_time'];
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->whereHas('patient', function (Builder $query) use ($search) {
                    $query->whereHas('account', function (Builder $query) use ($search) {
                        $query->whereHas('user', function (Builder $query) use ($search) {
                            $query->where('email', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('mobile_phone', 'like', '%'.$search.'%')
                                ->orWhere('notes', 'like', '%'.$search.'%')
                                ->orWhere('first_name', 'like', '%'.$search.'%');
                        });
                    });
                })->orWhereHas('stock', function (Builder $query) use ($search) {
                    $query->whereHas('batch', function (Builder $query) use ($search) {
                        $query->whereHas('vaccine', function (Builder $query) use ($search) {
                            $query->where('name','like', '%'.$search.'%');
                        });
                    });
                });
            });
        });

        $stateFilters = Arr::get($filters, 'state_filters');
        if ($stateFilters && is_array($stateFilters)) {
            $query->whereIn('state', $stateFilters);
        }

        $sortField = Str::afterLast(Arr::get($filters, 'sort_field'), '.');
        if ($sortField) {
            $sortOrder = Arr::get($filters, 'sort_order') ?? 'asc';
            if (!strcmp($sortField, 'date')) {
                $query->orderBy($sortField, $sortOrder)->orderBy('time');
            } elseif (!strcmp($sortField, 'email') || !strcmp($sortField, 'name')) {
                $query->join('patients', 'patients.id', '=', 'reservations.patient_id')
                    ->join('accounts', 'accounts.id', '=', 'patients.account_id')
                    ->join('users', 'users.id', '=', 'accounts.user_id')
                    ->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy($sortField, $sortOrder);
            }
        }

        $fromDate = Arr::get($filters, 'from_date');
        if ($fromDate) {
            $query->whereDate('date', '>=', Carbon::make($fromDate));
        }

        $toDate = Arr::get($filters, 'to_date');
        if ($toDate) {
            $query->whereDate('date', '<=', Carbon::make($toDate));
        }

        $fromTime = Arr::get($filters, 'from_time');
        if ($fromTime) {
            $query->where('time', '>=', $fromTime);
        }

        $toTime = Arr::get($filters, 'to_time');
        if ($toTime) {
            $query->where('time', '<=', $toTime);
        }
    }
}
