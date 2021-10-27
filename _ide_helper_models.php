<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Account
 *
 * @mixin IdeHelperAccount
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property mixed $date_of_birth
 * @property int $gender
 * @property string $fiscal_code
 * @property string $city
 * @property string $address
 * @property string $cap
 * @property string $mobile_phone
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Director|null $director
 * @property-read string $email
 * @property-read string $name
 * @property-read \App\Models\Patient|null $patient
 * @property-read \App\Models\Responsible|null $responsible
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\AccountFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereFiscalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereMobilePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUserId($value)
 */
	class IdeHelperAccount extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Batch
 *
 * @mixin IdeHelperBatch
 * @property int $id
 * @property string $code
 * @property int $vaccine_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stock[] $stocks
 * @property-read int|null $stocks_count
 * @property-read \App\Models\Vaccine $vaccine
 * @method static \Database\Factories\BatchFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Batch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Batch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Batch query()
 * @method static \Illuminate\Database\Eloquent\Builder|Batch whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Batch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Batch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Batch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Batch whereVaccineId($value)
 */
	class IdeHelperBatch extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Director
 *
 * @mixin IdeHelperDirector
 * @property int $id
 * @property int $account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\DirectorFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Director newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Director newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Director query()
 * @method static \Illuminate\Database\Eloquent\Builder|Director whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Director whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Director whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Director whereUpdatedAt($value)
 */
	class IdeHelperDirector extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Patient
 *
 * @mixin IdeHelperPatient
 * @property int $id
 * @property bool $heart_disease
 * @property bool $allergy
 * @property bool $immunosuppression
 * @property bool $anticoagulants
 * @property bool $covid
 * @property int $account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read string $email
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Reservation[] $reservations
 * @property-read int|null $reservations_count
 * @method static \Database\Factories\PatientFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Patient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Patient query()
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereAllergy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereAnticoagulants($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereCovid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereHeartDisease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereImmunosuppression($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Patient whereUpdatedAt($value)
 */
	class IdeHelperPatient extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Reservation
 *
 * @property bool $hasRecall
 * @property Account $account
 * @property User $user
 * @property string $code
 * @mixin IdeHelperReservation
 * @property int $id
 * @property \datetime $date
 * @property \datetime $time
 * @property string $state
 * @property int $patient_id
 * @property int $stock_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \datetime|null $updated_at
 * @property-read \App\Models\Patient $patient
 * @property-read \App\Models\Stock $stock
 * @method static \Database\Factories\ReservationFactory factory(...$parameters)
 * @method static Builder|Reservation filter(array $filters)
 * @method static Builder|Reservation newModelQuery()
 * @method static Builder|Reservation newQuery()
 * @method static Builder|Reservation query()
 * @method static Builder|Reservation whereCode($value)
 * @method static Builder|Reservation whereCreatedAt($value)
 * @method static Builder|Reservation whereDate($value)
 * @method static Builder|Reservation whereId($value)
 * @method static Builder|Reservation wherePatientId($value)
 * @method static Builder|Reservation whereState($value)
 * @method static Builder|Reservation whereStockId($value)
 * @method static Builder|Reservation whereTime($value)
 * @method static Builder|Reservation whereUpdatedAt($value)
 */
	class IdeHelperReservation extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Responsible
 *
 * @property string $email
 * @property Account $account
 * @property Structure $structure
 * @mixin IdeHelperResponsible
 * @property int $id
 * @property int $structure_id
 * @property int $account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ResponsibleFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible query()
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Responsible whereUpdatedAt($value)
 */
	class IdeHelperResponsible extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Stock
 *
 * @mixin IdeHelperStock
 * @property int $id
 * @property int $structure_id
 * @property int $quantity
 * @property string $code
 * @property int $batch_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Batch $batch
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Reservation[] $reservations
 * @property-read int|null $reservations_count
 * @property-read \App\Models\Structure $structure
 * @method static \Database\Factories\StockFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock query()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock whereBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock whereUpdatedAt($value)
 */
	class IdeHelperStock extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Structure
 *
 * @property Collection $reservations
 * @property int $halfHourCapacity
 * @property int $hourCapacity
 * @property int $timeSlicesPerDay
 * @property int $endingHour
 * @mixin IdeHelperStructure
 * @property int $id
 * @property string $name
 * @property int $capacity
 * @property string $region
 * @property string $address
 * @property string|null $phone_number
 * @property string $last_reservation_update
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $half_hour_capacity
 * @property-read int $hour_capacity
 * @property-read int $time_slices_per_day
 * @property-read Collection|\App\Models\Responsible[] $responsibles
 * @property-read int|null $responsibles_count
 * @property-read Collection|\App\Models\Stock[] $stocks
 * @property-read int|null $stocks_count
 * @property-read Collection|\App\Models\Vaccine[] $vaccines
 * @property-read int|null $vaccines_count
 * @method static \Database\Factories\StructureFactory factory(...$parameters)
 * @method static Builder|Structure newModelQuery()
 * @method static Builder|Structure newQuery()
 * @method static Builder|Structure query()
 * @method static Builder|Structure whereAddress($value)
 * @method static Builder|Structure whereCapacity($value)
 * @method static Builder|Structure whereCreatedAt($value)
 * @method static Builder|Structure whereId($value)
 * @method static Builder|Structure whereLastReservationUpdate($value)
 * @method static Builder|Structure whereName($value)
 * @method static Builder|Structure wherePhoneNumber($value)
 * @method static Builder|Structure whereRegion($value)
 * @method static Builder|Structure whereUpdatedAt($value)
 */
	class IdeHelperStructure extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @mixin IdeHelperUser
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property string|null $profile_photo_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Director|null $director
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Patient|null $patient
 * @property-read \App\Models\Responsible|null $responsible
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCurrentTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class IdeHelperUser extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Vaccine
 *
 * @mixin IdeHelperVaccine
 * @property int $id
 * @property string $name
 * @property int $vaccine_doses
 * @property string|null $src
 * @property string|null $lazy_src
 * @property string|null $url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Batch[] $batches
 * @property-read int|null $batches_count
 * @method static \Database\Factories\VaccineFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine query()
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereLazySrc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereSrc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vaccine whereVaccineDoses($value)
 */
	class IdeHelperVaccine extends \Eloquent {}
}

