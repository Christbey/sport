<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Drive Success Metrics</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Scoring Probability</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $homeTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $driveMetrics['scoring_drive_probability']['home'] > $driveMetrics['scoring_drive_probability']['away'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($driveMetrics['scoring_drive_probability']['home'] * 100, 1) }}%
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $awayTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $driveMetrics['scoring_drive_probability']['away'] > $driveMetrics['scoring_drive_probability']['home'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($driveMetrics['scoring_drive_probability']['away'] * 100, 1) }}%
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ ($driveMetrics['scoring_drive_probability']['home'] - $driveMetrics['scoring_drive_probability']['away']) > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(($driveMetrics['scoring_drive_probability']['home'] - $driveMetrics['scoring_drive_probability']['away']) * 100, 1) }}
                        %
                    </td>
                </tr>
            </table>
        </div>
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Explosive Drive Probability</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $homeTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $driveMetrics['explosive_drive_probability']['home'] > $driveMetrics['explosive_drive_probability']['away'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($driveMetrics['explosive_drive_probability']['home'] * 100, 1) }}%
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $awayTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $driveMetrics['explosive_drive_probability']['away'] > $driveMetrics['explosive_drive_probability']['home'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($driveMetrics['explosive_drive_probability']['away'] * 100, 1) }}%
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ ($driveMetrics['explosive_drive_probability']['home'] - $driveMetrics['explosive_drive_probability']['away']) > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(($driveMetrics['explosive_drive_probability']['home'] - $driveMetrics['explosive_drive_probability']['away']) * 100, 1) }}
                        %
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-span-2">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Red Zone Efficiency</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $homeTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $driveMetrics['red_zone_efficiency']['home'] > $driveMetrics['red_zone_efficiency']['away'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($driveMetrics['red_zone_efficiency']['home'] * 100, 1) }}%
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $awayTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $driveMetrics['red_zone_efficiency']['away'] > $driveMetrics['red_zone_efficiency']['home'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($driveMetrics['red_zone_efficiency']['away'] * 100, 1) }}%
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ ($driveMetrics['red_zone_efficiency']['home'] - $driveMetrics['red_zone_efficiency']['away']) > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(($driveMetrics['red_zone_efficiency']['home'] - $driveMetrics['red_zone_efficiency']['away']) * 100, 1) }}
                        %
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="text-sm text-gray-600">
            <p class="mb-2"><span class="font-medium">Scoring Drive:</span> Probability of a drive resulting in points
            </p>
            <p class="mb-2"><span class="font-medium">Explosive Drive:</span> Likelihood of generating plays of 20+
                yards</p>
            <p class="mb-2"><span class="font-medium">Red Zone:</span> Efficiency in converting red zone opportunities
            </p>
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="font-medium text-gray-700">Advantages:</p>
                <p class="mb-1">Scoring Drives: <span
                            class="font-medium {{ $driveMetrics['scoring_drive_probability']['home'] > $driveMetrics['scoring_drive_probability']['away'] ? 'text-green-600' : 'text-blue-600' }}">
                {{ $driveMetrics['scoring_drive_probability']['home'] > $driveMetrics['scoring_drive_probability']['away'] ? $homeTeam->school : $awayTeam->school }}
            </span></p>
                <p class="mb-1">Explosive Plays: <span
                            class="font-medium {{ $driveMetrics['explosive_drive_probability']['home'] > $driveMetrics['explosive_drive_probability']['away'] ? 'text-green-600' : 'text-blue-600' }}">
                {{ $driveMetrics['explosive_drive_probability']['home'] > $driveMetrics['explosive_drive_probability']['away'] ? $homeTeam->school : $awayTeam->school }}
            </span></p>
                <p>Red Zone: <span
                            class="font-medium {{ $driveMetrics['red_zone_efficiency']['home'] > $driveMetrics['red_zone_efficiency']['away'] ? 'text-green-600' : 'text-blue-600' }}">
                {{ $driveMetrics['red_zone_efficiency']['home'] > $driveMetrics['red_zone_efficiency']['away'] ? $homeTeam->school : $awayTeam->school }}
            </span></p>
            </div>
        </div>
    </div>
</div>