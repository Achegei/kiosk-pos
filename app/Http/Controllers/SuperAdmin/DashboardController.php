<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Tenant;
use App\Models\Device;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function index()
    {
        return view('superadmin.dashboard');
    }
}