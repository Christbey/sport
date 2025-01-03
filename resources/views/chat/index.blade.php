<x-app-layout>
    <div class="container mx-auto px-4 py-8 flex-grow">
        <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
            <x-chat.header/>
            <x-chat.rate-limit-info
                    :remainingRequests="$remainingRequests"
                    :userLimit="$userLimit"
                    :daysUntilReset="$daysUntilReset"
                    :hoursUntilReset="$hoursUntilReset"
            />
            <x-chat.conversation-list :conversations="$conversations"/>
            <x-chat.input-form :remainingRequests="$remainingRequests"/>
        </div>
    </div>
    <x-chat.styles/>
</x-app-layout>