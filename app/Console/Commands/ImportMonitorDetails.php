<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;

class ImportMonitorDetails extends Command
{
    protected $signature = 'import:monitor-details';
    protected $description = 'Importiert Monitor-Metadaten für kleine und große Monitore';

    public function handle(): int
    {
        $monitors = [
            // Kleine Monitore Unit 9
            [9, '1', 'BON', 'BPM 200 LS', 'B011M159', true, true, '20"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '2', 'BON', 'BPM 200 LS', 'B011M160', true, true, '20"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '3', 'BON', 'BTM 170 LS', 'B106M041', false, false, '17"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '4', 'BON', 'BTM 170 LS', 'B106M039', false, false, '17"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '5', 'BON', 'BPM 200 LS', 'B008M149', true, true, '20"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '6', 'BON', 'BPM 200 LS', 'B008M152', true, true, '20"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '7', 'BON', 'BEM 182', 'B706M083', false, true, '18,5"', null, null, false, '1080p50 (3G)', false, null],
            [9, '8', 'BON', 'BEM 182', 'B706M081', false, true, '18,5"', null, null, false, '1080p50 (3G)', false, null],
            [9, '9', 'BON', 'BEM 182', 'B706M084', false, true, '18,5"', null, null, false, '1080p50 (3G)', false, null],
            [9, '10', 'BON', 'BPM 201 LS', 'B112M110', true, true, '20"', null, null, false, '1080i50 (1,5G)', false, null],

            [9, '11', 'TVLogic', 'LVM 172 W', 'LVM172J847', true, true, '17"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '12', 'TVLogic', 'LVM 172 W', 'LVM172JY56', true, true, '17"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '13', 'TVLogic', 'LVM 172 W', 'LVM172JP12', true, true, '17"', null, null, false, '1080i50 (1,5G)', false, null],
            [9, '14', 'TVLogic', 'LVM 172 W', 'LV172K1740', true, true, '17"', null, null, false, '1080i50 (1,5G)', false, null],

            [9, '20', 'Lilliput', 'PVM 210 S', 'PVM21SA69876043', true, true, '21,5"', null, null, false, '1080p50 (3G)', false, null],
            [9, '21', 'Lilliput', 'PVM 210 S', 'PVM21SA69876056', true, true, '21,5"', null, null, false, '1080p50 (3G)', false, null],
            [9, '22', 'Lilliput', 'BM230-4KS', 'BM23A6070D029', true, true, '23,8"', null, null, false, '1080p50 (3G)', false, null],
            [9, '23', 'Lilliput', 'BM230-4KS', 'BM23A6070D028', true, true, '23,8"', null, null, false, '1080p50 (3G)', false, null],

            [9, '24', 'Seetec', 'Akku', 'P1731900272', true, true, '17"', null, null, false, '1080p50 (3G)', false, null],
            [9, '25', 'Seetec', '21 Zoll', 'P2152200227', true, true, '21"', null, null, false, '1080p50 (3G)', false, null],
            [9, '26', 'Seetec', '21 Zoll', 'P2152200223', true, true, '21"', null, null, false, '1080p50 (3G)', false, null],
            [9, '27', 'Seetec', '17 Zoll', 'P1731900511', true, true, '17"', null, null, false, '1080p50 (3G)', false, null],
            [9, '28', 'Seetec', '17 Zoll', 'P1731900509', true, true, '17"', null, null, false, '1080p50 (3G)', false, null],

            [9, '29', 'HD2Line', 'PDP 17,3 W', '173-0042-14', false, false, '17"', null, null, false, '1080p50 (3G)', false, null],
            [9, '30', 'HD2Line', 'PDP 17,3 W', '173-0043-14', false, false, '17"', null, null, false, '1080p50 (3G)', false, null],
            [9, '31', 'HDQLine', 'HRDP 240-A', '240-2003-2110', false, true, '24"', null, null, false, '1080p50 (3G)', false, null],

            [9, '32', 'Lenovo', 'LT2223pWC', 'VN451494', false, true, '22"', '12', 'NoName', true, '1080i50 (1,5G)', false, null],
            [9, '33', 'Lenovo', 'LT2223pWC', 'VN451365', false, true, '22"', null, 'NoName', false, '1080i50 (1,5G)', false, null],

            [9, '34', 'Samsung', 'LT22B300', '0237H4MC802275M', true, false, '22"', null, null, false, null, false, null],
            [9, '35', 'Samsung', 'S22A350H', '0048H4MC110028P', false, false, '22"', null, null, false, null, false, null],
            [9, '36', 'Samsung', 'S22A350H', '0048H4MC111323X', false, false, '22"', null, null, false, null, false, null],
            [9, '37', 'Samsung', 'S22A350H', 'Nicht lesbar', false, false, '22"', null, null, false, null, false, null],
            [9, '38', 'Samsung', 'S22A350H', '0048H4MC110017X', false, false, '22"', null, null, false, null, false, null],

            [9, '39', 'Sony', 'LMD-A240', '7451739', true, true, '24"', null, null, false, '1080p50 (3G)', true, null],
            [9, '40', 'Sony', 'LMD-A240', '7451802', true, true, '24"', null, null, false, '1080p50 (3G)', true, null],
            [9, '41', 'HD2Line', 'PDP 17,0 W', '170-1046-14', false, false, '17"', null, null, false, '1080p50 (3G)', false, null],

            // Große Monitore Unit 10
            [10, '1', 'Grundig', '55VCE222', '20600012', true, true, '55"', '44', 'BMD BiDirectional 3G', true, '1080i50 (1,5G)', true, '1'],
            [10, '2', 'Samsung', 'GU60AU8079U', '0H743HWR5017279', true, true, '60"', '45', 'BMD BiDirectional 3G', true, '1080i50 (1,5G)', true, '2'],
            [10, '3', 'Sony', 'KD-49X8305C', '6045932', true, true, '49"', '20', 'Shuiting 3G SDI to HDMI', true, '1080p50 (3G)', true, '3'],
            [10, '4', 'Sony', 'KD-49X8305C', '6046037', true, true, '49"', '19', 'Amazon Basics', false, '1080i50 (1,5G)', true, '4'],
            [10, '5', 'Dyon', 'ENTER42PROX2', '3010014903809', true, true, '42"', '43', 'BMD BiDirectional 3G', true, '1080i50 (1,5G)', true, '5'],
            [10, '6', 'Sony', 'XH81', '6008867', true, true, '49"', '47', 'Amazon Basics', false, '1080i50 (1,5G)', true, '6'],
            [10, '7', 'Philips', '32PH55525/12', 'FZ5A2143061139', true, true, '32"', '40', 'BMD BiDirectional 3G', true, '1080p50 (3G)', false, null],
            [10, '8', 'Philips', '32PH55525/13', 'FZ5A2143061143', true, true, '32"', '41', 'BMD BiDirectional 3G', true, '1080p50 (3G)', false, null],
            [10, '9', 'Philips', '42PFL3606H/12', 'RJA1137004988', true, true, '42"', '17', 'BMD Mini Converter', true, '1080i50 (1,5G)', false, null],
            [10, '10', 'Philips', '42PFL3606H/13', 'RJA1137004989', true, true, '42"', '42', 'Amazon Basics', false, '1080i50 (1,5G)', false, null],
            [10, '11', 'Samsung', 'OH46D', '0YNZH3DF600013Z', false, true, '46"', '46', 'BMD Mini Converter', true, '1080i50 (1,5G)', true, '11'],
            [10, '12', 'Philips', '32PHS6000/12', 'FZ1A2605043213', true, true, '32"', '13', 'BMD Mini Converter', true, '1080i50 (1,5G)', false, null],
            [10, '13', 'Philips', '32PHS6000/12', 'FZ1A2605043222', true, true, '32"', '37', 'BMD Mini Converter', true, '1080i50 (1,5G)', false, null],
            [10, '14', 'Philips', '32PHS6000/12', 'FZ1A2605043214', true, true, '32"', '38', 'Shuting 3G SDI to HDMI', true, '1080i50 (1,5G)', false, null],
        ];

        foreach ($monitors as $data) {
            [
                $unitId,
                $nummer,
                $manufacturer,
                $model,
                $serial,
                $hasSpeakers,
                $hasHeadphone,
                $screenSize,
                $converterNumber,
                $converterModel,
                $converterAudio,
                $maxInputFormat,
                $hasStand,
                $standNumber,
            ] = $data;

            $item = Item::where('units_id', $unitId)
                ->where('nummer', $nummer)
                ->first();

            if (!$item) {
                $this->warn("Monitor nicht gefunden: Unit {$unitId}, Nr. {$nummer}");
                continue;
            }

            $item->monitorDetail()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'manufacturer' => $manufacturer,
                    'model' => $model,
                    'serial_number' => $serial,
                    'screen_size' => $screenSize,
                    'has_speakers' => $hasSpeakers,
                    'has_headphone' => $hasHeadphone,
                    'converter_number' => $converterNumber,
                    'converter_model' => $converterModel,
                    'converter_audio' => $converterAudio,
                    'max_input_format' => $maxInputFormat,
                    'has_stand' => $hasStand,
                    'stand_number' => $standNumber,
                ]
            );

            $this->info("Importiert: {$nummer} {$manufacturer} {$model}");
        }

        $this->info('Monitor-Details Import abgeschlossen.');

        return self::SUCCESS;
    }
}
