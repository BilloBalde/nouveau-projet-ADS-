<?php

namespace App\Http\Controllers;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index()
    {
        return view('mails.index');
    }
}
