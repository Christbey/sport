@component('mail::message')
    # Your Picks for {{ $gameWeek }}

    Hi {{ $user->name }},

    Thank you for submitting your picks for **{{ $gameWeek }}**. Here are your selections:

    @component('mail::table')
        | Game           | Your Pick       |
        | -------------- | --------------- |
        @foreach($picks as $pick)
            | {{ $pick['game'] }} | {{ $pick['team_name'] }} |
        @endforeach
    @endcomponent

    You can review or update your picks before the games start.

    Thanks,
    {{ config('app.name') }}
@endcomponent
