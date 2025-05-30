<?php
namespace App\Http\Controllers;

use App\Models\Call;
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
     *     description="Initiates a call, stores call details (caller_id, callee_id, sdp_offer), and sends a notification to the callee via FCM",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="callee_id", type="integer", example=2, description="ID of the user to call"),
     *             @OA\Property(property="sdp_offer", type="string", example="v=0\r\no=- 123456789 2 IN IP4 127.0.0.1\r\n...", description="WebRTC SDP offer for call initiation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call initiated and stored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Call initiated"),
     *             @OA\Property(property="call_id", type="integer", example=1, description="ID of the stored call record")
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
     *     security={{"sanctum":{}}}
     * )
     */
    public function startCall(Request $request)
    {
        $request->validate([
            'callee_id' => 'required|exists:users,id',
            'sdp_offer' => 'required|string',
        ]);

        $caller = $request->user();
        $callee = User::find($request->callee_id);

        if (!$callee->fcm_token) {
            return response()->json(['error' => 'Callee not available'], 400);
        }

        // Store call details
        $call = Call::create([
            'caller_id' => $caller->id,
            'callee_id' => $callee->id,
            'sdp_offer' => $request->sdp_offer,
            'status' => 'initiated',
        ]);

        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $callee->fcm_token)
            ->withData([
                'type' => 'call',
                'caller_id' => (string) $caller->id,
                'caller_name' => $caller->name,
                'sdp_offer' => $request->sdp_offer,
                'call_id' => (string) $call->id,
            ]);

        $messaging->send($message);

        return response()->json([
            'message' => 'Call initiated',
            'call_id' => $call->id,
        ]);
    }
}
