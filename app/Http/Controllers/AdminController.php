<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request): View
    {
        $conditions = $request->validated();

        $contacts = Contact::query()
            ->with(['category', 'tags'])
            ->filter($conditions)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(7)
            ->withQueryString();

        return view('admin.index', [
            'categories' => Category::all(),
            'contacts' => $contacts,
            'tags' => Tag::query()
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function show(Contact $contact): View
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', [
            'contact' => $contact,
        ]);
    }
}
