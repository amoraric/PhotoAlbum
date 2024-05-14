<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- CSS -->
        @vite(['resources/css/gallery.css', 'resources/js/gallery.js'])
    </head>
    <body>
        <h1 hidden>Collective Gallery</h1>

        <p class="tooltip" x-data x-show="$store.tooltip.isVisible" x-text="$store.tooltip.text" x-transition></p>



        <div id="gallery"></div>

        <div id="list" x-data="$store.gallery.list" x-init="$store.gallery.getList()"></div>
    </body>
</html>
