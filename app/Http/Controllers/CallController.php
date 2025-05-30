<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class CallController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/start-call",
     *     tags={"Calls"},
     *     summary="Initiate a one-to-one video call",
     *     description="Sends a call initiation request to the specified callee with WebRTC SDP offer using Firebase Cloud Messaging",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="callee_id", type="integer", example=2, description="ID of the user to call"),
     *             @OA\Property(property="sdp_offer", type="string", example="v=0\r\no=- 123456789 2 IN IP4 127.0.0.1\r\n...", description="WebRTC SDP offer for call initiation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Call initiated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Callee not available or invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Callee not available")
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
    public function startCall(Request $request)
    {
        $request->validate(['callee_id' => 'required|exists:users,id']);
        $caller = $request->user();
        $callee = User::find($request->callee_id);

        if (!$callee->fcm_token) {
            return response()->json(['error' => 'Callee not available'], 400);
        }

        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $callee->fcm_token)
            ->withData([
                'type' => 'call',
                'caller_id' => $caller->id,
                'caller_name' => $caller->name,
                'sdp_offer' => $request->sdp_offer, // WebRTC SDP offer
            ]);

        $messaging->send($message);
        return response()->json(['message' => 'Call initiated']);
    }
}
