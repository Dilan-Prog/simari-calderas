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

    public function industrialMaintenance()
    {
        return view('frontend.pages.services.service-industrial-maintenance');
    }
    public function hydraulicEngineering()
    {
        return view('frontend.pages.services.service-hydraulic-engineering');
    }

    public function hairRepair()
    {
        return view('frontend.pages.services.service-industrial-hair-repair');
    }
    public function equipementCalibration()
    {
        return view('frontend.pages.services.service-calibration');
    }
    public function industrialProject()
    {
        return view('frontend.pages.services.service-industrial-project');
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
