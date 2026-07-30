<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
    public function index(IndexContactRequest $request): JsonResponse
    {
        $conditions = $request->validated();
        $perPage = $conditions['per_page'] ?? 20;

        $contacts = Contact::query()
            ->with(['category', 'tags'])
            ->filterForApi($conditions)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => ContactResource::collection(
                $contacts->getCollection()
            )->resolve($request),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];

        unset($validated['tag_ids']);

        $contact = DB::transaction(function () use ($validated, $tagIds): Contact {
            $contact = Contact::create($validated);

            if ($tagIds !== []) {
                $contact->tags()->attach($tagIds);
            }

            return $contact;
        });

        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Contact $contact): ContactResource
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }
}
