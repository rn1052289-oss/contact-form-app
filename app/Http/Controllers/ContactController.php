<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class ContactController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $category = Category::findOrFail(
            $validated['category_id']
        );

        $tags = Tag::whereIn(
            'id',
            $validated['tag_ids'] ?? []
        )->get();

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request)
    {
        if ($request->input('action') === 'back') {
            return redirect()
                ->route('contact.index')
                ->withInput();
        }

        $validated = $request->validated();

        $contactData = $validated;
        unset($contactData['tag_ids']);

        $contact = Contact::create($contactData);

        if (! empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }

        return redirect()->route('contact.thanks');
    }
}
