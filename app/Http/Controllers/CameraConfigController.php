<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CameraConfig;

class CameraConfigController extends Controller
{
    public function destroy($id)
{
    $config = CameraConfig::findOrFail($id);
    $config->delete();

    return redirect()->back()->with('success', 'Kamera-Konfiguration erfolgreich entfernt.');
}

}
