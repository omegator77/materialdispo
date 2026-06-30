<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Ersetzt den automatisch generierten Geraetetypen-Bestand durch den lokal
     * kuratierten Stand (zusammengefuehrte/umbenannte Typen). Gruppen werden
     * ueber units.bezeichnung aufgeloest und Items ueber
     * (units.bezeichnung, items.bezeichnung, nummer) gematcht - nicht ueber
     * IDs, damit das auch funktioniert, wenn die Ziel-Datenbank andere
     * units_id/item-IDs hat.
     */
    public function up(): void
    {
        $geraetetypen = array (
  0 => 
  array (
    'units_bezeichnung' => 'Kameras',
    'bezeichnung' => 'LDX 86N Universe',
    'description' => NULL,
  ),
  1 => 
  array (
    'units_bezeichnung' => 'Kameras',
    'bezeichnung' => 'LDX 86N WorldCam',
    'description' => NULL,
  ),
  2 => 
  array (
    'units_bezeichnung' => 'Kameras',
    'bezeichnung' => 'LDX 96 WorldCam Funk',
    'description' => NULL,
  ),
  3 => 
  array (
    'units_bezeichnung' => 'Kameras',
    'bezeichnung' => 'LDX C80 Premiere Funk',
    'description' => NULL,
  ),
  4 => 
  array (
    'units_bezeichnung' => 'Optiken',
    'bezeichnung' => 'Canon 10 Fach',
    'description' => NULL,
  ),
  5 => 
  array (
    'units_bezeichnung' => 'Optiken',
    'bezeichnung' => 'Canon 11 Fach',
    'description' => NULL,
  ),
  6 => 
  array (
    'units_bezeichnung' => 'Optiken',
    'bezeichnung' => 'Canon 14 Fach',
    'description' => NULL,
  ),
  7 => 
  array (
    'units_bezeichnung' => 'Optiken',
    'bezeichnung' => 'Canon 17 Fach',
    'description' => NULL,
  ),
  8 => 
  array (
    'units_bezeichnung' => 'Optiken',
    'bezeichnung' => 'Canon 22 Fach',
    'description' => NULL,
  ),
  9 => 
  array (
    'units_bezeichnung' => 'Optiken',
    'bezeichnung' => 'Fujinon 27 Fach',
    'description' => NULL,
  ),
  10 => 
  array (
    'units_bezeichnung' => 'Optiken',
    'bezeichnung' => 'Fujinon 86x Fach',
    'description' => NULL,
  ),
  11 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Absteller Pro-Touch Pro 5',
    'description' => NULL,
  ),
  12 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Absteller Vinten 250',
    'description' => NULL,
  ),
  13 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Absteller Vision 10',
    'description' => NULL,
  ),
  14 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'ENG',
    'description' => NULL,
  ),
  15 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'HDT',
    'description' => NULL,
  ),
  16 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'HDT-2',
    'description' => NULL,
  ),
  17 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Ospray Pedestal',
    'description' => NULL,
  ),
  18 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Quattro',
    'description' => NULL,
  ),
  19 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Righalter',
    'description' => NULL,
  ),
  20 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Sachtler',
    'description' => NULL,
  ),
  21 => 
  array (
    'units_bezeichnung' => 'Stativ',
    'bezeichnung' => 'Seeced',
    'description' => NULL,
  ),
  22 => 
  array (
    'units_bezeichnung' => 'Stativkopf',
    'bezeichnung' => 'Vector 700',
    'description' => NULL,
  ),
  23 => 
  array (
    'units_bezeichnung' => 'Stativkopf',
    'bezeichnung' => 'Vector 950',
    'description' => NULL,
  ),
  24 => 
  array (
    'units_bezeichnung' => 'Stativkopf',
    'bezeichnung' => 'Vision 250',
    'description' => NULL,
  ),
  25 => 
  array (
    'units_bezeichnung' => 'Stativkopf',
    'bezeichnung' => 'Vision 30',
    'description' => NULL,
  ),
  26 => 
  array (
    'units_bezeichnung' => 'Largelens Adapter',
    'bezeichnung' => 'Canon Adapter',
    'description' => NULL,
  ),
  27 => 
  array (
    'units_bezeichnung' => 'Largelens Adapter',
    'bezeichnung' => 'LDX LL Adapter',
    'description' => NULL,
  ),
  28 => 
  array (
    'units_bezeichnung' => 'Largelens Adapter',
    'bezeichnung' => 'LL Adapter Angnieux',
    'description' => NULL,
  ),
  29 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Antennen Umsetzer Funk Kamera',
    'description' => NULL,
  ),
  30 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Bernadette',
    'description' => NULL,
  ),
  31 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Chantal',
    'description' => NULL,
  ),
  32 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Der Gerät',
    'description' => NULL,
  ),
  33 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Ereca',
    'description' => NULL,
  ),
  34 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Mediornet',
    'description' => NULL,
  ),
  35 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'RockNet',
    'description' => NULL,
  ),
  36 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Shanaya',
    'description' => NULL,
  ),
  37 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'SHED Box',
    'description' => NULL,
  ),
  38 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'SHED System',
    'description' => NULL,
  ),
  39 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Steffi',
    'description' => NULL,
  ),
  40 => 
  array (
    'units_bezeichnung' => 'Stageboxen',
    'bezeichnung' => 'Tio',
    'description' => NULL,
  ),
  41 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'AirCom',
    'description' => NULL,
  ),
  42 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Atmotruhe',
    'description' => NULL,
  ),
  43 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Bandkoffer',
    'description' => NULL,
  ),
  44 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Chrissi',
    'description' => NULL,
  ),
  45 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Mikrofonsplitter',
    'description' => NULL,
  ),
  46 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Riedel CCP',
    'description' => NULL,
  ),
  47 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Schoeps Set',
    'description' => NULL,
  ),
  48 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Toncase',
    'description' => NULL,
  ),
  49 => 
  array (
    'units_bezeichnung' => 'Audio',
    'bezeichnung' => 'Toncase klein',
    'description' => NULL,
  ),
  50 => 
  array (
    'units_bezeichnung' => 'Intercom',
    'bezeichnung' => 'Bolero Koffer',
    'description' => NULL,
  ),
  51 => 
  array (
    'units_bezeichnung' => 'Intercom',
    'bezeichnung' => 'Riedel DCP-1016',
    'description' => NULL,
  ),
  52 => 
  array (
    'units_bezeichnung' => 'Intercom',
    'bezeichnung' => 'Riedel DCP-1116',
    'description' => NULL,
  ),
  53 => 
  array (
    'units_bezeichnung' => 'Intercom',
    'bezeichnung' => 'Riedel DSP-2312 Case',
    'description' => NULL,
  ),
  54 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'BON BEM 182',
    'description' => NULL,
  ),
  55 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'BON BPM 200 LS',
    'description' => NULL,
  ),
  56 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'BON BPM 201 LS',
    'description' => NULL,
  ),
  57 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'BON BTM 170 LS',
    'description' => NULL,
  ),
  58 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'HD2Line HRDP 240-A',
    'description' => NULL,
  ),
  59 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'HD2Line PDP 17,0 W',
    'description' => NULL,
  ),
  60 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'HD2Line PDP 17,3 W',
    'description' => NULL,
  ),
  61 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Lenovo LT2223pWC',
    'description' => NULL,
  ),
  62 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Lilliput BM230-4KS',
    'description' => NULL,
  ),
  63 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Lilliput PVM 210 S',
    'description' => NULL,
  ),
  64 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Samsung LT22B300',
    'description' => NULL,
  ),
  65 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Samsung S22A350H',
    'description' => NULL,
  ),
  66 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Seetec 17 Zoll',
    'description' => NULL,
  ),
  67 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Seetec 21 Zoll',
    'description' => NULL,
  ),
  68 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Seetec Akku',
    'description' => NULL,
  ),
  69 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'Sony LMD-A240',
    'description' => NULL,
  ),
  70 => 
  array (
    'units_bezeichnung' => 'Monitore bis 24 Zoll',
    'bezeichnung' => 'TVLogic LVM 172 W',
    'description' => NULL,
  ),
  71 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Dyon ENTER42PROX2',
    'description' => NULL,
  ),
  72 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Grundig 55VCE222',
    'description' => NULL,
  ),
  73 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Philips 32PHS5000/12',
    'description' => NULL,
  ),
  74 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Philips 32PHS5525/12',
    'description' => NULL,
  ),
  75 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Philips 32PHS5525/13',
    'description' => NULL,
  ),
  76 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Philips 32PHS6000/12',
    'description' => NULL,
  ),
  77 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Philips 42PFL3606H/12',
    'description' => NULL,
  ),
  78 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Philips 42PFL3606H/13',
    'description' => NULL,
  ),
  79 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Samsung GU60AU8079U',
    'description' => NULL,
  ),
  80 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Samsung OH46D',
    'description' => NULL,
  ),
  81 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Sony KD-49X8305C',
    'description' => NULL,
  ),
  82 => 
  array (
    'units_bezeichnung' => 'Monitore über 24 Zoll',
    'bezeichnung' => 'Sony XH81',
    'description' => NULL,
  ),
  83 => 
  array (
    'units_bezeichnung' => 'SMPTE Kabel',
    'bezeichnung' => 'SMPTE 100m',
    'description' => NULL,
  ),
  84 => 
  array (
    'units_bezeichnung' => 'SMPTE Kabel',
    'bezeichnung' => 'SMPTE 110m',
    'description' => NULL,
  ),
  85 => 
  array (
    'units_bezeichnung' => 'SMPTE Kabel',
    'bezeichnung' => 'SMPTE 150m',
    'description' => NULL,
  ),
  86 => 
  array (
    'units_bezeichnung' => 'SMPTE Kabel',
    'bezeichnung' => 'SMPTE 200m',
    'description' => NULL,
  ),
  87 => 
  array (
    'units_bezeichnung' => 'SMPTE Kabel',
    'bezeichnung' => 'SMPTE 300m',
    'description' => NULL,
  ),
  88 => 
  array (
    'units_bezeichnung' => 'SMPTE Kabel',
    'bezeichnung' => 'SMPTE 33m',
    'description' => NULL,
  ),
  89 => 
  array (
    'units_bezeichnung' => 'SMPTE Kabel',
    'bezeichnung' => 'SMPTE 400m',
    'description' => NULL,
  ),
  90 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-LC unbekannt',
    'description' => NULL,
  ),
  91 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 100m',
    'description' => NULL,
  ),
  92 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 120m',
    'description' => NULL,
  ),
  93 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 150m',
    'description' => NULL,
  ),
  94 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 170m',
    'description' => NULL,
  ),
  95 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 200m',
    'description' => NULL,
  ),
  96 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 230m',
    'description' => NULL,
  ),
  97 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 250m',
    'description' => NULL,
  ),
  98 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 290m',
    'description' => NULL,
  ),
  99 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 300m',
    'description' => NULL,
  ),
  100 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 350m',
    'description' => NULL,
  ),
  101 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 360m',
    'description' => NULL,
  ),
  102 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 450m',
    'description' => NULL,
  ),
  103 => 
  array (
    'units_bezeichnung' => 'Glasfaserkabel',
    'bezeichnung' => 'SC-SC 500m',
    'description' => NULL,
  ),
);

        $itemTypeMap = array (
  0 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '22',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  1 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '23',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  2 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '24',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  3 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '25',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  4 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '26',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  5 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N WorldCam',
    'nummer' => '27',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N WorldCam',
  ),
  6 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '28',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  7 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '29',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  8 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '30',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  9 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 86N Universe',
    'nummer' => '31',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 86N Universe',
  ),
  10 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX 96 WorldCam Funk',
    'nummer' => 'Videosys',
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX 96 WorldCam Funk',
  ),
  11 => 
  array (
    'item_units_bezeichnung' => 'Kameras',
    'item_bezeichnung' => 'LDX C80 Premiere Funk',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Kameras',
    'typ_bezeichnung' => 'LDX C80 Premiere Funk',
  ),
  12 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 10x',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 10 Fach',
  ),
  13 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 10x',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 10 Fach',
  ),
  14 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 11x',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 11 Fach',
  ),
  15 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 11x',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 11 Fach',
  ),
  16 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 14x',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 14 Fach',
  ),
  17 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 17x',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 17 Fach',
  ),
  18 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 17x',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 17 Fach',
  ),
  19 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  20 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  21 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  22 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Canon 22x',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Canon 22 Fach',
  ),
  23 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Fujinon 27x',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Fujinon 27 Fach',
  ),
  24 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Fujinon 27x',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Fujinon 27 Fach',
  ),
  25 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Fujinon 27x',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Fujinon 27 Fach',
  ),
  26 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Fujinon 86x',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Fujinon 86x Fach',
  ),
  27 => 
  array (
    'item_units_bezeichnung' => 'Optiken',
    'item_bezeichnung' => 'Fujinon 86x',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Optiken',
    'typ_bezeichnung' => 'Fujinon 86x Fach',
  ),
  28 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vision 30',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vision 30',
  ),
  29 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vision 30',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vision 30',
  ),
  30 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vision 250',
  ),
  31 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vision 250',
  ),
  32 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vision 250',
  ),
  33 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vision 250',
  ),
  34 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vision 250',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vision 250',
  ),
  35 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 700',
  ),
  36 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 700',
  ),
  37 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 700',
  ),
  38 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 700',
  ),
  39 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '1A',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 700',
  ),
  40 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '2A',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 700',
  ),
  41 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 700',
    'nummer' => '3A',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 700',
  ),
  42 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 950',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 950',
  ),
  43 => 
  array (
    'item_units_bezeichnung' => 'Stativkopf',
    'item_bezeichnung' => 'Vector 950',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativkopf',
    'typ_bezeichnung' => 'Vector 950',
  ),
  44 => 
  array (
    'item_units_bezeichnung' => 'Largelens Adapter',
    'item_bezeichnung' => 'LL Adapter Angnieux',
    'nummer' => '1A',
    'typ_units_bezeichnung' => 'Largelens Adapter',
    'typ_bezeichnung' => 'LL Adapter Angnieux',
  ),
  45 => 
  array (
    'item_units_bezeichnung' => 'Largelens Adapter',
    'item_bezeichnung' => 'LL Adapter Angnieux',
    'nummer' => '2A',
    'typ_units_bezeichnung' => 'Largelens Adapter',
    'typ_bezeichnung' => 'LL Adapter Angnieux',
  ),
  46 => 
  array (
    'item_units_bezeichnung' => 'Largelens Adapter',
    'item_bezeichnung' => 'LL Adapter Angnieux',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Largelens Adapter',
    'typ_bezeichnung' => 'LL Adapter Angnieux',
  ),
  47 => 
  array (
    'item_units_bezeichnung' => 'Largelens Adapter',
    'item_bezeichnung' => 'Canon Adapter',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Largelens Adapter',
    'typ_bezeichnung' => 'Canon Adapter',
  ),
  48 => 
  array (
    'item_units_bezeichnung' => 'Largelens Adapter',
    'item_bezeichnung' => 'LDX LL Adapter',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Largelens Adapter',
    'typ_bezeichnung' => 'LDX LL Adapter',
  ),
  49 => 
  array (
    'item_units_bezeichnung' => 'Largelens Adapter',
    'item_bezeichnung' => 'LDX LL Adapter',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Largelens Adapter',
    'typ_bezeichnung' => 'LDX LL Adapter',
  ),
  50 => 
  array (
    'item_units_bezeichnung' => 'Largelens Adapter',
    'item_bezeichnung' => 'LDX LL Adapter',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Largelens Adapter',
    'typ_bezeichnung' => 'LDX LL Adapter',
  ),
  51 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'ENG',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'ENG',
  ),
  52 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'ENG',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'ENG',
  ),
  53 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'ENG',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'ENG',
  ),
  54 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'ENG',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'ENG',
  ),
  55 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'ENG',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'ENG',
  ),
  56 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'ENG',
    'nummer' => '6',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'ENG',
  ),
  57 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'ENG',
    'nummer' => '7',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'ENG',
  ),
  58 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'HDT-2',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'HDT-2',
  ),
  59 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'HDT-2',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'HDT-2',
  ),
  60 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'HDT-2',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'HDT-2',
  ),
  61 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'HDT',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'HDT',
  ),
  62 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'HDT',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'HDT',
  ),
  63 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'HDT',
    'nummer' => '6',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'HDT',
  ),
  64 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'HDT',
    'nummer' => '7',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'HDT',
  ),
  65 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Seeced',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Seeced',
  ),
  66 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Seeced',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Seeced',
  ),
  67 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Absteller Vinten 250',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Absteller Vinten 250',
  ),
  68 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Absteller Vinten 250',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Absteller Vinten 250',
  ),
  69 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Absteller Vinten 250',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Absteller Vinten 250',
  ),
  70 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Absteller Vision 10',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Absteller Vision 10',
  ),
  71 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Absteller Vision 10',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Absteller Vision 10',
  ),
  72 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Absteller Pro-Touch Pro 5',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Absteller Pro-Touch Pro 5',
  ),
  73 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Quattro',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Quattro',
  ),
  74 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Quattro',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Quattro',
  ),
  75 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Quattro',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Quattro',
  ),
  76 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Quattro',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Quattro',
  ),
  77 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Ospray Pedestal',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Ospray Pedestal',
  ),
  78 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Sachtler',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Sachtler',
  ),
  79 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Sachtler',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Sachtler',
  ),
  80 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Set',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Mediornet',
  ),
  81 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Kom',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Mediornet',
  ),
  82 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Prod',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Mediornet',
  ),
  83 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Near',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Mediornet',
  ),
  84 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Mediornet',
    'nummer' => 'Far',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Mediornet',
  ),
  85 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Ereca',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Ereca',
  ),
  86 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Ereca',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Ereca',
  ),
  87 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Tio',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Tio',
  ),
  88 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Toncase',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Toncase',
  ),
  89 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Toncase',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Toncase',
  ),
  90 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Toncase klein',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Toncase klein',
  ),
  91 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Chrissi',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Chrissi',
  ),
  92 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Riedel CCP',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Riedel CCP',
  ),
  93 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'AirCom',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'AirCom',
  ),
  94 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'AirCom',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'AirCom',
  ),
  95 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'AirCom',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'AirCom',
  ),
  96 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'AirCom',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'AirCom',
  ),
  97 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'AirCom',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'AirCom',
  ),
  98 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Atmotruhe',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Atmotruhe',
  ),
  99 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Atmotruhe',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Atmotruhe',
  ),
  100 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  101 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  102 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  103 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  104 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1016',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1016',
  ),
  105 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  106 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  107 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  108 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  109 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DCP-1116',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DCP-1116',
  ),
  110 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DSP-2312 Case',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DSP-2312 Case',
  ),
  111 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Riedel DSP-2312 Case',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Riedel DSP-2312 Case',
  ),
  112 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Bolero Koffer',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Bolero Koffer',
  ),
  113 => 
  array (
    'item_units_bezeichnung' => 'Intercom',
    'item_bezeichnung' => 'Bolero Koffer',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Intercom',
    'typ_bezeichnung' => 'Bolero Koffer',
  ),
  114 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  115 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  116 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BTM 170 LS',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BTM 170 LS',
  ),
  117 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BTM 170 LS',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BTM 170 LS',
  ),
  118 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  119 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BPM 200 LS',
    'nummer' => '6',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BPM 200 LS',
  ),
  120 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BEM 182',
    'nummer' => '7',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BEM 182',
  ),
  121 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BEM 182',
    'nummer' => '8',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BEM 182',
  ),
  122 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BEM 182',
    'nummer' => '9',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BEM 182',
  ),
  123 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'BON BPM 201 LS',
    'nummer' => '10',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'BON BPM 201 LS',
  ),
  124 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '11',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  125 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '12',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  126 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '13',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  127 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'TVLogic LVM 172 W',
    'nummer' => '14',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'TVLogic LVM 172 W',
  ),
  128 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Lilliput PVM 210 S',
    'nummer' => '20',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Lilliput PVM 210 S',
  ),
  129 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Lilliput PVM 210 S',
    'nummer' => '21',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Lilliput PVM 210 S',
  ),
  130 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Lilliput BM230-4KS',
    'nummer' => '22',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Lilliput BM230-4KS',
  ),
  131 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Lilliput BM230-4KS',
    'nummer' => '23',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Lilliput BM230-4KS',
  ),
  132 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Seetec Akku',
    'nummer' => '24',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Seetec Akku',
  ),
  133 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Seetec 21 Zoll',
    'nummer' => '25',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Seetec 21 Zoll',
  ),
  134 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Seetec 21 Zoll',
    'nummer' => '26',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Seetec 21 Zoll',
  ),
  135 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Seetec 17 Zoll',
    'nummer' => '27',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Seetec 17 Zoll',
  ),
  136 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Seetec 17 Zoll',
    'nummer' => '28',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Seetec 17 Zoll',
  ),
  137 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'HD2Line PDP 17,3 W',
    'nummer' => '29',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'HD2Line PDP 17,3 W',
  ),
  138 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'HD2Line PDP 17,3 W',
    'nummer' => '30',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'HD2Line PDP 17,3 W',
  ),
  139 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'HD2Line HRDP 240-A',
    'nummer' => '31',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'HD2Line HRDP 240-A',
  ),
  140 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Lenovo LT2223pWC',
    'nummer' => '32',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Lenovo LT2223pWC',
  ),
  141 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Lenovo LT2223pWC',
    'nummer' => '33',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Lenovo LT2223pWC',
  ),
  142 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Samsung LT22B300',
    'nummer' => '34',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Samsung LT22B300',
  ),
  143 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '35',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  144 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '36',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  145 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '37',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  146 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Samsung S22A350H',
    'nummer' => '38',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Samsung S22A350H',
  ),
  147 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Sony LMD-A240',
    'nummer' => '39',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Sony LMD-A240',
  ),
  148 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'Sony LMD-A240',
    'nummer' => '40',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'Sony LMD-A240',
  ),
  149 => 
  array (
    'item_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'item_bezeichnung' => 'HD2Line PDP 17,0 W',
    'nummer' => '41',
    'typ_units_bezeichnung' => 'Monitore bis 24 Zoll',
    'typ_bezeichnung' => 'HD2Line PDP 17,0 W',
  ),
  150 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Grundig 55VCE222',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Grundig 55VCE222',
  ),
  151 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Samsung GU60AU8079U',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Samsung GU60AU8079U',
  ),
  152 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Sony KD-49X8305C',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Sony KD-49X8305C',
  ),
  153 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Sony KD-49X8305C',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Sony KD-49X8305C',
  ),
  154 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Dyon ENTER42PROX2',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Dyon ENTER42PROX2',
  ),
  155 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Sony XH81',
    'nummer' => '6',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Sony XH81',
  ),
  156 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Philips 32PHS5525/12',
    'nummer' => '7',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Philips 32PHS5525/12',
  ),
  157 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Philips 32PHS5525/13',
    'nummer' => '8',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Philips 32PHS5525/13',
  ),
  158 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Philips 42PFL3606H/12',
    'nummer' => '9',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Philips 42PFL3606H/12',
  ),
  159 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Philips 42PFL3606H/13',
    'nummer' => '10',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Philips 42PFL3606H/13',
  ),
  160 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Samsung OH46D',
    'nummer' => '11',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Samsung OH46D',
  ),
  161 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Philips 32PHS5000/12',
    'nummer' => '12',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Philips 32PHS5000/12',
  ),
  162 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Philips 32PHS6000/12',
    'nummer' => '13',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Philips 32PHS6000/12',
  ),
  163 => 
  array (
    'item_units_bezeichnung' => 'Monitore über 24 Zoll',
    'item_bezeichnung' => 'Philips 32PHS5000/12',
    'nummer' => '14',
    'typ_units_bezeichnung' => 'Monitore über 24 Zoll',
    'typ_bezeichnung' => 'Philips 32PHS5000/12',
  ),
  164 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 200m',
    'nummer' => '1',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 200m',
  ),
  165 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 110m',
    'nummer' => '2',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 110m',
  ),
  166 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '3',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  167 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '4',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  168 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '5',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  169 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '6',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  170 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '7',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  171 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '8',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  172 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '9',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  173 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '10',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  174 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '11',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  175 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 300m',
    'nummer' => '12',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 300m',
  ),
  176 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 300m',
    'nummer' => '13',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 300m',
  ),
  177 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 400m',
    'nummer' => '14',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 400m',
  ),
  178 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '28',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  179 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '29',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  180 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '30',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  181 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '31',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  182 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '32',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  183 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '33',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  184 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '34',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  185 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '35',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  186 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '36',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  187 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '37',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  188 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 33m',
    'nummer' => '38',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 33m',
  ),
  189 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 100m',
    'nummer' => '39',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 100m',
  ),
  190 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 100m',
    'nummer' => '40',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 100m',
  ),
  191 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 100m',
    'nummer' => '41',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 100m',
  ),
  192 => 
  array (
    'item_units_bezeichnung' => 'SMPTE Kabel',
    'item_bezeichnung' => 'SMPTE 150m',
    'nummer' => '42',
    'typ_units_bezeichnung' => 'SMPTE Kabel',
    'typ_bezeichnung' => 'SMPTE 150m',
  ),
  193 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 450m',
    'nummer' => '16',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 450m',
  ),
  194 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 350m',
    'nummer' => '17',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 350m',
  ),
  195 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 360m',
    'nummer' => '18',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 360m',
  ),
  196 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 350m',
    'nummer' => '19',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 350m',
  ),
  197 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 500m',
    'nummer' => '20',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 500m',
  ),
  198 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 500m',
    'nummer' => '21',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 500m',
  ),
  199 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '22',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  200 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 500m',
    'nummer' => '23',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 500m',
  ),
  201 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-LC unbekannt',
    'nummer' => '24',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-LC unbekannt',
  ),
  202 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '26',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  203 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 150m',
    'nummer' => '27',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 150m',
  ),
  204 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '43',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  205 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 150m',
    'nummer' => '44',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 150m',
  ),
  206 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '45',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  207 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 290m',
    'nummer' => '46',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 290m',
  ),
  208 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 250m',
    'nummer' => '47',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 250m',
  ),
  209 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 230m',
    'nummer' => '48',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 230m',
  ),
  210 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 230m',
    'nummer' => '49',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 230m',
  ),
  211 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '50',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  212 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 100m',
    'nummer' => '51',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 100m',
  ),
  213 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 200m',
    'nummer' => '52',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 200m',
  ),
  214 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '53',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  215 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 300m',
    'nummer' => '54',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 300m',
  ),
  216 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 170m',
    'nummer' => '55',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 170m',
  ),
  217 => 
  array (
    'item_units_bezeichnung' => 'Glasfaserkabel',
    'item_bezeichnung' => 'SC-SC 120m',
    'nummer' => '56',
    'typ_units_bezeichnung' => 'Glasfaserkabel',
    'typ_bezeichnung' => 'SC-SC 120m',
  ),
  218 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Schoeps Set',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Schoeps Set',
  ),
  219 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Bandkoffer',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Bandkoffer',
  ),
  220 => 
  array (
    'item_units_bezeichnung' => 'Audio',
    'item_bezeichnung' => 'Mikrofonsplitter',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Audio',
    'typ_bezeichnung' => 'Mikrofonsplitter',
  ),
  221 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'RockNet',
    'nummer' => 'Studio',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'RockNet',
  ),
  222 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'RockNet',
    'nummer' => 'Kom',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'RockNet',
  ),
  223 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'RockNet',
    'nummer' => 'Prod',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'RockNet',
  ),
  224 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Der Gerät',
    'nummer' => 'Set',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Der Gerät',
  ),
  225 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Steffi',
    'nummer' => 'Set',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Steffi',
  ),
  226 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Bernadette',
    'nummer' => 'Set',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Bernadette',
  ),
  227 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Chantal',
    'nummer' => 'Set',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Chantal',
  ),
  228 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Shanaya',
    'nummer' => 'Set',
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Shanaya',
  ),
  229 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'Antennen Umsetzer Funk Kamera',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'Antennen Umsetzer Funk Kamera',
  ),
  230 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'SHED System',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'SHED System',
  ),
  231 => 
  array (
    'item_units_bezeichnung' => 'Stageboxen',
    'item_bezeichnung' => 'SHED Box',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Stageboxen',
    'typ_bezeichnung' => 'SHED Box',
  ),
  232 => 
  array (
    'item_units_bezeichnung' => 'Stativ',
    'item_bezeichnung' => 'Righalter',
    'nummer' => NULL,
    'typ_units_bezeichnung' => 'Stativ',
    'typ_bezeichnung' => 'Righalter',
  ),
);

        $unitIds = DB::table('units')->pluck('id', 'bezeichnung');

        // Alte geraetetyp-IDs sichern, bevor sie gelöscht werden
        $oldGeraetetypenMap = DB::table('geraetetypen')
            ->join('units', 'geraetetypen.units_id', '=', 'units.id')
            ->select('geraetetypen.id', 'units.bezeichnung as units_bezeichnung', 'geraetetypen.bezeichnung')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->id => $row->units_bezeichnung.'|'.$row->bezeichnung]);

        // Anforderungen mit geraetetyp-Referenzen sichern
        $anforderungenOldValues = DB::table('vb_protokoll_anforderungen')
            ->where(fn ($q) => $q
                ->whereNotNull('geraetetyp_id')
                ->orWhereNotNull('lens_geraetetyp_id')
                ->orWhereNotNull('tripod_geraetetyp_id')
                ->orWhereNotNull('tripod_head_geraetetyp_id')
                ->orWhereNotNull('adapter_geraetetyp_id')
            )
            ->select('id', 'geraetetyp_id', 'lens_geraetetyp_id', 'tripod_geraetetyp_id', 'tripod_head_geraetetyp_id', 'adapter_geraetetyp_id')
            ->get();

        DB::transaction(function () use ($geraetetypen, $itemTypeMap, $unitIds, $oldGeraetetypenMap, $anforderungenOldValues) {
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
            $skippedTypen = 0;
            foreach ($geraetetypen as $typ) {
                $unitId = $unitIds[$typ['units_bezeichnung']] ?? null;
                if (! $unitId) {
                    $skippedTypen++;
                    continue;
                }

                $id = DB::table('geraetetypen')->insertGetId([
                    'units_id' => $unitId,
                    'bezeichnung' => $typ['bezeichnung'],
                    'description' => $typ['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $typIds[$typ['units_bezeichnung'].'|'.$typ['bezeichnung']] = $id;
            }

            // old_id → new_id Mapping aufbauen
            $oldToNewId = [];
            foreach ($oldGeraetetypenMap as $oldId => $key) {
                if (isset($typIds[$key])) {
                    $oldToNewId[$oldId] = $typIds[$key];
                }
            }

            $unmatchedItems = 0;
            foreach ($itemTypeMap as $row) {
                $typId = $typIds[$row['typ_units_bezeichnung'].'|'.$row['typ_bezeichnung']] ?? null;
                $unitId = $unitIds[$row['item_units_bezeichnung']] ?? null;
                if (! $typId || ! $unitId) {
                    continue;
                }

                $query = DB::table('items')
                    ->where('units_id', $unitId)
                    ->where('bezeichnung', $row['item_bezeichnung']);

                if ($row['nummer'] === null) {
                    $query->whereNull('nummer');
                } else {
                    $query->where('nummer', $row['nummer']);
                }

                $updated = $query->update(['geraetetyp_id' => $typId]);

                if ($updated === 0) {
                    $unmatchedItems++;
                }
            }

            // Anforderungen re-mappen
            $anforderungsFields = ['geraetetyp_id', 'lens_geraetetyp_id', 'tripod_geraetetyp_id', 'tripod_head_geraetetyp_id', 'adapter_geraetetyp_id'];
            $unmatchedAnforderungen = 0;
            foreach ($anforderungenOldValues as $row) {
                $update = [];
                foreach ($anforderungsFields as $field) {
                    $oldId = $row->{$field};
                    if ($oldId === null) {
                        continue;
                    }
                    if (isset($oldToNewId[$oldId])) {
                        $update[$field] = $oldToNewId[$oldId];
                    } else {
                        $unmatchedAnforderungen++;
                    }
                }
                if (! empty($update)) {
                    DB::table('vb_protokoll_anforderungen')->where('id', $row->id)->update($update);
                }
            }

            if ($skippedTypen > 0 || $unmatchedItems > 0 || $unmatchedAnforderungen > 0) {
                Log::warning("geraetetypen sync: {$skippedTypen} Typen ohne passende Gruppe (units.bezeichnung) uebersprungen, {$unmatchedItems} Items und {$unmatchedAnforderungen} Anforderungsfelder konnten nicht zugeordnet werden.");
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
