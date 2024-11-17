<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    /**
     * json response method.
     *
     * @param $message
     * @param array $data
     * @param int $statusCode
     * @return JsonResponse
     */
    public function sendJsonResponse($message, mixed $data = [], int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $isSuccess = $statusCode < 400;
        $response = [
            'success' => $isSuccess,
            'message' => $message,
        ];

        if(!$isSuccess){
            $response['errors'] = $data;
        }else{
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
}
