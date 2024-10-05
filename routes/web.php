<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
\Livewire\Volt\Volt::route('parties/{listeningParty}', 'pages.parties.show')->name('parties.show');

require __DIR__.'/auth.php';
