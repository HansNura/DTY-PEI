<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderReceiptScan;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\ProofOfDelivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProofOfDeliveryService
{
    /**
     * @param  array{received_by_name: string, scanned_tokens: list<string>, signature: string}  $data
     */
    public function confirm(DeliveryOrder $order, User $driver, array $data): ProofOfDelivery
    {
        $signature = base64_decode(substr($data['signature'], strlen('data:image/png;base64,')), true);

        if ($signature === false || strlen($signature) > 250000) {
            throw ValidationException::withMessages(['signature' => 'Tanda tangan tidak valid atau ukurannya terlalu besar.']);
        }

        $image = getimagesizefromstring($signature);

        if ($image === false || $image['mime'] !== 'image/png' || $image[0] > 2048 || $image[1] > 1024) {
            throw ValidationException::withMessages(['signature' => 'Tanda tangan harus berupa gambar PNG yang valid.']);
        }

        $signaturePath = null;

        try {
            $result = DB::transaction(function () use ($order, $driver, $data, $signature, &$signaturePath): array {
                $lockedOrder = DeliveryOrder::query()->lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->driver_id !== $driver->id) {
                    abort(403, 'Surat jalan ini bukan tanggung jawab Anda.');
                }

                if ($lockedOrder->status !== DeliveryOrderStatus::Arrived || $lockedOrder->proofOfDelivery()->exists()) {
                    throw ValidationException::withMessages(['received_by_name' => 'Konfirmasi hanya dapat dilakukan satu kali setelah status tiba.']);
                }

                $items = $lockedOrder->items()
                    ->with('roll.carton')
                    ->get();
                $expectedRolls = $items->pluck('roll')->filter()->keyBy('id');
                $expectedCartons = $items
                    ->map(fn ($item) => $item->roll?->carton)
                    ->filter()
                    ->unique('id')
                    ->keyBy('id');
                $expectedCartonRolls = $items
                    ->filter(fn ($item): bool => $item->roll?->carton !== null)
                    ->groupBy(fn ($item): int => $item->roll->carton->id);
                $seenTokens = [];
                $validRollIds = [];
                $scans = [];

                foreach ($data['scanned_tokens'] as $token) {
                    $carton = $expectedCartons->first(
                        fn (DtyCarton $expectedCarton): bool => $expectedCarton->nomor_hu === $token
                            || $expectedCarton->qr_token === $token
                    );

                    if ($carton !== null) {
                        $cartonRollIds = $expectedCartonRolls->get($carton->id, collect())
                            ->pluck('roll.id')
                            ->map(fn ($id): int => (int) $id)
                            ->all();
                        $result = isset($seenTokens[$token]) || array_intersect($cartonRollIds, $validRollIds) !== []
                            ? 'duplicate'
                            : 'valid';

                        $seenTokens[$token] = true;

                        foreach ($cartonRollIds as $rollId) {
                            if ($result === 'valid') {
                                $validRollIds[] = $rollId;
                            }

                            $scans[] = [
                                'delivery_order_id' => $lockedOrder->id,
                                'dty_roll_id' => $rollId,
                                'scanned_by' => $driver->id,
                                'result' => $result,
                                'scanned_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        continue;
                    }

                    $otherCarton = DtyCarton::query()
                        ->where(fn ($query) => $query->where('nomor_hu', $token)->orWhere('qr_token', $token))
                        ->first();

                    if ($otherCarton !== null) {
                        $seenTokens[$token] = true;
                        $scans[] = [
                            'delivery_order_id' => $lockedOrder->id,
                            'dty_roll_id' => null,
                            'scanned_by' => $driver->id,
                            'result' => 'wrong_do',
                            'scanned_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        continue;
                    }

                    $roll = $expectedRolls->first(
                        fn (DtyRoll $expectedRoll): bool => $expectedRoll->qr_token === $token
                            || $expectedRoll->nomor_seri === $token
                    );
                    $knownRoll = $roll ?? DtyRoll::query()
                        ->where(fn ($query) => $query
                            ->where('qr_token', $token)
                            ->orWhere('nomor_seri', $token))
                        ->first(['id', 'qr_token', 'nomor_seri']);
                    $result = 'valid';

                    if (isset($seenTokens[$token]) || ($roll !== null && in_array($roll->id, $validRollIds, true))) {
                        $result = 'duplicate';
                    } elseif ($knownRoll === null) {
                        $result = 'unknown';
                    } elseif ($roll === null) {
                        $result = 'wrong_do';
                    } else {
                        $validRollIds[] = $roll->id;
                    }

                    $seenTokens[$token] = true;
                    $scans[] = [
                        'delivery_order_id' => $lockedOrder->id,
                        'dty_roll_id' => $knownRoll?->id,
                        'scanned_by' => $driver->id,
                        'result' => $result,
                        'scanned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                DeliveryOrderReceiptScan::query()->insert($scans);
                $missingRolls = $expectedRolls->filter(
                    fn (DtyRoll $expectedRoll): bool => ! in_array($expectedRoll->id, $validRollIds, true)
                );
                $hasInvalidScan = collect($scans)->contains(fn (array $scan): bool => $scan['result'] !== 'valid');

                if ($hasInvalidScan || $missingRolls->isNotEmpty() || count($validRollIds) !== $expectedRolls->count()) {
                    return [
                        'errors' => ['scanned_tokens' => 'Scan ditolak. Pastikan setiap QR sesuai surat jalan, tidak berulang, dan seluruh gulungan sudah dipindai.'],
                        'proof' => null,
                    ];
                }

                $signaturePath = 'proof-of-deliveries/'.str()->uuid().'.png';

                if (! Storage::disk('local')->put($signaturePath, $signature)) {
                    throw new RuntimeException('Tanda tangan gagal disimpan.');
                }

                $proof = ProofOfDelivery::create([
                    'delivery_order_id' => $lockedOrder->id,
                    'received_by_name' => $data['received_by_name'],
                    'signature_path' => $signaturePath,
                    'received_at' => now(),
                ]);

                DtyRoll::query()->whereIn('id', $validRollIds)->update(['status' => RollStatus::Delivered->value]);
                $lockedOrder->update(['status' => DeliveryOrderStatus::Received]);

                return ['errors' => [], 'proof' => $proof];
            });
        } catch (Throwable $exception) {
            if ($signaturePath !== null) {
                Storage::disk('local')->delete($signaturePath);
            }

            throw $exception;
        }

        if ($result['errors'] !== []) {
            throw ValidationException::withMessages($result['errors']);
        }

        return $result['proof'];
    }
}
