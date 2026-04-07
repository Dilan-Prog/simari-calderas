<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EmployeController extends Controller
{
    public function dashboard(): View
    {
        return view('employe.dashboard');
    }
}
