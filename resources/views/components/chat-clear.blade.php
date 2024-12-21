<div
        id="modalContainer"
        class="fixed inset-0 z-50 hidden"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
>
    <!-- Background Backdrop -->
    <div
            id="modalBackdrop"
            class="fixed inset-0 bg-black/50"
    ></div>

    <!-- Modal Panel -->
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 w-full sm:max-w-lg text-left shadow-xl transition-all">
            <div class="p-6">
                <h3 id="modal-title" class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Clear Chat History
                </h3>
                <p class="text-gray-700 dark:text-gray-300 mb-6">
                    Are you sure you want to clear all chat messages? This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-4">
                    <button
                            id="cancelClear"
                            type="button"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                    >
                        Cancel
                    </button>
                    <button
                            id="confirmClear"
                            type="button"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                    >
                        Clear History
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
