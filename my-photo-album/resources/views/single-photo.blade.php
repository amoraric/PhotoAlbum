<x-app-layout>
    @php
    $explodeFileName = explode('.', $photo->original_name);
    $extension = '.' . $explodeFileName[count($explodeFileName) - 1];
    @endphp
    <div>
        <img src="{{ asset('uploads/' . $photo->hash . $extension) }}" alt="{{ $photo->original_name }}" class="bg-cover" />
    </div>
</x-app-layout>