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
    }




    // SERVICES PAGES
    public function hairRepair()
    {
        return view('frontend.pages.services.industrial-hair-repair');
    }


    // Masstercal Rinnai
    public function rinnaiHeatPumps()
    {
        return view('frontend.pages.masstercal-rinnai.heat-pumps');
    }
    public function storageTanks()
    {
        return view('frontend.pages.masstercal-rinnai.storage-tanks');
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
