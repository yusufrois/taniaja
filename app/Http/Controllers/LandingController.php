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

    /**
     * Halaman Tentang Kami.
     */
    public function tentang()
    {
        return view('landing.tentang');
    }

    /**
     * Halaman Blog & Artikel.
     */
    public function blog()
    {
        return view('landing.blog');
    }

    /**
     * Halaman Kontak.
     */
    public function kontak()
    {
        return view('landing.kontak');
    }
}
