<?php

namespace Tests\Feature\Whitebox;

use App\Repositories\AccountRepository;
use App\Repositories\BatchRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ResponsibleRepository;
use App\Repositories\StockRepository;
use App\Repositories\StructureRepository;
use App\Repositories\UserRepository;
use App\Repositories\VaccineRepository;
use Exception;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Tests\ReservationTestCase;
use Throwable;

class RepositoryCoverageTest extends ReservationTestCase
{
    /**
     * @dataProvider repositoryProvider
     * @param $class
     * @throws ReflectionException
     */
    public function testRepositoryCoverage($class)
    {
        $faker = app(Generator::class);
        $repository = app($class);
        $repositoryClass = new ReflectionClass($repository);
        $methods = $repositoryClass->getMethods();
        foreach ($methods as $method) {
                if ($method->name === '__construct') {
                    continue;
                }
                $params = $method->getParameters();
                $dictionary = [];
                foreach ($params as $param) {
                    $type = $param->getType() ? $param->getType()->getName() : null;
                    $paramName = $param->name;
                    if ($type === 'string') {
                        $dictionary[$paramName] = [
                            '', Str::random(50), $faker->email, Str::random(500)
                        ];
                        continue;
                    }
                    if ($type === 'int') {
                        $dictionary[$paramName] = [
                            -1, 0, 1
                        ];
                        continue;
                    }
                    if ($type === 'float') {
                        $dictionary[$paramName] = [
                            -1.5, 0, 1.5
                        ];
                        continue;
                    }
                    if ($type === 'boolean' || $type === 'bool') {
                        $dictionary[$paramName] = [
                            true, false
                        ];
                        continue;
                    }
                    if ($type === 'NULL' || !$type) {
                        $dictionary[$paramName] = [
                            null, '', -1, 0, 1, $faker->email
                        ];
                        continue;
                    }
                    /** @var Model $model */
                    $model = app($type);
                    $dictionary[$param->name] = [
                        new $model(),
                        $model::factory()->make()
                    ];
                }
                for ($i = 0; $i < 30; $i++) {
                    try {
                        $callBody = [];
                        foreach ($dictionary as $paramName => $values) {
                            $callBody[$paramName] = Arr::random($values);
                        }
                        $method->invokeArgs($repository, $callBody);
                    } catch (Exception $exception) {
                    } catch (Throwable $exception) {
                        foreach ($callBody as $paramName => $paramValue) {
                            if ($paramValue instanceof Model) {
                                $callBody[$paramName] = $paramValue->toArray();
                            }
                        }
                        $this->fail(
                            "Lanciata eccezione non gestita con il seguente messaggio: {$exception->getMessage()}\ne il seguente body"
                            . print_r($callBody, true));
                    }
                }
        }
    }

    public function repositoryProvider(): array
    {
        return [
            [UserRepository::class],
            [StockRepository::class],
            [VaccineRepository::class],
            [AccountRepository::class],
            [ResponsibleRepository::class],
            [StructureRepository::class],
            [PatientRepository::class],
            [BatchRepository::class],
        ];
    }
}
