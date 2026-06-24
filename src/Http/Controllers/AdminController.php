<?php

namespace LaraBucket\Http\Controllers;

use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    /**
     * Display the LaraBucket Admin Dashboard view.
     */
    public function dashboard()
    {
        return view('larabucket::dashboard');
    }
}
