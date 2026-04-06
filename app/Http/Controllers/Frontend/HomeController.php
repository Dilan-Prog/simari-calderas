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
    public function company()
    {
        return view('frontend.pages.company.company');
    }
    public function contact()
    {
        return view('frontend.pages.contact');
    }





    // TEST CREATE ADMIN PAGE
    public function users(){
        return view('admin.users.user-manager');
    }

    public function clients(){
        return view('admin.managers.clients-manager');
    }

    public function supliers(){
        return view('admin.managers.supliers-manager');
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

    public function equipementCalibration()
    {
        return view('frontend.pages.services.service-calibration');
    }
    public function waterTreatment()
    {
        return view('frontend.pages.services.service-water-treatment');
    }
    public function automation()
    {
        return view('frontend.pages.services.service-automation');
    }
    public function chillerMaintenance()
    {
        return view('frontend.pages.services.service-chillers-maintenance');
    }
    public function descaleBoilers()
    {
        return view('frontend.pages.services.service-descale-boilers');
    }
    public function industrialProject()
    {
        return view('frontend.pages.services.service-industrial-project');
    }
      public function hairRepair()
    {
        return view('frontend.pages.services.service-industrial-hair-repair');
    }



    // Products
    public function simariBoilers()
    {
        return view('frontend.pages.products.products-simari-boilers');
    }
    public function industrialInstrumentation()
    {
        return view('frontend.pages.products.products-industrial-instrumentation');
    }
    public function waterTreatmentAnti()
    {
        return view('frontend.pages.products.products-water-treatment-anti');
    }
    public function spareParts()
    {
        return view('frontend.pages.products.products-spare-parts');
    }
    public function solarHeaters()
    {
        return view('frontend.pages.products.products-simari-solar-heaters');
    }



    // Masstercal Rinnai
    public function rinnaiHeatPumps()
    {
        return view('frontend.pages.masstercal-rinnai.heat-pumps');
    }
    public function waterHeaters()
    {
        return view('frontend.pages.masstercal-rinnai.water-heaters');
    }
    public function electricHeaters()
    {
        return view('frontend.pages.masstercal-rinnai.electric-heaters');
    }
    public function tanklessHeaters()
    {
        return view('frontend.pages.masstercal-rinnai.tankless-heaters');
    }
    public function softenersFilters()
    {
        return view('frontend.pages.masstercal-rinnai.softeners-filters');
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
