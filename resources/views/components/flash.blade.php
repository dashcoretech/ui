@props(['status' => session('status')])

@php($bag = $errors ?? null)

{{-- Muted semantic colour on its -bg tint, with a 2px rule on the left rather
     than a filled box — the same treatment as a status tag, at page width. --}}
@if ($status)
    <div class="mb-6 border-l-2 border-dc-success bg-dc-success-bg px-4 py-3 text-sm text-dc-ink" role="status" data-dc-flash="status">
        {{ $status }}
    </div>
@endif

@if ($bag && $bag->any())
    <div class="mb-6 border-l-2 border-dc-danger bg-dc-danger-bg px-4 py-3 text-sm text-dc-ink" role="alert" data-dc-flash="errors">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
