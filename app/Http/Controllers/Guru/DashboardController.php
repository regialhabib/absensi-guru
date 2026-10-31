<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Placeholder page from the starter kit; wave 2 replaces it with the real guru dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard');
    }
}
