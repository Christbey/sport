<style>
    .loading-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .prose pre {
        background-color: #f8fafc;
        border-radius: 0.5rem;
        padding: 1rem;
        margin: 1rem 0;
        overflow-x: auto;
    }

    .prose code {
        background-color: #f1f5f9;
        padding: 0.2em 0.4em;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }
</style>