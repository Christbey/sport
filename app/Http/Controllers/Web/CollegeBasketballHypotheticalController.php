<?php

// App/Http/Controllers/Web/CollegeBasketballHypotheticalController.php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\CollegeBasketballHypotheticalController as ApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListCollegeBasketballRequest;
use App\Models\CollegeBasketballHypothetical;
use Illuminate\View\View;

class CollegeBasketballHypotheticalController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    public function index(ListCollegeBasketballRequest $request): View
    {
        $response = $this->apiController->index($request);
        $data = $response->additional;

        return view('cbb.index', [
            'hypotheticals' => $response->resource,
            'dates' => $data['meta']['available_dates'],
            'selectedDate' => $request->game_date
        ]);
    }

    public function show(string $id): View
    {
        $hypothetical = $this->apiController->show(
            CollegeBasketballHypothetical::findOrFail($id)
        );

        return view('cbb.show', [
            'hypothetical' => $hypothetical->getData()->data
        ]);
    }
}

