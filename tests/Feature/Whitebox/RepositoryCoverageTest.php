<?php

namespace Tests\Feature\Whitebox;

use App\Models\User;
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
    protected function getDictByType(?string $type): array
    {
        $faker = app(Generator::class);
        if ($type === 'string') {
            return [
                '', Str::random(50), $faker->email, Str::random(500), User::firstOrFail()->email
            ];
        }
        if ($type === 'int') {
            return [
                -1, 0, 1, PHP_INT_MAX + 1
            ];
        }
        if ($type === 'float') {
            return [
                -1.5, 0, 1.5, PHP_FLOAT_MAX + 1
            ];
        }
        if ($type === 'boolean' || $type === 'bool') {
            return [
                true, false
            ];
        }
        if ($type === 'NULL' || !$type) {
            return [
                null, '', -1, 0, 1, $faker->email
            ];
        }
        /** @var Model $model */
        $model = app($type);
        $modelInstance = new $model();
        return [
            $modelInstance,
            $model::factory()->make(),
            $modelInstance->first() ?? null
        ];
    }

    protected function getMethodDictionary($method): array
    {
        if ($method->name === '__construct') {
            return [];
        }
        $params = $method->getParameters();
        $dictionary = [];
        foreach ($params as $param) {
            $type = $param->getType() ? $param->getType()->getName() : null;
            $paramName = $param->name;
            $dictionary[$paramName] = $this->getDictByType($type);
        }
        return $dictionary;
    }

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

    /**
     * @dataProvider repositoryProvider
     * @param $class
     * @throws ReflectionException
     */
    public function testRepositoryCoverageKWay($class)
    {
        $repository = app($class);
        $repositoryClass = new ReflectionClass($repository);
        $methods = $repositoryClass->getMethods();
        foreach ($methods as $method) {
            if ($method->name === "__construct") {
                continue;
            }
            $dictionaries = $this->getMethodDictionary($method);
            $fieldsCombinations = $this->generateKCombinations($dictionaries, 5);
            $keysByCombination = [];
            $lengthsByKeyCombinations = [];
            foreach ($fieldsCombinations as $keysCombination => $fieldsCombination) {
                $keysByCombination[$keysCombination] = explode('.', $keysCombination);
                $lengthsByKeyCombinations[$keysCombination] = sizeof($fieldsCombination);
            }
            $iterations = is_array(Arr::first($fieldsCombinations)) ? sizeof($fieldsCombinations) : 1;
            for ($i = 0; $i < $iterations; $i++) {
                $callBody = [];
                foreach ($keysByCombination as $keyCombination => $keys) {
                    foreach ($keys as $index => $key) {
                        $callBody[$key] = $dictionaries[$key][$fieldsCombinations[$keyCombination][$i % $lengthsByKeyCombinations[$keyCombination]][$index]];
                    }
                }
                $orderedBody = [];
                foreach ($method->getParameters() as $parameter) {
                    $orderedBody[$parameter->getName()] = $callBody[$parameter->getName()];
                }
                try {
                    $method->invokeArgs($repository, $orderedBody);
                } catch (Exception $exception) {
                } catch (Throwable $exception) {
                    foreach ($callBody as $paramName => $paramValue) {
                        if ($paramValue instanceof Model) {
                            $callBody[$paramName] = $paramValue->toArray();
                        }
                    }
                    $this->fail(
                        "Lanciata eccezione non gestita con il seguente messaggio:"
                        . "{$exception->getMessage()}\ne il seguente body"
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
