<?php

namespace App\Exceptions;

use App\Models\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $exception) {
            //
        });

        $this->renderable(function (NotFoundHttpException $exception, Request $request) {
            $previousException = $exception->getPrevious();

            if (
                $request->is('api/v1/contacts/*')
                && $previousException instanceof ModelNotFoundException
                && $previousException->getModel() === Contact::class
            ) {
                return response()->json([
                    'error' => 'お問い合わせが見つかりませんでした。',
                ], Response::HTTP_NOT_FOUND);
            }

            return null;
        });
    }
}
