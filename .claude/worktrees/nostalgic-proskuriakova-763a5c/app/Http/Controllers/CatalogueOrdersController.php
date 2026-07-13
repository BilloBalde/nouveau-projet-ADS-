<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Support\Facades\Auth;

class CatalogueOrdersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:catalogue');
    }

    public function index()
    {
        $customerId = Auth::guard('catalogue')->id();
        $orders = CustomerOrder::with(['items.product', 'store'])
            ->where('catalogue_customer_id', $customerId)
            ->latest()
            ->get();

        return view('catalogue.orders.index', compact('orders'));
    }
}

