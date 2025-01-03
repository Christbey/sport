@props(['conversations'])

<div id="chat-container" class="p-4 space-y-4 h-[500px] overflow-y-auto overflow-x-scroll bg-gray-50">
    @forelse ($conversations as $conversation)
        <x-chat.message-user :message="$conversation->input"/>
        <x-chat.message-ai :message="$conversation->output"/>
    @empty
        <x-chat.empty-state/>
    @endforelse
    <x-chat.loading-indicator/>
</div>