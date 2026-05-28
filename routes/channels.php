<?php



use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('App.Models.Admin.{id}', function ($admin, $id) {
    return (int) $admin->id === (int) $id;
});



// Broadcast::channel('App.Models.Client.{id}', function ($client, $id) {
//     return (int) $client->id === (int) $id;
// });