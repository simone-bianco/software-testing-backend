<?php

namespace Database\Seeders;

use App\Repositories\VaccineRepository;
use Database\Factories\VaccineFactory;
use Illuminate\Database\Seeder;

class VaccinesSeeder extends Seeder
{
    protected VaccineRepository $vaccineRepository;

    public function __construct(VaccineRepository $vaccineRepository)
    {
        $this->vaccineRepository = $vaccineRepository;
    }

    /**
     * @throws \Throwable
     */
    public function run()
    {
        $this->vaccineRepository->saveOrCreate(
            VaccineFactory::new()->make([
                "name" => "Pfizer",
                "vaccine_doses" => 2,
                "src" => "vaccine_images/pfizer.jpg",
                "lazy_src" => "vaccine_images/pfizer_lazy.jpg",
                "url" => "https://www.pfizer.com/"
            ])
        );

        $this->vaccineRepository->saveOrCreate(
            VaccineFactory::new()->make([
                "name" => "Moderna",
                "vaccine_doses" => 2,
                "src" => "vaccine_images/moderna.jpg",
                "lazy_src" => "vaccine_images/moderna_lazy.jpg",
                "url" => "https://www.modernatx.com/"
            ])
        );

        $this->vaccineRepository->saveOrCreate(
            VaccineFactory::new()->make([
                "name" => "Johnson&Johnson" ,
                "vaccine_doses" => 1,
                "src" => "vaccine_images/johnson.jpg",
                "lazy_src" => "vaccine_images/johnson_lazy.jpg",
                "url" => "https://www.jnj.com/"
            ])
        );

        $this->vaccineRepository->saveOrCreate(
            VaccineFactory::new()->make([
                "name" => "AstraZeneca",
                "vaccine_doses" => 2,
                "src" => "vaccine_images/astrazeneca.jpg",
                "lazy_src" => "vaccine_images/astrazeneca_lazy.jpg",
                "url" => "https://www.astrazeneca.it/"
            ])
        );
    }
}
