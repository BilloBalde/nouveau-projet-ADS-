<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use App\Models\Company;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|min:10',
        ], [
            'name.required' => 'Votre nom est requis.',
            'email.required' => 'Votre email est requis.',
            'email.email' => 'Veuillez entrer un email valide.',
            'subject.required' => 'Le sujet est requis.',
            'message.required' => 'Le message est requis.',
            'message.min' => 'Le message doit contenir au moins 10 caractères.',
        ]);

        $message = ContactMessage::create($request->only(['name', 'email', 'phone', 'subject', 'message']));

        // Le message est conservé en base et consultable dans Boutique en ligne >
        // Messages reçus. On prévient en plus par courriel : sans cela, personne
        // ne sait qu'un client a écrit, et l'écran n'est consulté que par hasard.
        $destinataire = Company::current()?->email ?: config('mail.from.address');

        if ($destinataire) {
            try {
                Mail::to($destinataire)->send(new ContactMail(
                    $request->subject,
                    $request->email,
                    $request->name,
                    $request->message
                ));
            } catch (\Throwable $e) {
                // Une panne SMTP ne doit pas faire croire au visiteur que son
                // message est perdu : il est déjà enregistré.
                Log::warning('Notification de contact non envoyée : '.$e->getMessage(), [
                    'message_id' => $message->id,
                ]);
            }
        }

        return redirect()->route('contact')->with('success',
            'Votre message a bien été reçu ! Nous vous répondrons sous 24h.');
    }

    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);

        return view('admin.contact-messages', compact('messages'));
    }

    public function markRead($id)
    {
        ContactMessage::findOrFail($id)->update(['is_read' => true]);

        return back()->with('success', 'Message marqué comme lu.');
    }

    public function destroy($id)
    {
        ContactMessage::findOrFail($id)->delete();

        return back()->with('success', 'Message supprimé.');
    }
}
