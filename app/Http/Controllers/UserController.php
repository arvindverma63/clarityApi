<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/update-fcm-token",
     *     tags={"Users"},
     *     summary="Update user's FCM token",
     *     description="Updates the Firebase Cloud Messaging token for the authenticated user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="fcm_token", type="string", example="sample_fcm_token", description="Firebase Cloud Messaging token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FCM token updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="FCM token updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     * )
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        $user = $request->user();
        $user->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['message' => 'FCM token updated']);
    }
}
