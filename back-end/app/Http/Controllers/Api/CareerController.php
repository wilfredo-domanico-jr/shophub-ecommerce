<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobOpening;

class CareerController extends Controller
{
    public function index()
    {
        return JobOpening::where('is_active', true)
            ->latest()
            ->get();
    }
}
