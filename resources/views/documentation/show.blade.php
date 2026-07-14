<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Anleitung
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-sm p-8 markdown-body">
                {!! $html !!}
            </div>
        </div>
    </div>

    <style>
        .markdown-body h1 { font-size: 1.75rem; font-weight: 700; color: #111827; margin-bottom: 1rem; }
        .markdown-body h2 { font-size: 1.35rem; font-weight: 600; color: #111827; margin-top: 2rem; margin-bottom: 0.75rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem; }
        .markdown-body h2:first-child { border-top: 0; margin-top: 0; padding-top: 0; }
        .markdown-body h3 { font-size: 1.1rem; font-weight: 600; color: #111827; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .markdown-body p { color: #374151; margin-bottom: 0.9rem; line-height: 1.6; }
        .markdown-body ul, .markdown-body ol { color: #374151; margin: 0.5rem 0 1rem 1.4rem; line-height: 1.6; }
        .markdown-body ul { list-style: disc; }
        .markdown-body ol { list-style: decimal; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #111827; font-weight: 600; }
        .markdown-body code { background: #f3f4f6; padding: 0.1rem 0.35rem; border-radius: 0.25rem; font-size: 0.85em; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.9rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #e5e7eb; padding: 0.5rem 0.75rem; text-align: left; }
        .markdown-body th { background: #f9fafb; font-weight: 600; }
        .markdown-body a { color: #ea580c; text-decoration: underline; }
    </style>
</x-app-layout>
