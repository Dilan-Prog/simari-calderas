<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

class EmailMarketingController extends Controller
{
    public function index()
    {
        return view('admin.email-marketing.index');
    }
}
