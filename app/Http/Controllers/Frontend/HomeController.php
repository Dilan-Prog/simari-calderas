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




    // SERVICES PAGES
    public function storageTanks()
    {
        return view('frontend.pages.services.storage-tanks');
    }   




    // LEGAL PAGES
    public function privacyNotice()
    {
        return view('frontend.pages.legal.privacy-notice');
    }

    public function termsOfService()
    {
        return view('frontend.pages.legal.termsconditions');
    }
}
