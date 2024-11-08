<!-- resources/views/components/user-notes-section.blade.php -->
@props(['team', 'notes'])

<div class="bg-white shadow-lg rounded-lg p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Your Notes for {{ $team->school }}</h2>

    @if($notes->isEmpty())
        <p class="text-gray-500">No notes found for {{ $team->school }}.</p>
    @else
        <ul class="list-disc pl-5 space-y-2">
            @foreach($notes as $note)
                <li class="text-gray-600">
                    <span class="font-semibold">{{ $note->created_at->format('M d, Y H:i') }}</span>:
                    {{ $note->note }}
                </li>
            @endforeach
        </ul>
    @endif
</div>
