<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// In routes/channels.php
Broadcast::channel('quotes', function ($user) {
    return true; // Allow all authenticated users to access this channel
});

// Private quotes channel with token in request
Broadcast::channel('private_quotes', function ($user) {
    $token = request()->input('token');
    return ($token == 'secret'); // allow all secret members access :-)
});