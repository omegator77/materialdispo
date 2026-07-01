<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\LensDetail;
use Illuminate\Console\Command;

class ImportLensDetails extends Command
{
    protected $signature = 'app:import-lens-details';

    protected $description = 'Importiert Objektiv-Details für vorhandene Objektive';

    public function handle(): int
    {
        $lenses = [
            13 => [
                'bezeichnung' => 'Canon 10x Nr.1',
                'manufacturer' => 'Canon',
                'model' => 'KJ10ex4.5B IASE A',
                'serial_number' => '61810760',
                'zoom_factor' => '10x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '009162 G',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '008163 F',
            ],
            14 => [
                'bezeichnung' => 'Canon 10x Nr.2',
                'manufacturer' => 'Canon',
                'model' => 'KJ10ex4.5B IASE A',
                'serial_number' => '61810759',
                'zoom_factor' => '10x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '003036 G',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '001117 F',
            ],
            15 => [
                'bezeichnung' => 'Canon 11x Nr.1',
                'manufacturer' => 'Canon',
                'model' => 'HJ11ex4.7B IASE',
                'serial_number' => '00716281',
                'zoom_factor' => '11x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '307017 E',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '505720',
            ],
            16 => [
                'bezeichnung' => 'Canon 11x o. HKB',
                'manufacturer' => 'Canon',
                'model' => 'HJ11ex4.7B IRSE',
                'serial_number' => '00713573',
                'zoom_factor' => '11x',
                'zoom_servo_model' => null,
                'zoom_servo_serial_number' => null,
                'focus_servo_model' => null,
                'focus_servo_serial_number' => null,
            ],
            17 => [
                'bezeichnung' => 'Canon 14x',
                'manufacturer' => 'Canon',
                'model' => 'HJ14ex4.3B IASE S',
                'serial_number' => '01618673',
                'zoom_factor' => '14x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '504255',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '501177',
            ],
            18 => [
                'bezeichnung' => 'Canon 17x Nr.1',
                'manufacturer' => 'Canon',
                'model' => 'KJ17ex7.7B IASE',
                'serial_number' => '62210977',
                'zoom_factor' => '17x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '009078 G',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '304021 F',
            ],
            19 => [
                'bezeichnung' => 'Canon 17x Nr.2',
                'manufacturer' => 'Canon',
                'model' => 'KJ17ex7.7B IASE',
                'serial_number' => '62210966',
                'zoom_factor' => '17x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '009080 G',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '001118 F',
            ],
            20 => [
                'bezeichnung' => 'Canon 22x Nr.1',
                'manufacturer' => 'Canon',
                'model' => 'KJ22ex7.6B IASE',
                'serial_number' => '62311642',
                'zoom_factor' => '22x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '304072 H',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '304093 F',
            ],
            21 => [
                'bezeichnung' => 'Canon 22x Nr.2',
                'manufacturer' => 'Canon',
                'model' => 'HJ22ex7.6B IASE A',
                'serial_number' => '01220625',
                'zoom_factor' => '22x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '003036 G',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '008130 F',
            ],
            22 => [
                'bezeichnung' => 'Canon 22x Nr.3',
                'manufacturer' => 'Canon',
                'model' => 'HJ22ex7.6B IASE A',
                'serial_number' => '01219939',
                'zoom_factor' => '22x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '304014 H',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '008126 F',
            ],
            23 => [
                'bezeichnung' => 'Canon 22x Nr.4',
                'manufacturer' => 'Canon',
                'model' => 'KJ22ex7.6B IASE',
                'serial_number' => '62310009',
                'zoom_factor' => '22x',
                'zoom_servo_model' => 'ZSD-300D',
                'zoom_servo_serial_number' => '009161 G',
                'focus_servo_model' => 'FPD-400D',
                'focus_servo_serial_number' => '00812',
            ],
            24 => [
                'bezeichnung' => 'Fujinon 27x Nr.1',
                'manufacturer' => 'Fujinon',
                'model' => 'XJ27x6.5B',
                'serial_number' => '321627 AN',
                'zoom_factor' => '27x',
                'zoom_servo_model' => null,
                'zoom_servo_serial_number' => '500225',
                'focus_servo_model' => null,
                'focus_servo_serial_number' => '500088',
            ],
            25 => [
                'bezeichnung' => 'Fujinon 27x Nr.2',
                'manufacturer' => 'Fujinon',
                'model' => 'XJ27x6.5B',
                'serial_number' => '321592 AK',
                'zoom_factor' => '27x',
                'zoom_servo_model' => null,
                'zoom_servo_serial_number' => '500217',
                'focus_servo_model' => null,
                'focus_servo_serial_number' => '500092',
            ],
            26 => [
                'bezeichnung' => 'Fujinon 27x Nr.3 AF',
                'manufacturer' => 'Fujinon',
                'model' => 'XJ27x6.5B AF',
                'serial_number' => '321541 AJ',
                'zoom_factor' => '27x',
                'zoom_servo_model' => null,
                'zoom_servo_serial_number' => '006021 C',
                'focus_servo_model' => null,
                'focus_servo_serial_number' => null,
            ],
            27 => [
                'bezeichnung' => 'Fujinon 86x Nr.1',
                'manufacturer' => 'Fujinon',
                'model' => 'XJ86x9.3B IE-2',
                'serial_number' => '201134 AF',
                'zoom_factor' => '86x',
                'zoom_servo_model' => 'ZDJ-D02',
                'zoom_servo_serial_number' => '50702',
                'focus_servo_model' => 'CR-30',
                'focus_servo_serial_number' => null,
            ],
            28 => [
                'bezeichnung' => 'Fujinon 86x Nr.2',
                'manufacturer' => 'Fujinon',
                'model' => 'XJ86x9.3B IE-2',
                'serial_number' => '201109 AF',
                'zoom_factor' => '86x',
                'zoom_servo_model' => 'ZDJ-D02',
                'zoom_servo_serial_number' => null,
                'focus_servo_model' => 'CR-30',
                'focus_servo_serial_number' => null,
            ],
        ];

        foreach ($lenses as $itemId => $data) {
            $item = Item::find($itemId);

            if (! $item) {
                $this->warn("Item {$itemId} nicht gefunden.");

                continue;
            }

            $item->update([
                'bezeichnung' => $data['bezeichnung'],
                'description' => null,
            ]);

            unset($data['bezeichnung']);

            LensDetail::updateOrCreate(
                ['item_id' => $itemId],
                $data
            );

            $this->info("Objektiv {$itemId} importiert.");
        }

        $this->info('Objektiv-Details wurden importiert.');

        return self::SUCCESS;
    }
}
