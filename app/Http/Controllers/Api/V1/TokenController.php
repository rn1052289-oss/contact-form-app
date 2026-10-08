<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

final class TokenController extends Controller
{
    public function store(StoreTokenRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'token' => $user->createToken('contact-api')->plainTextToken,
            'token_type' => 'Bearer',
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // セッション認証では失効対象の永続トークンが存在しない。
        abort_unless($token instanceof PersonalAccessToken, Response::HTTP_UNAUTHORIZED);

        $token->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
