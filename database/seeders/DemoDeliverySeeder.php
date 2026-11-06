<?php

namespace Database\Seeders;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\TrackingStatus;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryTrackingEvent;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\LoadScan;
use App\Models\ProductionBatch;
use App\Models\ProofOfDelivery;
use App\Models\User;
use App\Services\DeliveryOrderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoDeliverySeeder extends Seeder
{
    private int $serial = 98000;

    private int $hu = 2000;

    public function run(): void
    {
        $warehouse = User::query()->where('email', 'gudang@pmt.test')->first();
        $logistics = User::query()->where('email', 'logistik@pmt.test')->first();

        if ($warehouse === null || $logistics === null) {
            return;
        }

        $customers = Customer::query()->where('is_active', true)->get()->keyBy('kode_pelanggan');
        $fleets = Fleet::query()->where('is_active', true)->get()->keyBy('nomor_polisi');
        $drivers = User::query()->whereIn('email', [
            'driver@pmt.test',
            'driver2@pmt.test',
            'driver3@pmt.test',
            'driver4@pmt.test',
        ])->get()->keyBy('email');
        $batches = ProductionBatch::query()->get()->keyBy('nomor_merge');

        $scenarios = [
            [
                'number' => 'DO-20261106-1001',
                'date' => '2026-11-06',
                'customer' => 'CUST-0001',
                'plate' => 'B 1234 PMT',
                'driver' => 'driver@pmt.test',
                'merge' => '4D9164',
                'boxes' => 6,
                'status' => DeliveryOrderStatus::Issued,
                'note' => 'Pengiriman kain rajut reguler, muatan siap dipindai checker.',
            ],
            [
                'number' => 'DO-20261106-1002',
                'date' => '2026-11-06',
                'customer' => 'CUST-0002',
                'plate' => 'B 9876 XYZ',
                'driver' => 'driver2@pmt.test',
                'merge' => '4D9165',
                'boxes' => 6,
                'status' => DeliveryOrderStatus::Loading,
                'note' => 'Pemuatan bertahap; tiga kardus sudah divalidasi.',
                'load_boxes' => 3,
            ],
            [
                'number' => 'DO-20261105-1001',
                'date' => '2026-11-05',
                'customer' => 'CUST-0003',
                'plate' => 'D 2345 AB',
                'driver' => 'driver3@pmt.test',
                'merge' => '4D9166',
                'boxes' => 6,
                'status' => DeliveryOrderStatus::Departed,
                'note' => 'Armada berangkat menuju Tangerang Selatan.',
            ],
            [
                'number' => 'DO-20261105-1002',
                'date' => '2026-11-05',
                'customer' => 'CUST-0004',
                'plate' => 'F 7788 CD',
                'driver' => 'driver4@pmt.test',
                'merge' => '4D9167',
                'boxes' => 6,
                'status' => DeliveryOrderStatus::InTransit,
                'note' => 'Armada dalam perjalanan menuju Surakarta.',
            ],
            [
                'number' => 'DO-20261104-1001',
                'date' => '2026-11-04',
                'customer' => 'CUST-0005',
                'plate' => 'B 1234 PMT',
                'driver' => 'driver@pmt.test',
                'merge' => '4D9168',
                'boxes' => 6,
                'status' => DeliveryOrderStatus::Arrived,
                'note' => 'Armada tiba; menunggu konfirmasi penerimaan pelanggan.',
            ],
            [
                'number' => 'DO-20261103-1001',
                'date' => '2026-11-03',
                'customer' => 'CUST-0006',
                'plate' => 'B 9876 XYZ',
                'driver' => 'driver2@pmt.test',
                'merge' => '4D9164',
                'boxes' => 30,
                'status' => DeliveryOrderStatus::Received,
                'note' => 'Pengiriman satu palet penuh; sudah diterima pelanggan.',
                'received' => true,
            ],
            [
                'number' => 'DO-20261102-1001',
                'date' => '2026-11-02',
                'customer' => 'CUST-0001',
                'plate' => 'D 2345 AB',
                'driver' => 'driver3@pmt.test',
                'merge' => '4D9165',
                'boxes' => 6,
                'status' => DeliveryOrderStatus::Cancelled,
                'note' => 'Dibatalkan karena perubahan jadwal pengiriman.',
            ],
        ];

        foreach ($scenarios as $scenario) {
            if (DeliveryOrder::query()->where('nomor_do', $scenario['number'])->exists()) {
                continue;
            }

            $customer = $customers->get($scenario['customer']);
            $fleet = $fleets->get($scenario['plate']);
            $driver = $drivers->get($scenario['driver']);
            $batch = $batches->get($scenario['merge']);

            if ($customer === null || $fleet === null || $driver === null || $batch === null) {
                continue;
            }

            DB::transaction(function () use ($scenario, $customer, $fleet, $driver, $batch, $warehouse, $logistics): void {
                $cartons = $this->createBoxes($batch, $scenario['boxes'], $scenario['date'], $warehouse);
                $rollIds = $cartons->flatMap(fn (DtyCarton $carton): Collection => $carton->rolls->pluck('id'))->all();

                $order = app(DeliveryOrderService::class)->createDeliveryOrder([
                    'nomor_do' => $scenario['number'],
                    'customer_id' => $customer->id,
                    'fleet_id' => $fleet->id,
                    'driver_id' => $driver->id,
                    'tanggal' => $scenario['date'],
                    'catatan' => $scenario['note'],
                ], $rollIds, $logistics);

                $status = $scenario['status'];

                if ($status === DeliveryOrderStatus::Cancelled) {
                    app(DeliveryOrderService::class)->cancel($order);

                    return;
                }

                $loadCount = $scenario['load_boxes'] ?? $scenario['boxes'];

                if ($loadCount > 0) {
                    $this->loadCartons($order, $cartons->take($loadCount), $warehouse, Carbon::parse($scenario['date'])->setTime(8, 30));
                }

                if ($status === DeliveryOrderStatus::Loading) {
                    $order->update(['status' => DeliveryOrderStatus::Loading]);

                    return;
                }

                if ($status === DeliveryOrderStatus::Issued) {
                    return;
                }

                $order->update(['status' => DeliveryOrderStatus::Departed]);
                $this->trackingEvent($order, $driver, TrackingStatus::Departed, Carbon::parse($scenario['date'])->setTime(9, 0), -6.9175, 107.6191);

                if ($status === DeliveryOrderStatus::Departed) {
                    return;
                }

                $order->update(['status' => DeliveryOrderStatus::InTransit]);
                $this->trackingEvent(
                    $order,
                    $driver,
                    TrackingStatus::InTransit,
                    Carbon::parse($scenario['date'])->setTime(12, 15),
                    ((float) $customer->latitude + -6.9175) / 2,
                    ((float) $customer->longitude + 107.6191) / 2,
                );

                if ($status === DeliveryOrderStatus::InTransit) {
                    return;
                }

                $order->update(['status' => DeliveryOrderStatus::Arrived]);
                $this->trackingEvent(
                    $order,
                    $driver,
                    TrackingStatus::Arrived,
                    Carbon::parse($scenario['date'])->setTime(15, 30),
                    (float) $customer->latitude,
                    (float) $customer->longitude,
                );

                if ($status === DeliveryOrderStatus::Arrived) {
                    return;
                }

                $this->completeReceipt($order, $driver, $warehouse, $customer, $scenario['date']);
            });
        }
    }

    /**
     * @return Collection<int, DtyCarton>
     */
    private function createBoxes(ProductionBatch $batch, int $count, string $date, User $warehouse): Collection
    {
        return collect(range(1, $count))->map(function () use ($batch, $date, $warehouse): DtyCarton {
            $hu = 'ADMG'.str_pad((string) (++$this->hu), 8, '0', STR_PAD_LEFT);
            $weight = 5485;
            $grade = 'AX';
            $netKg = round($weight * 6 / 1000, 2);

            $carton = DtyCarton::create([
                'nomor_hu' => $hu,
                'nomor_merge' => $batch->nomor_merge,
                'grade' => $grade,
                'production_date' => $date,
                'denier' => $batch->denier,
                'filamen' => $batch->filamen,
                'intermingled' => $batch->intermingled,
                'twist_direction' => $batch->twist_direction,
                'gross_weight_kg' => round($netKg + 2.65, 2),
                'net_weight_kg' => $netKg,
                'unit_bobbins' => 6,
                'pallet_number' => null,
                'qr_token' => substr(hash('sha256', 'demo-carton-'.$hu), 0, 64),
                'catatan' => null,
                'created_by' => $warehouse->id,
            ]);

            foreach (range(1, 6) as $position) {
                $kodeRol = $hu.'-'.$position;
                $serial = str_pad((string) (++$this->serial), 5, '0', STR_PAD_LEFT)
                    .'0079'
                    .Carbon::parse($date)->format('Ymd');

                DtyRoll::create([
                    'dty_carton_id' => $carton->id,
                    'production_batch_id' => $batch->id,
                    'kode_rol' => 'DTY-DEMO-'.$kodeRol,
                    'nomor_seri' => $serial,
                    'grade' => $grade,
                    'denier' => $batch->denier,
                    'filamen' => $batch->filamen,
                    'berat_gram' => $weight,
                    'nomor_lot' => $batch->nomor_merge,
                    'qr_token' => substr(hash('sha256', 'demo-roll-'.$serial), 0, 64),
                    'status' => RollStatus::InStock,
                    'created_by' => $warehouse->id,
                ]);
            }

            return $carton->load('rolls');
        });
    }

    /**
     * @param  Collection<int, DtyCarton>  $cartons
     */
    private function loadCartons(DeliveryOrder $order, Collection $cartons, User $warehouse, Carbon $at): void
    {
        foreach ($cartons as $carton) {
            $carton->rolls()->update(['status' => RollStatus::Loaded->value]);

            LoadScan::create([
                'delivery_order_id' => $order->id,
                'dty_carton_id' => $carton->id,
                'scanned_by' => $warehouse->id,
                'result' => 'valid',
                'scanned_at' => $at,
            ]);
        }
    }

    private function trackingEvent(
        DeliveryOrder $order,
        User $driver,
        TrackingStatus $status,
        Carbon $at,
        float $latitude,
        float $longitude,
    ): void {
        DeliveryTrackingEvent::create([
            'delivery_order_id' => $order->id,
            'status' => $status,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_by' => $driver->id,
            'recorded_at' => $at,
        ]);
    }

    private function completeReceipt(DeliveryOrder $order, User $driver, User $warehouse, Customer $customer, string $date): void
    {
        $signaturePath = 'proof-of-deliveries/demo-signature.png';

        if (! Storage::disk('local')->exists($signaturePath)) {
            Storage::disk('local')->put($signaturePath, $this->signaturePng());
        }

        ProofOfDelivery::create([
            'delivery_order_id' => $order->id,
            'received_by_name' => $customer->kontak_nama,
            'signature_path' => $signaturePath,
            'received_at' => Carbon::parse($date)->setTime(16, 0),
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
        ]);

        DtyRoll::query()
            ->whereIn('id', $order->items()->pluck('dty_roll_id'))
            ->update(['status' => RollStatus::Delivered->value]);

        $order->update(['status' => DeliveryOrderStatus::Received]);
    }

    private function signaturePng(): string
    {
        if (function_exists('imagecreatetruecolor')) {
            $image = imagecreatetruecolor(320, 100);
            $white = imagecolorallocate($image, 255, 255, 255);
            $ink = imagecolorallocate($image, 15, 23, 42);
            imagefill($image, 0, 0, $white);
            imageline($image, 30, 65, 85, 35, $ink);
            imageline($image, 85, 35, 130, 70, $ink);
            imageline($image, 130, 70, 185, 30, $ink);
            imageline($image, 185, 30, 250, 60, $ink);
            imageline($image, 250, 60, 290, 40, $ink);

            ob_start();
            imagepng($image);
            $png = (string) ob_get_clean();
            imagedestroy($image);

            return $png;
        }

        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}
