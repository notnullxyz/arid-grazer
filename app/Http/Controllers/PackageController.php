<?php

namespace App\Http\Controllers;
use Log;

class PackageController extends Controller
{

    public function __construct()
    {
        Log::info('PackageController construction.');
    }

}