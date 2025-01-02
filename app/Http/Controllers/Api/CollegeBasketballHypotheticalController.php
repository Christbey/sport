<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListCollegeBasketballRequest;
use App\Http\Resources\CollegeBasketballCollection;
use App\Http\Resources\CollegeBasketballResource;
use App\Models\CollegeBasketballHypothetical;
use Illuminate\Http\Request;

class CollegeBasketballHypotheticalController extends Controller
{
    /**
     * List college basketball hypotheticals with optional date filtering
     */
    public function index(ListCollegeBasketballRequest $request)
    {
        $query = CollegeBasketballHypothetical::query();

        if ($request->filled('game_date')) {
            $query->where('game_date', $request->game_date);
        }

        // Apply role-based limit
        $perPage = min($request->per_page ?? 15, $this->getResultLimit());
        $hypotheticals = $query->orderBy('game_date')->paginate($perPage);

        // Get distinct dates with the full date object for the view
        $dates = CollegeBasketballHypothetical::select('game_date')
            ->distinct()
            ->orderBy('game_date')
            ->get();

        if ($request->wantsJson()) {
            return (new CollegeBasketballCollection($hypotheticals))
                ->additional([
                    'meta' => [
                        'available_dates' => $dates->pluck('game_date'),
                        'result_limit' => $this->getResultLimit(),
                        'current_page_size' => $perPage
                    ]
                ]);
        }

        return view('cbb.index', [
            'hypotheticals' => $hypotheticals,
            'dates' => $dates,
            'selectedDate' => $request->game_date,
            'resultLimit' => $this->getResultLimit()
        ]);
    }

    /**
     * Get the result limit based on user role
     */
    private function getResultLimit(): int
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return 1000;
        } elseif ($user->hasRole(['pro_subscriber', 'pro_user'])) {
            return 25;
        } elseif ($user->hasRole(['basic_subscriber', 'basic_user'])) {
            return 3;
        } else {
            return 3;
        }
    }

    /**
     * Get a specific basketball hypothetical
     */
    public function show(Request $request, CollegeBasketballHypothetical $hypothetical)
    {
        if ($request->wantsJson()) {
            return new CollegeBasketballResource($hypothetical);
        }

        return view('cbb.show', compact('hypothetical'));
    }
}