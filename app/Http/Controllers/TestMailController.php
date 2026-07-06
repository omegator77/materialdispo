<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TestMailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Mail::to($request->email)->send(new TestMail($request->user()));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Versand fehlgeschlagen: '.$e->getMessage());
        }

        return redirect()->back()->with('success', "Test-E-Mail an {$request->email} verschickt.");
    }
}
