<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
        $this->reportable(function (Throwable $e) {
            //
        });

        // Pastikan respons API selalu JSON dengan format seragam (context.md 5.2).
        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422),

                $e instanceof AuthenticationException => response()->json([
                    'message' => 'Belum terautentikasi.',
                ], 401),

                $e instanceof AuthorizationException,
                $e instanceof UnauthorizedException => response()->json([
                    'message' => 'Anda tidak memiliki izin untuk tindakan ini.',
                ], 403),

                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => response()->json([
                    'message' => 'Data tidak ditemukan.',
                ], 404),

                default => $this->renderApiFallback($e),
            };
        });
    }

    /**
     * Fallback JSON untuk error tak terduga: pertahankan status HTTP yang sesuai,
     * sembunyikan detail saat produksi.
     */
    private function renderApiFallback(Throwable $e)
    {
        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        $payload = ['message' => $status === 500 ? 'Terjadi kesalahan pada server' : $e->getMessage()];

        if (config('app.debug') && $status === 500) {
            $payload['exception'] = $e->getMessage();
        }

        return response()->json($payload, $status);
    }
}
