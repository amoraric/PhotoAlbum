<x-app-layout>
    <x-modal name="create-album" :show="$errors->albumCreation->isNotEmpty()" focusable>
        <form method="post" action="{{ route('album.create') }}" class="p-6">
            @csrf
            @method('post')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Créer un nouvel album') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="title" value="{{ __('Titre') }}" class="sr-only" />

                <x-text-input
                    id="title"
                    name="title"
                    type="text"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Titre') }}"
                />

                <x-input-error :messages="$errors->albumCreation->get('title')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    {{ __('Create Album') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mes Albums') }}
        </h2>
    </x-slot>

    <div class="pt-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex justify-end p-2 text-gray-900 dark:text-gray-100">
                    <x-primary-button 
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'create-album')"
                    >
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current">
                            <title>add</title>
                            <path d="M18.984 12.984h-6v6h-1.969v-6h-6v-1.969h6v-6h1.969v6h6v1.969z"></path>
                        </svg>
                        <span>{{ __('Créer') }}</span>
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="album-collection">
                        @if (count($albums) > 0)
                            @foreach ($albums as $album)
                                @php
                                    $counter = count($album->photos);
                                    $bg = null;

                                    if ($counter > 0) {
                                        $photo = $album->photos()->get()->shuffle()->first();
                                        $explodeFileName = explode('.', $photo->original_name);
                                        $extension = '.' . $explodeFileName[count($explodeFileName) - 1];
                                        $bg = 'background: url("' . asset('uploads/' . $photo->hash . $extension) . '") no-repeat center center';
                                    }
                                @endphp
                                <a href="{{ route('album.read', $album->id) }}" class="album-link" style="{{ $bg }}">
                                    <div>
                                        <p class="album-title">{{ $album->title }}</p>
                                        <p class="album-counter">
                                            {{ $counter }} {{ ($counter > 1) ? 'photos' : 'photo' }}
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        @else 
                            <div class="text-center">
                                {{ __('Vous n\'avez aucun album pour le moment.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
