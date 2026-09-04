<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    /**
     * Tampilkan halaman landing page AgroFlow.
     *
     * Jika user sudah login, redirect ke dashboard agar alur
     * auth yang existing tidak terganggu.
     */
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('landing.index');
    }
}
