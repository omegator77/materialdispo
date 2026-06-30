<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Ersetzt den automatisch generierten Geraetetypen-Bestand durch den lokal
     * kuratierten Stand (zusammengefuehrte/umbenannte Typen). Items werden ueber
     * (units_id, bezeichnung, nummer) gematcht, nicht ueber IDs, damit das auch
     * funktioniert, wenn die Ziel-Datenbank andere Item-IDs hat.
     */
    public function up(): void
    {
        $geraetetypen = array (
  0 => 
  array (
    'units_id' => 1,
    'bezeichnung' => 'LDX 86N Universe',
    'description' => NULL,
  ),
  1 => 
  array (
    'units_id' => 1,
    'bezeichnung' => 'LDX 86N WorldCam',
    'description' => NULL,
  ),
  2 => 
  array (
    'units_id' => 1,
    'bezeichnung' => 'LDX 96 WorldCam Funk',
    'description' => NULL,
  ),
  3 => 
  array (
    'units_id' => 1,
    'bezeichnung' => 'LDX C80 Premiere Funk',
    'description' => NULL,
  ),
  4 => 
  array (
    'units_id' => 2,
    'bezeichnung' => 'Canon 10 Fach',
    'description' => NULL,
  ),
  5 => 
  array (
    'units_id' => 2,
    'bezeichnung' => 'Canon 11 Fach',
    'description' => NULL,
  ),
  6 => 
  array (
    'units_id' => 2,
    'bezeichnung' => 'Canon 14 Fach',
    'description' => NULL,
  ),
  7 => 
  array (
    'units_id' => 2,
    'bezeichnung' => 'Canon 17 Fach',
    'description' => NULL,
  ),
  8 => 
  array (
    'units_id' => 2,
    'bezeichnung' => 'Canon 22 Fach',
    'description' => NULL,
  ),
  9 => 
  array (
    'units_id' => 2,
    'bezeichnung' => 'Fujinon 27 Fach',
    'description' => NULL,
  ),
  10 => 
  array (
    'units_id' => 2,
    'bezeichnung' => 'Fujinon 86x Fach',
    'description' => NULL,
  ),
  11 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Absteller Pro-Touch Pro 5',
    'description' => NULL,
  ),
  12 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Absteller Vinten 250',
    'description' => NULL,
  ),
  13 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Absteller Vision 10',
    'description' => NULL,
  ),
  14 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'ENG',
    'description' => NULL,
  ),
  15 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'HDT',
    'description' => NULL,
  ),
  16 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'HDT-2',
    'description' => NULL,
  ),
  17 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Ospray Pedestal',
    'description' => NULL,
  ),
  18 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Quattro',
    'description' => NULL,
  ),
  19 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Righalter',
    'description' => NULL,
  ),
  20 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Sachtler',
    'description' => NULL,
  ),
  21 => 
  array (
    'units_id' => 3,
    'bezeichnung' => 'Seeced',
    'description' => NULL,
  ),
  22 => 
  array (
    'units_id' => 4,
    'bezeichnung' => 'Vector 700',
    'description' => NULL,
  ),
  23 => 
  array (
    'units_id' => 4,
    'bezeichnung' => 'Vector 950',
    'description' => NULL,
  ),
  24 => 
  array (
    'units_id' => 4,
    'bezeichnung' => 'Vision 250',
    'description' => NULL,
  ),
  25 => 
  array (
    'units_id' => 4,
    'bezeichnung' => 'Vision 30',
    'description' => NULL,
  ),
  26 => 
  array (
    'units_id' => 5,
    'bezeichnung' => 'Canon Adapter',
    'description' => NULL,
  ),
  27 => 
  array (
    'units_id' => 5,
    'bezeichnung' => 'LDX LL Adapter',
    'description' => NULL,
  ),
  28 => 
  array (
    'units_id' => 5,
    'bezeichnung' => 'LL Adapter Angnieux',
    'description' => NULL,
  ),
  29 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Antennen Umsetzer Funk Kamera',
    'description' => NULL,
  ),
  30 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Bernadette',
    'description' => NULL,
  ),
  31 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Chantal',
    'description' => NULL,
  ),
  32 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Der Gerät',
    'description' => NULL,
  ),
  33 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Ereca',
    'description' => NULL,
  ),
  34 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Mediornet',
    'description' => NULL,
  ),
  35 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'RockNet',
    'description' => NULL,
  ),
  36 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Shanaya',
    'description' => NULL,
  ),
  37 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'SHED Box',
    'description' => NULL,
  ),
  38 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'SHED System',
    'description' => NULL,
  ),
  39 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Steffi',
    'description' => NULL,
  ),
  40 => 
  array (
    'units_id' => 6,
    'bezeichnung' => 'Tio',
    'description' => NULL,
  ),
  41 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'AirCom',
    'description' => NULL,
  ),
  42 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Atmotruhe',
    'description' => NULL,
  ),
  43 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Bandkoffer',
    'description' => NULL,
  ),
  44 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Chrissi',
    'description' => NULL,
  ),
  45 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Mikrofonsplitter',
    'description' => NULL,
  ),
  46 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Riedel CCP',
    'description' => NULL,
  ),
  47 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Schoeps Set',
    'description' => NULL,
  ),
  48 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Toncase',
    'description' => NULL,
  ),
  49 => 
  array (
    'units_id' => 7,
    'bezeichnung' => 'Toncase klein',
    'description' => NULL,
  ),
  50 => 
  array (
    'units_id' => 8,
    'bezeichnung' => 'Bolero Koffer',
    'description' => NULL,
  ),
  51 => 
  array (
    'units_id' => 8,
    'bezeichnung' => 'Riedel DCP-1016',
    'description' => NULL,
  ),
  52 => 
  array (
    'units_id' => 8,
    'bezeichnung' => 'Riedel DCP-1116',
    'description' => NULL,
  ),
  53 => 
  array (
    'units_id' => 8,
    'bezeichnung' => 'Riedel DSP-2312 Case',
    'description' => NULL,
  ),
  54 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'BON BEM 182',
    'description' => NULL,
  ),
  55 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'BON BPM 200 LS',
    'description' => NULL,
  ),
  56 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'BON BPM 201 LS',
    'description' => NULL,
  ),
  57 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'BON BTM 170 LS',
    'description' => NULL,
  ),
  58 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'HD2Line HRDP 240-A',
    'description' => NULL,
  ),
  59 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'HD2Line PDP 17,0 W',
    'description' => NULL,
  ),
  60 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'HD2Line PDP 17,3 W',
    'description' => NULL,
  ),
  61 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Lenovo LT2223pWC',
    'description' => NULL,
  ),
  62 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Lilliput BM230-4KS',
    'description' => NULL,
  ),
  63 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Lilliput PVM 210 S',
    'description' => NULL,
  ),
  64 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Samsung LT22B300',
    'description' => NULL,
  ),
  65 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Samsung S22A350H',
    'description' => NULL,
  ),
  66 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Seetec 17 Zoll',
    'description' => NULL,
  ),
  67 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Seetec 21 Zoll',
    'description' => NULL,
  ),
  68 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Seetec Akku',
    'description' => NULL,
  ),
  69 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'Sony LMD-A240',
    'description' => NULL,
  ),
  70 => 
  array (
    'units_id' => 9,
    'bezeichnung' => 'TVLogic LVM 172 W',
    'description' => NULL,
  ),
  71 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Dyon ENTER42PROX2',
    'description' => NULL,
  ),
  72 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Grundig 55VCE222',
    'description' => NULL,
  ),
  73 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Philips 32PHS5000/12',
    'description' => NULL,
  ),
  74 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Philips 32PHS5525/12',
    'description' => NULL,
  ),
  75 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Philips 32PHS5525/13',
    'description' => NULL,
  ),
  76 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Philips 32PHS6000/12',
    'description' => NULL,
  ),
  77 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Philips 42PFL3606H/12',
    'description' => NULL,
  ),
  78 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Philips 42PFL3606H/13',
    'description' => NULL,
  ),
  79 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Samsung GU60AU8079U',
    'description' => NULL,
  ),
  80 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Samsung OH46D',
    'description' => NULL,
  ),
  81 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Sony KD-49X8305C',
    'description' => NULL,
  ),
  82 => 
  array (
    'units_id' => 10,
    'bezeichnung' => 'Sony XH81',
    'description' => NULL,
  ),
  83 => 
  array (
    'units_id' => 11,
    'bezeichnung' => 'SMPTE 100m',
    'description' => NULL,
  ),
  84 => 
  array (
    'units_id' => 11,
    'bezeichnung' => 'SMPTE 110m',
    'description' => NULL,
  ),
  85 => 
  array (
    'units_id' => 11,
    'bezeichnung' => 'SMPTE 150m',
    'description' => NULL,
  ),
  86 => 
  array (
    'units_id' => 11,
    'bezeichnung' => 'SMPTE 200m',
    'description' => NULL,
  ),
  87 => 
  array (
    'units_id' => 11,
    'bezeichnung' => 'SMPTE 300m',
    'description' => NULL,
  ),
  88 => 
  array (
    'units_id' => 11,
    'bezeichnung' => 'SMPTE 33m',
    'description' => NULL,
  ),
  89 => 
  array (
    'units_id' => 11,
    'bezeichnung' => 'SMPTE 400m',
    'description' => NULL,
  ),
  90 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-LC unbekannt',
    'description' => NULL,
  ),
  91 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 100m',
    'description' => NULL,
  ),
  92 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 120m',
    'description' => NULL,
  ),
  93 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 150m',
    'description' => NULL,
  ),
  94 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 170m',
    'description' => NULL,
  ),
  95 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 200m',
    'description' => NULL,
  ),
  96 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 230m',
    'description' => NULL,
  ),
  97 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 250m',
    'description' => NULL,
  ),
  98 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 290m',
    'description' => NULL,
  ),
  99 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 300m',
    'description' => NULL,
  ),
  100 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 350m',
    'description' => NULL,
  ),
  101 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 360m',
    'description' => NULL,
  ),
  102 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 450m',
    'description' => NULL,
  ),
  103 => 
  array (
    'units_id' => 12,
    'bezeichnung' => 'SC-SC 500m',
    'description' => NULL,
  ),
);

        $itemTypeMap = array (
  0 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '22',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  1 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '23',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  2 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '24',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  3 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '25',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  4 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '26',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  5 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '27',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  6 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '28',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  7 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '29',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  8 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '30',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  9 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '31',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  10 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX 96 WorldCam Funk',
    'nummer' => 'Videosys',
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX 96 WorldCam Funk',
  ),
  11 => 
  array (
    'units_id' => 1,
    'item_bezeichnung' => 'LDX C80 Premiere Funk',
    'nummer' => NULL,
    'typ_units_id' => 1,
    'typ_bezeichnung' => 'LDX C80 Premiere Funk',
  ),
  12 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 10x',
    'nummer' => '1',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 10 Fach',
  ),
  13 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 10x',
    'nummer' => '2',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 10 Fach',
  ),
  14 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 11x',
    'nummer' => '1',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 11 Fach',
  ),
  15 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 11x',
    'nummer' => '2',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 11 Fach',
  ),
  16 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 14x',
    'nummer' => '1',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 14 Fach',
  ),
  17 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 17x',
    'nummer' => '1',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 17 Fach',
  ),
  18 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 17x',
    'nummer' => '2',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 17 Fach',
  ),
  19 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '1',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  20 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '2',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  21 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '3',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  22 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '4',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  23 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Fujinon 27x',
    'nummer' => '1',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Fujinon 27 Fach',
  ),
  24 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Fujinon 27x',
    'nummer' => '2',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Fujinon 27 Fach',
  ),
  25 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Fujinon 27x',
    'nummer' => '3',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Fujinon 27 Fach',
  ),
  26 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Fujinon 86x',
    'nummer' => '1',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Fujinon 86x Fach',
  ),
  27 => 
  array (
    'units_id' => 2,
    'item_bezeichnung' => 'Fujinon 86x',
    'nummer' => '2',
    'typ_units_id' => 2,
    'typ_bezeichnung' => 'Fujinon 86x Fach',
  ),
  28 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vision 30',
    'nummer' => '1',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vision 30',
  ),
  29 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vision 30',
    'nummer' => '2',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vision 30',
  ),
  30 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '1',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vision 250',
  ),
  31 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '2',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vision 250',
  ),
  32 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '3',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vision 250',
  ),
  33 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '4',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vision 250',
  ),
  34 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '5',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vision 250',
  ),
  35 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '1',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 700',
  ),
  36 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '2',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 700',
  ),
  37 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '3',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 700',
  ),
  38 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '4',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 700',
  ),
  39 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '1A',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 700',
  ),
  40 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '2A',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 700',
  ),
  41 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '3A',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 700',
  ),
  42 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 950',
    'nummer' => '1',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 950',
  ),
  43 => 
  array (
    'units_id' => 4,
    'item_bezeichnung' => 'Vector 950',
    'nummer' => '2',
    'typ_units_id' => 4,
    'typ_bezeichnung' => 'Vector 950',
  ),
  44 => 
  array (
    'units_id' => 5,
    'item_bezeichnung' => 'LL Adapter Angnieux',
    'nummer' => '1A',
    'typ_units_id' => 5,
    'typ_bezeichnung' => 'LL Adapter Angnieux',
  ),
  45 => 
  array (
    'units_id' => 5,
    'item_bezeichnung' => 'LL Adapter Angnieux',
    'nummer' => '2A',
    'typ_units_id' => 5,
    'typ_bezeichnung' => 'LL Adapter Angnieux',
  ),
  46 => 
  array (
    'units_id' => 5,
    'item_bezeichnung' => 'LL Adapter Angnieux',
    'nummer' => '3',
    'typ_units_id' => 5,
    'typ_bezeichnung' => 'LL Adapter Angnieux',
  ),
  47 => 
  array (
    'units_id' => 5,
    'item_bezeichnung' => 'Canon Adapter',
    'nummer' => '1',
    'typ_units_id' => 5,
    'typ_bezeichnung' => 'Canon Adapter',
  ),
  48 => 
  array (
    'units_id' => 5,
    'item_bezeichnung' => 'LDX LL Adapter',
    'nummer' => '1',
    'typ_units_id' => 5,
    'typ_bezeichnung' => 'LDX LL Adapter',
  ),
  49 => 
  array (
    'units_id' => 5,
    'item_bezeichnung' => 'LDX LL Adapter',
    'nummer' => '2',
    'typ_units_id' => 5,
    'typ_bezeichnung' => 'LDX LL Adapter',
  ),
  50 => 
  array (
    'units_id' => 5,
    'item_bezeichnung' => 'LDX LL Adapter',
    'nummer' => '3',
    'typ_units_id' => 5,
    'typ_bezeichnung' => 'LDX LL Adapter',
  ),
  51 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'ENG',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'ENG',
  ),
  52 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'ENG',
    'nummer' => '2',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'ENG',
  ),
  53 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'ENG',
    'nummer' => '3',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'ENG',
  ),
  54 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'ENG',
    'nummer' => '4',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'ENG',
  ),
  55 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'ENG',
    'nummer' => '5',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'ENG',
  ),
  56 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'ENG',
    'nummer' => '6',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'ENG',
  ),
  57 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'ENG',
    'nummer' => '7',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'ENG',
  ),
  58 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'HDT-2',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'HDT-2',
  ),
  59 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'HDT-2',
    'nummer' => '2',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'HDT-2',
  ),
  60 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'HDT-2',
    'nummer' => '3',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'HDT-2',
  ),
  61 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'HDT',
    'nummer' => '4',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'HDT',
  ),
  62 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'HDT',
    'nummer' => '5',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'HDT',
  ),
  63 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'HDT',
    'nummer' => '6',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'HDT',
  ),
  64 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'HDT',
    'nummer' => '7',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'HDT',
  ),
  65 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Seeced',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Seeced',
  ),
  66 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Seeced',
    'nummer' => '2',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Seeced',
  ),
  67 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Absteller Vinten 250',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Absteller Vinten 250',
  ),
  68 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Absteller Vinten 250',
    'nummer' => '2',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Absteller Vinten 250',
  ),
  69 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Absteller Vinten 250',
    'nummer' => '3',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Absteller Vinten 250',
  ),
  70 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Absteller Vision 10',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Absteller Vision 10',
  ),
  71 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Absteller Vision 10',
    'nummer' => '2',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Absteller Vision 10',
  ),
  72 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Absteller Pro-Touch Pro 5',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Absteller Pro-Touch Pro 5',
  ),
  73 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Quattro',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Quattro',
  ),
  74 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Quattro',
    'nummer' => '2',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Quattro',
  ),
  75 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Quattro',
    'nummer' => '3',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Quattro',
  ),
  76 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Quattro',
    'nummer' => '4',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Quattro',
  ),
  77 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Ospray Pedestal',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Ospray Pedestal',
  ),
  78 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Sachtler',
    'nummer' => '1',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Sachtler',
  ),
  79 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Sachtler',
    'nummer' => '2',
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Sachtler',
  ),
  80 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Set',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Mediornet',
  ),
  81 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Kom',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Mediornet',
  ),
  82 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Prod',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Mediornet',
  ),
  83 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Near',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Mediornet',
  ),
  84 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Far',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Mediornet',
  ),
  85 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Ereca',
    'nummer' => '1',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Ereca',
  ),
  86 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Ereca',
    'nummer' => '2',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Ereca',
  ),
  87 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Tio',
    'nummer' => '1',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Tio',
  ),
  88 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Toncase',
    'nummer' => '1',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Toncase',
  ),
  89 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Toncase',
    'nummer' => '2',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Toncase',
  ),
  90 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Toncase klein',
    'nummer' => '1',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Toncase klein',
  ),
  91 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Chrissi',
    'nummer' => '1',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Chrissi',
  ),
  92 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Riedel CCP',
    'nummer' => '1',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Riedel CCP',
  ),
  93 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'AirCom',
    'nummer' => '1',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'AirCom',
  ),
  94 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'AirCom',
    'nummer' => '2',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'AirCom',
  ),
  95 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'AirCom',
    'nummer' => '3',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'AirCom',
  ),
  96 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'AirCom',
    'nummer' => '4',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'AirCom',
  ),
  97 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'AirCom',
    'nummer' => '5',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'AirCom',
  ),
  98 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Atmotruhe',
    'nummer' => '1',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Atmotruhe',
  ),
  99 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Atmotruhe',
    'nummer' => '2',
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Atmotruhe',
  ),
  100 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '1',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  101 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '2',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  102 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '3',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  103 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '4',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  104 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '5',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  105 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '1',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  106 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '2',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  107 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '3',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  108 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '4',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  109 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '5',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  110 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DSP-2312 Case',
    'nummer' => '1',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DSP-2312 Case',
  ),
  111 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Riedel DSP-2312 Case',
    'nummer' => '2',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Riedel DSP-2312 Case',
  ),
  112 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Bolero Koffer',
    'nummer' => '1',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Bolero Koffer',
  ),
  113 => 
  array (
    'units_id' => 8,
    'item_bezeichnung' => 'Bolero Koffer',
    'nummer' => '2',
    'typ_units_id' => 8,
    'typ_bezeichnung' => 'Bolero Koffer',
  ),
  114 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '1',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  115 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '2',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  116 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BTM 170 LS',
    'nummer' => '3',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BTM 170 LS',
  ),
  117 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BTM 170 LS',
    'nummer' => '4',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BTM 170 LS',
  ),
  118 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '5',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  119 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '6',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  120 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BEM 182',
    'nummer' => '7',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BEM 182',
  ),
  121 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BEM 182',
    'nummer' => '8',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BEM 182',
  ),
  122 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BEM 182',
    'nummer' => '9',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BEM 182',
  ),
  123 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'BON BPM 201 LS',
    'nummer' => '10',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'BON BPM 201 LS',
  ),
  124 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '11',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  125 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '12',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  126 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '13',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  127 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '14',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  128 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Lilliput PVM 210 S',
    'nummer' => '20',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Lilliput PVM 210 S',
  ),
  129 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Lilliput PVM 210 S',
    'nummer' => '21',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Lilliput PVM 210 S',
  ),
  130 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Lilliput BM230-4KS',
    'nummer' => '22',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Lilliput BM230-4KS',
  ),
  131 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Lilliput BM230-4KS',
    'nummer' => '23',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Lilliput BM230-4KS',
  ),
  132 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Seetec Akku',
    'nummer' => '24',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Seetec Akku',
  ),
  133 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Seetec 21 Zoll',
    'nummer' => '25',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Seetec 21 Zoll',
  ),
  134 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Seetec 21 Zoll',
    'nummer' => '26',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Seetec 21 Zoll',
  ),
  135 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Seetec 17 Zoll',
    'nummer' => '27',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Seetec 17 Zoll',
  ),
  136 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Seetec 17 Zoll',
    'nummer' => '28',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Seetec 17 Zoll',
  ),
  137 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'HD2Line PDP 17,3 W',
    'nummer' => '29',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'HD2Line PDP 17,3 W',
  ),
  138 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'HD2Line PDP 17,3 W',
    'nummer' => '30',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'HD2Line PDP 17,3 W',
  ),
  139 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'HD2Line HRDP 240-A',
    'nummer' => '31',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'HD2Line HRDP 240-A',
  ),
  140 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Lenovo LT2223pWC',
    'nummer' => '32',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Lenovo LT2223pWC',
  ),
  141 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Lenovo LT2223pWC',
    'nummer' => '33',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Lenovo LT2223pWC',
  ),
  142 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Samsung LT22B300',
    'nummer' => '34',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Samsung LT22B300',
  ),
  143 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '35',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  144 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '36',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  145 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '37',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  146 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '38',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  147 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Sony LMD-A240',
    'nummer' => '39',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Sony LMD-A240',
  ),
  148 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'Sony LMD-A240',
    'nummer' => '40',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'Sony LMD-A240',
  ),
  149 => 
  array (
    'units_id' => 9,
    'item_bezeichnung' => 'HD2Line PDP 17,0 W',
    'nummer' => '41',
    'typ_units_id' => 9,
    'typ_bezeichnung' => 'HD2Line PDP 17,0 W',
  ),
  150 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Grundig 55VCE222',
    'nummer' => '1',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Grundig 55VCE222',
  ),
  151 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Samsung GU60AU8079U',
    'nummer' => '2',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Samsung GU60AU8079U',
  ),
  152 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Sony KD-49X8305C',
    'nummer' => '3',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Sony KD-49X8305C',
  ),
  153 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Sony KD-49X8305C',
    'nummer' => '4',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Sony KD-49X8305C',
  ),
  154 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Dyon ENTER42PROX2',
    'nummer' => '5',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Dyon ENTER42PROX2',
  ),
  155 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Sony XH81',
    'nummer' => '6',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Sony XH81',
  ),
  156 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Philips 32PHS5525/12',
    'nummer' => '7',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Philips 32PHS5525/12',
  ),
  157 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Philips 32PHS5525/13',
    'nummer' => '8',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Philips 32PHS5525/13',
  ),
  158 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Philips 42PFL3606H/12',
    'nummer' => '9',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Philips 42PFL3606H/12',
  ),
  159 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Philips 42PFL3606H/13',
    'nummer' => '10',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Philips 42PFL3606H/13',
  ),
  160 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Samsung OH46D',
    'nummer' => '11',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Samsung OH46D',
  ),
  161 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Philips 32PHS5000/12',
    'nummer' => '12',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Philips 32PHS5000/12',
  ),
  162 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Philips 32PHS6000/12',
    'nummer' => '13',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Philips 32PHS6000/12',
  ),
  163 => 
  array (
    'units_id' => 10,
    'item_bezeichnung' => 'Philips 32PHS5000/12',
    'nummer' => '14',
    'typ_units_id' => 10,
    'typ_bezeichnung' => 'Philips 32PHS5000/12',
  ),
  164 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 200m',
    'nummer' => '1',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 200m',
  ),
  165 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 110m',
    'nummer' => '2',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 110m',
  ),
  166 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '3',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  167 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '4',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  168 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '5',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  169 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '6',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  170 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '7',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  171 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '8',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  172 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '9',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  173 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '10',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  174 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '11',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  175 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 300m',
    'nummer' => '12',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 300m',
  ),
  176 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 300m',
    'nummer' => '13',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 300m',
  ),
  177 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '14',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  178 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '28',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  179 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '29',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  180 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '30',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  181 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '31',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  182 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '32',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  183 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '33',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  184 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '34',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  185 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '35',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  186 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '36',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  187 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '37',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  188 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 33m',
    'nummer' => '38',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 33m',
  ),
  189 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 100m',
    'nummer' => '39',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 100m',
  ),
  190 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 100m',
    'nummer' => '40',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 100m',
  ),
  191 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 100m',
    'nummer' => '41',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 100m',
  ),
  192 => 
  array (
    'units_id' => 11,
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '42',
    'typ_units_id' => 11,
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  193 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 450m',
    'nummer' => '16',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 450m',
  ),
  194 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 350m',
    'nummer' => '17',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 350m',
  ),
  195 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 360m',
    'nummer' => '18',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 360m',
  ),
  196 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 350m',
    'nummer' => '19',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 350m',
  ),
  197 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 500m',
    'nummer' => '20',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 500m',
  ),
  198 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 500m',
    'nummer' => '21',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 500m',
  ),
  199 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '22',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  200 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 500m',
    'nummer' => '23',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 500m',
  ),
  201 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-LC unbekannt',
    'nummer' => '24',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-LC unbekannt',
  ),
  202 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '26',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  203 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 150m',
    'nummer' => '27',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 150m',
  ),
  204 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '43',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  205 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 150m',
    'nummer' => '44',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 150m',
  ),
  206 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '45',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  207 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 290m',
    'nummer' => '46',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 290m',
  ),
  208 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 250m',
    'nummer' => '47',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 250m',
  ),
  209 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 230m',
    'nummer' => '48',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 230m',
  ),
  210 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 230m',
    'nummer' => '49',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 230m',
  ),
  211 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '50',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  212 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '51',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  213 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 200m',
    'nummer' => '52',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 200m',
  ),
  214 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '53',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  215 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '54',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  216 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 170m',
    'nummer' => '55',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 170m',
  ),
  217 => 
  array (
    'units_id' => 12,
    'item_bezeichnung' => 'SC-SC 120m',
    'nummer' => '56',
    'typ_units_id' => 12,
    'typ_bezeichnung' => 'SC-SC 120m',
  ),
  218 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Schoeps Set',
    'nummer' => NULL,
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Schoeps Set',
  ),
  219 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Bandkoffer',
    'nummer' => NULL,
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Bandkoffer',
  ),
  220 => 
  array (
    'units_id' => 7,
    'item_bezeichnung' => 'Mikrofonsplitter',
    'nummer' => NULL,
    'typ_units_id' => 7,
    'typ_bezeichnung' => 'Mikrofonsplitter',
  ),
  221 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'RockNet',
    'nummer' => 'Studio',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'RockNet',
  ),
  222 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'RockNet',
    'nummer' => 'Kom',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'RockNet',
  ),
  223 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'RockNet',
    'nummer' => 'Prod',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'RockNet',
  ),
  224 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Der Gerät',
    'nummer' => 'Set',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Der Gerät',
  ),
  225 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Steffi',
    'nummer' => 'Set',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Steffi',
  ),
  226 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Bernadette',
    'nummer' => 'Set',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Bernadette',
  ),
  227 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Chantal',
    'nummer' => 'Set',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Chantal',
  ),
  228 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Shanaya',
    'nummer' => 'Set',
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Shanaya',
  ),
  229 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'Antennen Umsetzer Funk Kamera',
    'nummer' => NULL,
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'Antennen Umsetzer Funk Kamera',
  ),
  230 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'SHED System',
    'nummer' => NULL,
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'SHED System',
  ),
  231 => 
  array (
    'units_id' => 6,
    'item_bezeichnung' => 'SHED Box',
    'nummer' => NULL,
    'typ_units_id' => 6,
    'typ_bezeichnung' => 'SHED Box',
  ),
  232 => 
  array (
    'units_id' => 3,
    'item_bezeichnung' => 'Righalter',
    'nummer' => NULL,
    'typ_units_id' => 3,
    'typ_bezeichnung' => 'Righalter',
  ),
);

        DB::transaction(function () use ($geraetetypen, $itemTypeMap) {
            DB::table('items')->update(['geraetetyp_id' => null]);
            DB::table('vb_protokoll_anforderungen')->update([
                'geraetetyp_id' => null,
                'lens_geraetetyp_id' => null,
                'tripod_geraetetyp_id' => null,
                'tripod_head_geraetetyp_id' => null,
                'adapter_geraetetyp_id' => null,
            ]);
            DB::table('geraetetypen')->delete();

            $typIds = [];
            foreach ($geraetetypen as $typ) {
                $id = DB::table('geraetetypen')->insertGetId([
                    'units_id' => $typ['units_id'],
                    'bezeichnung' => $typ['bezeichnung'],
                    'description' => $typ['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $typIds[$typ['units_id'].'|'.$typ['bezeichnung']] = $id;
            }

            $unmatched = 0;
            foreach ($itemTypeMap as $row) {
                $typId = $typIds[$row['typ_units_id'].'|'.$row['typ_bezeichnung']] ?? null;
                if (! $typId) {
                    continue;
                }

                $query = DB::table('items')
                    ->where('units_id', $row['units_id'])
                    ->where('bezeichnung', $row['item_bezeichnung']);

                if ($row['nummer'] === null) {
                    $query->whereNull('nummer');
                } else {
                    $query->where('nummer', $row['nummer']);
                }

                $updated = $query->update(['geraetetyp_id' => $typId]);

                if ($updated === 0) {
                    $unmatched++;
                }
            }

            if ($unmatched > 0) {
                Log::warning("geraetetypen sync: {$unmatched} Items konnten nicht ueber (units_id, bezeichnung, nummer) zugeordnet werden und blieben ohne Geraetetyp.");
            }
        });
    }

    public function down(): void
    {
        DB::table('items')->update(['geraetetyp_id' => null]);
        DB::table('vb_protokoll_anforderungen')->update([
            'geraetetyp_id' => null,
            'lens_geraetetyp_id' => null,
            'tripod_geraetetyp_id' => null,
            'tripod_head_geraetetyp_id' => null,
            'adapter_geraetetyp_id' => null,
        ]);
        DB::table('geraetetypen')->delete();
    }
};
