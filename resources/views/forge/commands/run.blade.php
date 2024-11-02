<x-app-layout>
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">Run Command for Site {{ $siteId }} on Server {{ $serverId }}</h1>

        <!-- Status Notification -->
        <div id="status-message" class="p-4 mb-4 text-sm bg-yellow-100 text-yellow-700 rounded-lg" role="alert">
            Command is in progress or waiting.
        </div>

        <!-- Command Details Table -->
        <div class="overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 bg-white rounded-md shadow-md">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="py-3 px-6">Command</th>
                    <th scope="col" class="py-3 px-6">Status</th>
                    <th scope="col" class="py-3 px-6">Started At</th>
                    <th scope="col" class="py-3 px-6">Completed At</th>
                </tr>
                </thead>
                <tbody>
                <tr class="border-b">
                    <td class="py-4 px-6" id="command-text">{{ $commandText }}</td>
                    <td class="py-4 px-6">
                        <span id="command-status"
                              class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                            Unknown
                        </span>
                    </td>
                    <td class="py-4 px-6" id="started-at">N/A</td>
                    <td class="py-4 px-6" id="completed-at">N/A</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="{{ route('forge.sites.index', $serverId) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Back to Sites
            </a>
        </div>
    </div>

    <!-- Polling Script to Check Command Status -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const commandId = "{{ $commandId }}";  // Command ID passed to JavaScript
            const serverId = "{{ $serverId }}";
            const siteId = "{{ $siteId }}";

            function fetchCommandStatus() {
                fetch(`/api/forge/servers/${serverId}/sites/${siteId}/commands/${commandId}`)
                    .then(response => response.json())
                    .then(data => {
                        const commandData = data.command || {};
                        const status = commandData.status || 'Unknown';
                        const createdAt = commandData.created_at || 'N/A';
                        const updatedAt = commandData.updated_at || 'N/A';
                        const commandText = commandData.command || 'N/A';

                        // Update the text fields with the latest data
                        document.getElementById('command-text').textContent = commandText;
                        document.getElementById('command-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        document.getElementById('started-at').textContent = createdAt;
                        document.getElementById('completed-at').textContent = (status === 'finished' || status === 'failed') ? updatedAt : 'N/A';

                        // Update the status message based on the command status
                        const statusMessage = document.getElementById('status-message');
                        const commandStatus = document.getElementById('command-status');
                        if (status === 'finished') {
                            statusMessage.textContent = 'Command executed successfully.';
                            statusMessage.className = 'p-4 mb-4 text-sm bg-green-100 text-green-700 rounded-lg';
                            commandStatus.className = 'inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800';
                        } else if (status === 'failed') {
                            statusMessage.textContent = 'Command execution failed.';
                            statusMessage.className = 'p-4 mb-4 text-sm bg-red-100 text-red-700 rounded-lg';
                            commandStatus.className = 'inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800';
                        } else {
                            statusMessage.textContent = 'Command is in progress or waiting.';
                            statusMessage.className = 'p-4 mb-4 text-sm bg-yellow-100 text-yellow-700 rounded-lg';
                            commandStatus.className = 'inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800';
                        }
                    })
                    .catch(error => console.error('Error fetching command status:', error));
            }

            // Poll every 5 seconds to check command status
            setInterval(fetchCommandStatus, 5000);
        });
    </script>
</x-app-layout>
