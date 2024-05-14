<x-app-layout>
    <!-- Add photos modal -->
    <x-modal name="add-photos" :show="$errors->photoAdd->isNotEmpty()" focusable>
        <form method="post" action="{{ route('photos.add', $album->id) }}" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('post')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Ajouter des photos') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="photos" value="{{ __('Photos') }}" class="sr-only" />

                <input
                    type="file"
                    id="photos"
                    name="photos[]"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Sélectionner mes photos') }}"
                    accept="image/jpeg, image/png, image/gif, image/bmp, image/webp, image/svg+xml, image/x-icon"
                    multiple
                    webkitdirectory directory
                />

                <x-input-error :messages="$errors->photoAdd->get('photos')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    {{ __('Add Photos') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Update album title -->
    <x-modal name="update-album" :show="$errors->albumUpdating->isNotEmpty()" focusable>
        <form method="post" action="{{ route('album.update', $album->id) }}" class="p-6">
            @csrf
            @method('patch')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Changer le titre') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="title" value="{{ __('Titre') }}" class="sr-only" />

                <x-text-input
                    id="title"
                    name="title"
                    type="text"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Titre') }}"
                    value="{{ $album->title }}"
                />

                <x-input-error :messages="$errors->albumUpdating->get('title')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    {{ __('Update Album') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Delete album modal -->
    <x-modal name="confirm-album-deletion" :show="$errors->albumDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('album.delete', $album->id) }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Are you sure you want to delete album "' . $album->title . '" ?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Once your album is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your album.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->albumDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Album') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __($album->title) . ' (' . count($photos) . ')' }}
        </h2>
    </x-slot>

    <div class="pt-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="album-actions-group flex justify-end p-2 text-gray-900 dark:text-gray-100">
                    <x-primary-button
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'add-photos')"
                    >
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current">
                            <title>add</title>
                            <path d="M18.984 12.984h-6v6h-1.969v-6h-6v-1.969h6v-6h1.969v6h6v1.969z"></path>
                        </svg>
                        <span class="hidden sm:block">{{ __('Ajouter') }}</span>
                    </x-primary-button>
                    <x-secondary-button
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'update-album')"
                    >
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current">
                            <title>edit</title>
                            <path d="M20.719 7.031l-1.828 1.828-3.75-3.75 1.828-1.828q0.281-0.281 0.703-0.281t0.703 0.281l2.344 2.344q0.281 0.281 0.281 0.703t-0.281 0.703zM3 17.25l11.063-11.063 3.75 3.75-11.063 11.063h-3.75v-3.75z"></path>
                        </svg>
                        <span class="hidden sm:block">{{ __('Changer le titre') }}</span>
                    </x-secondary-button>
                    <x-danger-button
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-album-deletion')"
                    >
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current">
                            <title>delete</title>
                            <path d="M15.516 3.984h3.469v2.016h-13.969v-2.016h3.469l1.031-0.984h4.969zM8.438 11.859l2.156 2.156-2.109 2.109 1.406 1.406 2.109-2.109 2.109 2.109 1.406-1.406-2.109-2.109 2.109-2.156-1.406-1.406-2.109 2.156-2.109-2.156zM6 18.984v-12h12v12q0 0.797-0.609 1.406t-1.406 0.609h-7.969q-0.797 0-1.406-0.609t-0.609-1.406z"></path>
                        </svg>
                        <span class="hidden sm:block">{{ __('Supprimer') }}</span>
                    </x-danger-button>
                </div>
            </div>
        </div>
    </div>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="photo-collection">
                        @if (count($photos) > 0)
                            @foreach ($photos as $photo)
                                @php
                                    $explodeFileName = explode('.', $photo->original_name);
                                    $extension = '.' . $explodeFileName[count($explodeFileName) - 1];
                                    $bg = 'background: url("' . asset('uploads/' . $photo->hash . $extension) . '") no-repeat center center';
                                @endphp
                                <!-- Delete photo modal -->
                                <x-modal name="confirm-photo-{{ $photo->id }}-deletion" :show="$errors->photoDeletion->isNotEmpty()" focusable>
                                    <form method="post" action="{{ route('photo.delete', $album->id) }}" class="p-6">
                                        @csrf
                                        @method('delete')

                                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            {{ __('Are you sure you want to delete photo "' . $photo->original_name . '" ?') }}
                                        </h2>

                                        <div class="mt-6">

                                            <x-text-input
                                                id="photo"
                                                name="photo"
                                                type="hidden"
                                                class="mt-1 block w-3/4"
                                                value="{{ $photo->id }}"
                                            />

                                            <x-input-error :messages="$errors->photoDeletion->get('photo')" class="mt-2" />
                                        </div>

                                        <div class="mt-6 flex justify-end">
                                            <x-secondary-button x-on:click="$dispatch('close')">
                                                {{ __('Cancel') }}
                                            </x-secondary-button>

                                            <x-danger-button class="ms-3">
                                                {{ __('Delete Photo') }}
                                            </x-danger-button>
                                        </div>
                                    </form>
                                </x-modal>

                                <div class="photo-link overflow-hidden">
                                    <div style="{{ $bg }}">
                                        <div class="buttons-group flex flex-col items-center justify-between w-20 h-full overflow-hidden p-2">
                                            <div class="grid">
                                                <x-photo-button href="{{ route('photo.show', ['photo' => $photo->id]) }}" target="_blank">
                                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current">
                                                        <title>open</title>
                                                        <path d="M21 11.016v-8.016h-8.016l3.328 3.281-10.031 10.031-3.281-3.328v8.016h8.016l-3.328-3.281 10.031-10.031z"></path>
                                                    </svg>
                                                </x-photo-button>
                                                @if ($photo->shared_at === null)
                                                    <x-photo-button href="{{ route('photo.share', ['album' => $album->id, 'photo' => $photo->id]) }}">
                                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                            <title>share</title>
                                                            <path d="M18 16.078q1.219 0 2.063 0.867t0.844 2.039q0 1.219-0.867 2.086t-2.039 0.867-2.039-0.867-0.867-2.086q0-0.469 0.047-0.656l-7.078-4.125q-0.891 0.797-2.063 0.797-1.219 0-2.109-0.891t-0.891-2.109 0.891-2.109 2.109-0.891q1.172 0 2.063 0.797l7.031-4.078q-0.094-0.469-0.094-0.703 0-1.219 0.891-2.109t2.109-0.891 2.109 0.891 0.891 2.109-0.891 2.109-2.109 0.891q-1.125 0-2.063-0.844l-7.031 4.125q0.094 0.469 0.094 0.703t-0.094 0.703l7.125 4.125q0.844-0.75 1.969-0.75z"></path>
                                                        </svg>
                                                    </x-photo-button>
                                                @else
                                                    <x-photo-button href="{{ route('photo.unshare', ['album' => $album->id, 'photo' => $photo->id]) }}">
                                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28">
                                                            <title>unshare</title>
                                                            <path d="M8.672 20.859l1.219-2.203c-1.813-1.313-2.891-3.422-2.891-5.656 0-1.234 0.328-2.453 0.953-3.516-2.438 1.25-4.469 3.219-5.953 5.516 1.625 2.516 3.922 4.625 6.672 5.859zM14.75 9c0-0.406-0.344-0.75-0.75-0.75-2.609 0-4.75 2.141-4.75 4.75 0 0.406 0.344 0.75 0.75 0.75s0.75-0.344 0.75-0.75c0-1.797 1.469-3.25 3.25-3.25 0.406 0 0.75-0.344 0.75-0.75zM20.422 6.016c0 0.031 0 0.109-0.016 0.141-3.297 5.891-6.563 11.813-9.859 17.703l-0.766 1.391c-0.094 0.156-0.266 0.25-0.438 0.25-0.281 0-1.766-0.906-2.094-1.094-0.156-0.094-0.25-0.25-0.25-0.438 0-0.25 0.531-1.094 0.688-1.359-3.031-1.375-5.578-3.719-7.375-6.531-0.203-0.313-0.313-0.688-0.313-1.078 0-0.375 0.109-0.766 0.313-1.078 3.094-4.75 7.922-7.922 13.687-7.922 0.938 0 1.891 0.094 2.812 0.266l0.844-1.516c0.094-0.156 0.25-0.25 0.438-0.25 0.281 0 1.75 0.906 2.078 1.094 0.156 0.094 0.25 0.25 0.25 0.422zM21 13c0 2.906-1.797 5.5-4.5 6.531l4.375-7.844c0.078 0.438 0.125 0.875 0.125 1.313zM28 15c0 0.406-0.109 0.734-0.313 1.078-0.484 0.797-1.094 1.563-1.703 2.266-3.063 3.516-7.281 5.656-11.984 5.656l1.156-2.063c4.547-0.391 8.406-3.156 10.844-6.937-1.156-1.797-2.641-3.375-4.406-4.594l0.984-1.75c1.937 1.297 3.891 3.25 5.109 5.266 0.203 0.344 0.313 0.672 0.313 1.078z"></path>
                                                        </svg>
                                                    </x-photo-button>
                                                @endif
                                            </div>
                                            <div class="grid">
                                                <x-photo-danger-button
                                                    x-data=""
                                                    x-on:click.prevent="$dispatch('open-modal', 'confirm-photo-{{ $photo->id }}-deletion')"
                                                >
                                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current">
                                                        <title>delete</title>
                                                        <path d="M15.516 3.984h3.469v2.016h-13.969v-2.016h3.469l1.031-0.984h4.969zM8.438 11.859l2.156 2.156-2.109 2.109 1.406 1.406 2.109-2.109 2.109 2.109 1.406-1.406-2.109-2.109 2.109-2.156-1.406-1.406-2.109 2.156-2.109-2.156zM6 18.984v-12h12v12q0 0.797-0.609 1.406t-1.406 0.609h-7.969q-0.797 0-1.406-0.609t-0.609-1.406z"></path>
                                                    </svg>
                                                </x-photo-danger-button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center">
                                {{ __('Vous n\'avez aucune photo dans cet album.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
