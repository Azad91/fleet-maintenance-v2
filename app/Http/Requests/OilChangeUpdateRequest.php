<?php

namespace App\Http\Requests;

class OilChangeUpdateRequest extends OilChangeStoreRequest
{
    // Same rules; bus_id is still required (allows re-assigning to a
    // different bus when correcting a mistake).
}
