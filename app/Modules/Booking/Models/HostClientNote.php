<?php

namespace App\Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;

/** A guide's private note about one client. Only the guide who wrote it can ever read it. */
class HostClientNote extends Model
{
    protected $fillable = ['host_id', 'tourist_id', 'body'];
}
