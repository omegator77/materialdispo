<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class CameraDetailSeeder extends Seeder
{
    public function run(): void
    {
        $kameras = [
            [
                'nummer' => '22',
                'body_serial' => '3RR65',
                'fiber_adapter_serial' => '3SGPR',
                'large_viewfinder_model' => 'LDK 5307/40 OLED 7.4"',
                'large_viewfinder_type' => 'OLED',
                'large_viewfinder_serial' => '3RUJN',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3SWYW',
                'ssl_license' => false,
            ],
            [
                'nummer' => '23',
                'body_serial' => '3RHNV',
                'fiber_adapter_serial' => '3SGZA',
                'large_viewfinder_model' => 'LDK 5307/40 OLED 7.4"',
                'large_viewfinder_type' => 'OLED',
                'large_viewfinder_serial' => '3RUJR',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3SVSF',
                'ssl_license' => false,
            ],
            [
                'nummer' => '24',
                'body_serial' => '3SNTD',
                'fiber_adapter_serial' => '3SGPP',
                'large_viewfinder_model' => 'LDK 5307/40 OLED 7.4"',
                'large_viewfinder_type' => 'OLED',
                'large_viewfinder_serial' => '3RZ7G',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3SX7H',
                'ssl_license' => false,
            ],
            [
                'nummer' => '25',
                'body_serial' => '3RUMY',
                'fiber_adapter_serial' => '3RPKJ',
                'large_viewfinder_model' => 'LDK 5307/00 LCD 7"',
                'large_viewfinder_type' => 'LCD',
                'large_viewfinder_serial' => '3RR4Z',
                'small_viewfinder_model' => 'LDK 5302/60 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3A6R4',
                'ssl_license' => false,
            ],
            [
                'nummer' => '26',
                'body_serial' => '3RU3A',
                'fiber_adapter_serial' => '3SGPG',
                'large_viewfinder_model' => 'LDK 5307/00 LCD 7"',
                'large_viewfinder_type' => 'LCD',
                'large_viewfinder_serial' => '3SNE3',
                'small_viewfinder_model' => 'LDK 5302/60 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '27XH7',
                'ssl_license' => false,
            ],
            [
                'nummer' => '27',
                'body_serial' => '3RNWC',
                'fiber_adapter_serial' => '3SGPK',
                'large_viewfinder_model' => 'LDK 5307/00 LCD 7"',
                'large_viewfinder_type' => 'LCD',
                'large_viewfinder_serial' => '3SJPD',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3SWTF',
                'ssl_license' => false,
            ],
            [
                'nummer' => '28',
                'body_serial' => '3F939',
                'fiber_adapter_serial' => '3ASDN',
                'large_viewfinder_model' => 'LDK 5307/40 OLED 7.4"',
                'large_viewfinder_type' => 'OLED',
                'large_viewfinder_serial' => '3G3P9',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3GNC4',
                'ssl_license' => true,
            ],
            [
                'nummer' => '29',
                'body_serial' => '3ZDYZ',
                'fiber_adapter_serial' => '3H2SG',
                'large_viewfinder_model' => 'LDK 5307/40 OLED 7.4"',
                'large_viewfinder_type' => 'OLED',
                'large_viewfinder_serial' => '3G3P5',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3GN4A',
                'ssl_license' => true,
            ],
            [
                'nummer' => '30',
                'body_serial' => '3YUPC',
                'fiber_adapter_serial' => '3Y3XA',
                'large_viewfinder_model' => 'LDK 5307/40 OLED 7.4"',
                'large_viewfinder_type' => 'OLED',
                'large_viewfinder_serial' => '3XNW6',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3YDYS',
                'ssl_license' => true,
            ],
            [
                'nummer' => '31',
                'body_serial' => '3YZMH',
                'fiber_adapter_serial' => '3YY9H',
                'large_viewfinder_model' => 'LDK 5307/40 OLED 7.4"',
                'large_viewfinder_type' => 'OLED',
                'large_viewfinder_serial' => '3XF94',
                'small_viewfinder_model' => 'LDK 5303/50 LCD 2"',
                'small_viewfinder_type' => 'LCD',
                'small_viewfinder_serial' => '3YEER',
                'ssl_license' => true,
            ],
        ];

        foreach ($kameras as $data) {
            $nummer = $data['nummer'];
            unset($data['nummer']);

            $item = Item::where('nummer', $nummer)
                ->where('units_id', 1)
                ->first();

            if (! $item) {
                $this->command?->warn("Kamera {$nummer} nicht gefunden.");
                continue;
            }

            $item->cameraDetail()->updateOrCreate(
                ['item_id' => $item->id],
                $data
            );

            $this->command?->info("Kamera {$nummer} importiert.");
        }
    }
}