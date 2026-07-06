<?php

namespace App\Http\Controllers;

use App\Http\Requests\MailingListRequest;
use App\Models\MailingList;
use Illuminate\Http\Request;

class MailingListController extends Controller
{
    public function index()
    {
        $mailingLists = MailingList::withCount('recipients')->orderBy('name')->get();

        return view('mailing-lists.index', compact('mailingLists'));
    }

    public function create()
    {
        return view('mailing-lists.create');
    }

    public function store(MailingListRequest $request)
    {
        $mailingList = MailingList::create($request->validated());

        $this->syncRecipients($request, $mailingList);

        return redirect()->route('mailing-lists.index')->with('success', 'Mailingliste angelegt.');
    }

    public function edit(MailingList $mailingList)
    {
        $mailingList->load('recipients');

        return view('mailing-lists.edit', compact('mailingList'));
    }

    public function update(MailingListRequest $request, MailingList $mailingList)
    {
        $mailingList->update($request->validated());

        $this->syncRecipients($request, $mailingList);

        return redirect()->route('mailing-lists.index')->with('success', 'Mailingliste aktualisiert.');
    }

    public function destroy(MailingList $mailingList)
    {
        $mailingList->delete();

        return redirect()->route('mailing-lists.index')->with('success', 'Mailingliste gelöscht.');
    }

    /**
     * Empfänger werden als Repeater-Felder (recipient_name[]/recipient_email[])
     * im selben Formular gepflegt; bestehende werden ersetzt, da die Liste
     * typischerweise klein ist.
     */
    private function syncRecipients(Request $request, MailingList $mailingList): void
    {
        $mailingList->recipients()->delete();

        $names = $request->input('recipient_name', []);
        $emails = $request->input('recipient_email', []);

        foreach ($emails as $i => $email) {
            if (empty($email)) {
                continue;
            }

            $mailingList->recipients()->create([
                'name' => $names[$i] ?? null,
                'email' => $email,
            ]);
        }
    }
}
