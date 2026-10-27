<?php

namespace App\Http\Controllers;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        $this->middleware('permission:settings.view');
    }

    public function index()
    {
        return view('mails.index');
    }
}
