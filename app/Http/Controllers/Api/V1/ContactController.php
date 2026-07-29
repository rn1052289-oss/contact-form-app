<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    /**
     * お問い合わせ一覧を取得する
     */
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
}
