<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegisteredUserController extends Controller
{
    public function create()
    {
        abort(403, 'New users can only be created by an administrator.');
    }

    public function store(Request $request)
    {
        abort(403, 'New users can only be created by an administrator.');
    }
}
