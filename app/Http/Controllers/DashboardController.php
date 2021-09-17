<?php

namespace App\Http\Controllers;

use App\Exceptions\ResponsibleNotFoundException;
use App\Repositories\ResponsibleRepository;
use Auth;
use Inertia\Inertia;
use Inertia\Response;
use Request;

class DashboardController extends Controller
{
    protected ResponsibleRepository $responsibleRepository;

    /**
     * DashboardController constructor.
     * @param  ResponsibleRepository  $responsibleRepository
     */
    public function __construct(
        ResponsibleRepository $responsibleRepository
    ) {
        $this->responsibleRepository = $responsibleRepository;
    }

    /**
     * @param  Request  $request
     * @return Response
     * @throws ResponsibleNotFoundException
     */
    public function index(Request $request): Response
    {
        $currentOperator = $this->responsibleRepository->get(Auth::user()->email);

        return Inertia::render("Dashboard", [
            'vaccinesQty' => $currentOperator->structure->getVaccinesWithQty(),
            'structure' => $currentOperator->structure
        ]);
    }
}
