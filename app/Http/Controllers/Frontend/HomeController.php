<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function index()
    {
        return view('frontend.home.home');
        //return view('frontend.pages.masstercal-rinnai.heat-pumps');
        //return view('frontend.pages.services.Industrial-Hair-Dryer-Repair');
    }
}
