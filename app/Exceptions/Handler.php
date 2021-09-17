<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use PDOException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * @param $request
     * @param  Throwable  $exception
     * @return Application|ResponseFactory|JsonResponse|Response
     */
    private function handleApiException($request, Throwable $exception)
    {
        if (!strcmp(get_class($exception), AuthenticationException::class)) {
            return response('Unauthorized.', 401);
        }

        if ($exception instanceof \HttpResponseException) {
            $exception = $exception->getResponse();
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            $exception = $this->unauthenticated($request, $exception);
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            /** @var ValidationException $exception */
            return response()->json(['message' => 'validation', 'errors' => $exception->errors()], 500);
        }

        return $this->customApiResponse($exception);
    }

    /**
     * @param $exception
     * @return JsonResponse
     */
    private function customApiResponse($exception): JsonResponse
    {
        if ($exception instanceof AuthenticationException) {
            $statusCode = 403;
        } elseif ($exception instanceof AuthorizationException) {
            $statusCode = 401;
        } elseif (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = 500;
        }

        $response = [];

        switch ($statusCode) {
            case 401:
                $response['message'] = 'Unauthorized';
                break;
            case 403:
                $response['message'] = 'Forbidden';
                break;
            case 404:
                $response['message'] = 'Not Found';
                break;
            case 405:
                $response['message'] = 'Method Not Allowed';
                break;
            case 422:
                $response['message'] = $exception->original['message'];
                $response['errors'] = $exception->original['errors'];
                break;
            default:
                $response['message'] = ($exception instanceof QueryException)
                    ? 'Server internal error' : $exception->getMessage();
                break;
        }

        if (config('app.debug')) {
            $response['trace'] = $exception->getTrace();
            $response['code'] = $exception->getCode();
        }

        $response['status'] = $statusCode;

        return response()->json($response, $statusCode);
    }

    /**
     * @param  Request  $request
     * @param  Throwable  $e
     * @return Application|ResponseFactory|JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->wantsJson()) {
            return $this->handleApiException($request, $e);
        }

        if (!($e instanceof ValidationException)
            && !($e instanceof UnauthorizedException)
            && !($e instanceof AuthenticationException)
            && !($e instanceof AuthorizationException)
        ) {
            if (($e instanceof PDOException) || ($e instanceof QueryException)) {
                throw new Exception("Errore interno del server");
            } else {
                $errorMessage = $e->getMessage();
            }

            $e = ValidationException::withMessages([
                'exception' => json_decode(json_encode(['error' => $errorMessage]))
            ]);
        }

        return parent::render($request, $e);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
