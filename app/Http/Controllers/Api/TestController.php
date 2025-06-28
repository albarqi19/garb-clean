<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    public function testRequest(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'received_data' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method(),
            'raw_input' => $request->getContent(),
            'json_decoded' => json_decode($request->getContent(), true),
            'input_student_id' => $request->input('student_id'),
            'has_json' => $request->isJson(),
        ]);
    }
}
