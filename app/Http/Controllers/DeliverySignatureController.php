<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DeliverySignatureController extends Controller
{
    public function __invoke(Request $request, DeliveryOrder $deliveryOrder): Response
    {
        $user = $request->user();
        $canView = $user?->hasRole(UserRole::Logistik)
            || ($user?->hasRole(UserRole::Driver) && $deliveryOrder->driver_id === $user->id);

        abort_unless($canView, 403, 'Anda tidak memiliki akses ke bukti tanda tangan ini.');

        $signaturePath = $deliveryOrder->proofOfDelivery?->signature_path;

        abort_if($signaturePath === null || ! Storage::disk('local')->exists($signaturePath), 404);

        return Storage::disk('local')->response($signaturePath, headers: [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
