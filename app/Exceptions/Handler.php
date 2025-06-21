<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;
use App\Helpers\BaseResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;  
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
    }

    public function render($request, Throwable $exception): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($request->expectsJson()) {

            if ($exception instanceof ModelNotFoundException) {
                return BaseResponse::Notfound('Data tidak ditemukan.');
            }

            if ($exception instanceof AuthenticationException) {
                return BaseResponse::Custom(false, 'Anda harus login untuk mengakses resource ini.', null, 401);
            }

            if ($exception instanceof AccessDeniedHttpException ) {
                return BaseResponse::Custom(false, 'Akses ditolak.', null, 403);
            }

            if ($exception instanceof ValidationException) {
                return BaseResponse::Custom(false, 'Validasi gagal!', $exception->errors(), 422);
            }

            if ($exception instanceof ThrottleRequestsException) {
                return BaseResponse::Custom(false, 'Terlalu banyak permintaan. Coba lagi nanti.', null, 429);
            }

            if ($exception instanceof MethodNotAllowedHttpException) {
                return BaseResponse::Custom(false, 'Metode tidak diizinkan.', null, 405);
            }

            if ($exception instanceof HttpExceptionInterface) {
                return BaseResponse::Custom(false, $exception->getMessage(), null, $exception->getStatusCode());
            }

            return BaseResponse::ServerError('Terjadi kesalahan pada server.');
        }

        return parent::render($request, $exception);
    }
}
