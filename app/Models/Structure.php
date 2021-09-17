<?php

namespace App\Models;

use App\Exceptions\MaxCapacityExceededException;
use App\Exceptions\NoAvailableStockException;
use Arr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Str;

/**
 * @property Collection $reservations
 * @property int $halfHourCapacity
 * @property int $hourCapacity
 * @property int $timeSlicesPerDay
 * @property int $endingHour
 * @mixin IdeHelperStructure
 */
class Structure extends Model
{
    use HasFactory;

    public const TIME_SLICE_MINUTES = 30;
    public const NUMBER_OF_WORKING_HOURS = 12;
    public const STARTING_HOUR = 8;

    protected $fillable = [
        'name',
        'city',
        'address',
        'cap',
        'phone_number',
        'last_reservation_update',
    ];

    protected $cast = [
        'id' => 'integer',
        'name' => 'string',
        'city' => 'string',
        'address' => 'string',
        'cap' => 'integer',
        'phone_number' => 'string',
        'last_reservation_update' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @param  Carbon  $date
     * @return string
     * @throws MaxCapacityExceededException
     */
    public function getNextAvailableHour(Carbon $date): string
    {
        $existingReservations = Reservation::query()
            ->whereIn('stock_id', $this->stocks()->pluck('id'))
            ->whereDate('date', '=', $date)
            ->where('state', '!=', Reservation::CANCELED_STATE);

        if ($existingReservations->get()->count() > $this->capacity) {
            throw new MaxCapacityExceededException(sprintf(
                "Capacità della struttura di %s raggiunta", $this->capacity)
            );
        }

        $existingReservations = $existingReservations
            ->orderBy('time')
            ->selectRaw('time, count(*) as bookings')
            ->groupBy('time')
            ->pluck('bookings', 'time')
            ->toArray();

        $time = Carbon::make($date)->setTime(self::STARTING_HOUR, 0);
        $endingTime = Carbon::make($date)->setTime($this->endingHour, 0)->format('H:i');

        while (strcmp($time->format('H:i'), $endingTime)) {
            $hour = $time->format('H:i:00');
            if (array_key_exists($hour, $existingReservations)) {
                if ($existingReservations[$hour] < $this->halfHourCapacity) {
                    return $hour;
                }
                $time->addMinutes(self::TIME_SLICE_MINUTES);
                continue;
            }
            return $hour;
        }

        throw new MaxCapacityExceededException(sprintf(
                "Capacità della struttura di %s raggiunta", $this->capacity)
        );
    }

    /**
     * @return Collection
     */
    public function getAvailableVaccines(): Collection
    {
        return $this->stocks()
            ->where('quantity', '>', 0)
            ->join('batches', 'batches.id', '=', 'stocks.batch_id')
            ->join('vaccines', 'vaccines.id', '=', 'batches.vaccine_id')
            ->selectRaw('name, count(*) as stocks_count')
            ->groupBy('name')
            ->selectRaw('sum(quantity) as qty, name')
            ->get();
    }

    /**
     * @return Collection
     */
    public function getVaccinesWithQty(): Collection
    {
        return $this->stocks()
            ->join('batches', 'batches.id', '=', 'stocks.batch_id')
            ->join('vaccines', 'vaccines.id', '=', 'batches.vaccine_id')
            ->groupBy('name')
            ->selectRaw(
                'sum(quantity) as qty, name, max(vaccines.src) as src, '
                . 'max(vaccines.lazy_src) as lazy_src, max(vaccines.url) as url'
            )
            ->get();
    }

    /**
     * @param  array  $vaccines
     * @return Model|HasMany|object|null
     * @throws NoAvailableStockException
     */
    public function getMaxStock(array $vaccines): Stock
    {
        $batches = Batch::whereIn('vaccine_id', Arr::pluck($vaccines, 'id'))->get();

        $stock = $this->stocks()
            ->whereIn('batch_id', Arr::pluck($batches, 'id'))
            ->orderBy('quantity', 'desc')
            ->first();

        if (!$stock) {
            throw new NoAvailableStockException("Nessuno stock disponibile");
        }

        if ($stock->quantity <= 0) {
            throw new NoAvailableStockException("Nessuno stock disponibile");
        }

        return $stock;
    }

    /**
     * @return int
     */
    public function getHalfHourCapacityAttribute(): int
    {
        return (int) ($this->capacity / $this->timeSlicesPerDay);
    }

    /**
     * @return int
     */
    public function getHourCapacityAttribute(): int
    {
        return $this->getHalfHourCapacityAttribute() * 2;
    }

    /**
     * @return int
     */
    public function getTimeSlicesPerDayAttribute(): int
    {
        return (int) ((60 / self::TIME_SLICE_MINUTES) * self::NUMBER_OF_WORKING_HOURS);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function vaccines(): BelongsToMany
    {
        return $this->belongsToMany(Vaccine::class);
    }

    public function responsibles(): HasMany
    {
        return $this->hasMany(Responsible::class);
    }

    /**
     * @return string[]
     */
    public function getBusyDates(): array
    {
        return $this->stocks()
            ->join('reservations', 'reservations.stock_id', '=', 'stocks.id')
            ->where('reservations.state', '!=', Reservation::CANCELED_STATE)
            ->where('reservations.date', '>=', Carbon::today()->format('Y-m-d'))
            ->orderBy('reservations.date')
            ->groupBy('reservations.date')
            ->selectRaw('reservations.date, count(*) as bookings')
            ->get()
            ->where('bookings', '>=', $this->capacity)
            ->pluck('date')
            ->toArray();
    }

    /**
     * @param  string  $date
     * @param  string[]|Carbon[]  $excludeHours
     * @return array
     */
    public function getBusyTimes(string $date, array $excludeHours = []): array
    {
        array_walk($excludeHours, function ($excludeHours) {
            return Carbon::make($excludeHours)->format('H:i:00');
        });

        return $this->stocks()
            ->join('reservations', 'reservations.stock_id', '=', 'stocks.id')
            ->where('reservations.state', '!=', Reservation::CANCELED_STATE)
            ->where('reservations.date', '=', Carbon::make($date)->format('Y-m-d'))
            ->orderBy('reservations.time')
            ->groupBy('reservations.time')
            ->whereNotIn('time', $excludeHours)
            ->selectRaw('reservations.time, count(*) as bookings')
            ->get()
            ->where('bookings', '>=', $this->halfHourCapacity)
            ->pluck('time')
            ->transform(function ($time) { return Str::beforeLast($time, ':'); })
            ->toArray();
    }
}
