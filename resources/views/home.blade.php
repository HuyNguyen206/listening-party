<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600|aleo:300,400|annie-use-your-telescope:400&display=swap" rel="stylesheet" />
        @wireUiScripts

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-emerald-50">
    <livewire:layout.navigation/>
    <h1 class="text-center mt-8 text-3xl font-cursive text-slate-800">
        💓 awwdio
    </h1>
        <livewire:dashboard/>
    </body>
</html>
