<?php

namespace App\Http\Controllers\Integrations;

use App\Exceptions\Twilio\TwilioBalanceFetchFailed;
use App\Exceptions\Twilio\TwilioCredentialsMissing;
use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Services\TwilioAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwilioBalanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function __invoke(Request $request, Entity $entity, TwilioAccountService $twilioAccountService): JsonResponse
    {
        $user = $request->user();

        if (! $user || (int) $user->entity_id !== (int) $entity->id) {
            abort(403, 'You are not authorized to view this balance.');
        }

        try {
            $balance = $twilioAccountService->fetchBalanceForEntity($entity);
        } catch (TwilioCredentialsMissing $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (TwilioBalanceFetchFailed $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($balance);
    }
}
