<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// In routes/channels.php
Broadcast::channel('quotes', function ($user) {
    return true; // Allow all authenticated users to access this channel
});