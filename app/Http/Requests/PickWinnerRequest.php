<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PickWinnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_ids' => 'required|array',
            'event_ids.*' => 'exists:nfl_team_schedules,espn_event_id',
            'team_ids' => 'required|array',
            'team_ids.*' => 'exists:nfl_teams,id',
        ];
    }
}