<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }
    
    public function index(){
        return view('mails.index');
    }
}
