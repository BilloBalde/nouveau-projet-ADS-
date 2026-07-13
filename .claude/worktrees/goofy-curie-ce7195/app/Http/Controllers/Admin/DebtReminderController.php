<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DebtReminder;

class DebtReminderController extends Controller
{
    /**
     * Liste des rappels de dettes groupés par client, avec liens WhatsApp.
     */
    public function index()
    {
        $reminders = DebtReminder::with('customer')
            ->where('status', '!=', 'resolved')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($group) {
                $customer = $group->first()->customer;

                return (object) [
                    'customer'      => $customer,
                    'total'         => $group->sum('amount'),
                    'count'         => $group->count(),
                    'max_reminders' => $group->max('reminder_count'),
                    'last_sent_at'  => $group->max('last_sent_at'),
                    'reminder_ids'  => $group->pluck('id'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('admin.debt-reminders.index', compact('reminders'));
    }

    /**
     * Marquer tous les rappels d'un client comme résolus.
     */
    public function resolve(int $customerId)
    {
        DebtReminder::where('customer_id', $customerId)
            ->where('status', '!=', 'resolved')
            ->update(['status' => 'resolved']);

        return back()->with('success', 'Rappels marqués comme résolus.');
    }
}
