<?php
namespace App\Trait;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait {
    //
    protected function successResponse($data = null, string $message = "Request was successful", int $code = Response::HTTP_OK): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
    protected function authResponse( $user,string $tokenType='Bearer',string $token, string $message = "Authentication successful", int $code = Response::HTTP_OK): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => [
                'token_type' => $tokenType,
                'token' => $token,
                'user'  => $user,
            ],
        ], $code);
    }

    protected function errorResponse(string $message = "An error occurred", int $code = Response::HTTP_BAD_REQUEST, $errors = null): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    protected function createResponse($data = null, string $message = "Resource created successfully", int $code = Response::HTTP_CREATED): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function updatedResponse($data = null, string $message = "Resource updated successfully", int $code = Response::HTTP_OK): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function deletedResponse(string $message = "Resource deleted successfully", int $code = Response::HTTP_OK): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
        ], $code);
    }
    protected function notFoundResponse(string $message = "Resource not found", int $code = Response::HTTP_NOT_FOUND): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $code);
    }

    protected function validationErrorResponse($errors = null, string $message = "Validation errors occurred", int $code = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    protected function unauthorizedResponse(string $message = "Unauthorized access", int $code = Response::HTTP_UNAUTHORIZED): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $code);
    }

    protected function forbiddenResponse(string $message = "Forbidden access", int $code = Response::HTTP_FORBIDDEN): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $code);
    }

    protected function serverErrorResponse(string $message = "Internal server error", int $code = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $code);
    }

    protected function paginationResponse($data = null, string $message = "Request was successful", int $code = Response::HTTP_OK): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data->items(),
            'meta'    => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ], $code);
    }
}
