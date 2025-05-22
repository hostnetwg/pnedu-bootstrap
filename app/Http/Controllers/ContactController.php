<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Handle the incoming contact form submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
            'consent' => 'accepted',
        ]);

        Mail::to('kontakt@nowoczesna-edukacja.pl')
            ->send(new ContactFormMail($data));

        return redirect()->route('home')
                         ->with('success', 'Wiadomość została wysłana. Wkrótce się z Tobą skontaktujemy.');
    }
}