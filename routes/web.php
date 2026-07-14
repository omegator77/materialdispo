<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ItemproductionController;
use App\Http\Controllers\CameraConfigController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\VbProtokollController;
use App\Http\Controllers\GeraetetypController;
use App\Http\Controllers\PackvorgangController;
use App\Http\Controllers\MailingListController;
use App\Http\Controllers\MietvorgangController;
use App\Http\Controllers\MieterController;
use App\Http\Controllers\VermietvorgangController;
use App\Http\Controllers\TestMailController;
use App\Http\Controllers\SlackInteractionController;
use App\Http\Controllers\SettingController;

Route::redirect('/', '/login');

// Slack ruft diesen Endpunkt ohne Session/CSRF-Token auf; Absicherung erfolgt
// über die Signaturprüfung (X-Slack-Signature) im Controller selbst.
Route::post('/slack/interactions', [SlackInteractionController::class, 'handle'])->name('slack.interactions');

// Admin + Benutzer — Schreibzugriff
// Muss vor den read-only Resource-Routes stehen, damit z. B. "units/create"
// nicht von "units/{unit}" (show) abgefangen wird.
Route::middleware(['auth', 'role:admin,user'])->group(function () {
    Route::resource('/units', UnitController::class)->except(['index', 'show']);
    Route::post('/units/{unit}/reorder', [UnitController::class, 'reorder'])->name('units.reorder');
    Route::get('/geraetetypen/create', [GeraetetypController::class, 'create'])->name('geraetetypen.create');
    Route::post('/geraetetypen', [GeraetetypController::class, 'store'])->name('geraetetypen.store');
    Route::get('/geraetetypen/{geraetetyp}/edit', [GeraetetypController::class, 'edit'])->name('geraetetypen.edit');
    Route::put('/geraetetypen/{geraetetyp}', [GeraetetypController::class, 'update'])->name('geraetetypen.update');
    Route::delete('/geraetetypen/{geraetetyp}', [GeraetetypController::class, 'destroy'])->name('geraetetypen.destroy');
    Route::resource('/items', ItemController::class)->except(['index', 'show']);
    Route::resource('/suppliers', SupplierController::class)->except(['index', 'show']);
    Route::resource('/mieter', MieterController::class)->except(['index', 'show']);

    Route::get('productions/templates-search', [ProductionController::class, 'searchTemplates'])->name('productions.searchTemplates');
    Route::resource('/productions', ProductionController::class)->except(['index', 'show']);
    Route::post('productions/{id}/attach-item', [ProductionController::class, 'attachItem'])->name('productions.attachItem');
    Route::delete('productions/{id}/detach-item/{itemId}', [ProductionController::class, 'detachItem'])->name('productions.detachItem');
    Route::get('productions/{production}/import-from/{source}', [ProductionController::class, 'importFrom'])->name('productions.importFrom');
    Route::post('productions/{production}/import-from/{source}', [ProductionController::class, 'storeImport'])->name('productions.storeImport');

    Route::get('/camera-configs/{config}/edit', [CameraConfigController::class, 'edit'])->name('camera-config.edit');
    Route::put('/camera-configs/{config}', [CameraConfigController::class, 'update'])->name('camera-config.update');
    Route::delete('/camera-configs/{config}', [CameraConfigController::class, 'destroy'])->name('camera-config.destroy');
    Route::get('/productions/{production}/camera-config/create', [CameraConfigController::class, 'create'])->name('camera-config.create');
    Route::post('/productions/{production}/camera-config', [CameraConfigController::class, 'store'])->name('camera-config.store');

    Route::get('productions/{production}/vb-protokoll/create', [VbProtokollController::class, 'create'])->name('vb-protokoll.create');
    Route::post('productions/{production}/vb-protokoll', [VbProtokollController::class, 'store'])->name('vb-protokoll.store');
    Route::get('productions/{production}/vb-protokoll/edit', [VbProtokollController::class, 'edit'])->name('vb-protokoll.edit');
    Route::put('productions/{production}/vb-protokoll', [VbProtokollController::class, 'update'])->name('vb-protokoll.update');
    Route::delete('productions/{production}/vb-protokoll', [VbProtokollController::class, 'destroy'])->name('vb-protokoll.destroy');
    Route::delete('vb-protokoll-fotos/{foto}', [VbProtokollController::class, 'destroyFoto'])->name('vb-protokoll.foto.destroy');

    Route::post('productions/{production}/packvorgang/complete', [PackvorgangController::class, 'complete'])->name('packvorgang.complete');
    Route::post('productions/{production}/packvorgang/reopen', [PackvorgangController::class, 'reopen'])->name('packvorgang.reopen');

    Route::resource('/mailing-lists', MailingListController::class)->except(['index', 'show']);
    Route::post('mailing-lists/{mailing_list}/make-default', [MailingListController::class, 'makeDefault'])->name('mailing-lists.makeDefault');

    Route::resource('/mietvorgaenge', MietvorgangController::class)
        ->except(['index', 'show', 'edit'])
        ->parameters(['mietvorgaenge' => 'mietvorgang']);
    Route::get('mietvorgaenge/suggest-bezeichnung', [MietvorgangController::class, 'suggestBezeichnung'])->name('mietvorgaenge.suggestBezeichnung');
    Route::post('mietvorgaenge/{mietvorgang}/attach-items', [MietvorgangController::class, 'attachItems'])->name('mietvorgaenge.attachItems');
    Route::delete('mietvorgaenge/{mietvorgang}/detach-item/{item}', [MietvorgangController::class, 'detachItem'])->name('mietvorgaenge.detachItem');
    Route::post('mietvorgaenge/{mietvorgang}/confirm-transport/{type}', [MietvorgangController::class, 'confirmTransport'])
        ->whereIn('type', ['start', 'end'])
        ->name('mietvorgaenge.confirmTransport');
    Route::delete('mietvorgaenge/{mietvorgang}/confirm-transport/{type}', [MietvorgangController::class, 'reopenTransport'])
        ->whereIn('type', ['start', 'end'])
        ->name('mietvorgaenge.reopenTransport');
    Route::post('mietvorgaenge/{mietvorgang}/confirm-kontrolliert', [MietvorgangController::class, 'confirmKontrolliert'])->name('mietvorgaenge.confirmKontrolliert');
    Route::delete('mietvorgaenge/{mietvorgang}/confirm-kontrolliert', [MietvorgangController::class, 'reopenKontrolliert'])->name('mietvorgaenge.reopenKontrolliert');
    Route::post('mietvorgaenge/{mietvorgang}/confirm-bereit-zur-rueckgabe', [MietvorgangController::class, 'confirmBereitZurRueckgabe'])->name('mietvorgaenge.confirmBereitZurRueckgabe');
    Route::delete('mietvorgaenge/{mietvorgang}/confirm-bereit-zur-rueckgabe', [MietvorgangController::class, 'reopenBereitZurRueckgabe'])->name('mietvorgaenge.reopenBereitZurRueckgabe');

    Route::resource('/vermietvorgaenge', VermietvorgangController::class)
        ->except(['index', 'show', 'edit'])
        ->parameters(['vermietvorgaenge' => 'vermietvorgang']);
    Route::get('vermietvorgaenge/suggest-bezeichnung', [VermietvorgangController::class, 'suggestBezeichnung'])->name('vermietvorgaenge.suggestBezeichnung');
    Route::post('vermietvorgaenge/{vermietvorgang}/attach-items', [VermietvorgangController::class, 'attachItems'])->name('vermietvorgaenge.attachItems');
    Route::delete('vermietvorgaenge/{vermietvorgang}/detach-item/{item}', [VermietvorgangController::class, 'detachItem'])->name('vermietvorgaenge.detachItem');
    Route::post('vermietvorgaenge/{vermietvorgang}/confirm-transport/{type}', [VermietvorgangController::class, 'confirmTransport'])
        ->whereIn('type', ['start', 'end'])
        ->name('vermietvorgaenge.confirmTransport');
    Route::delete('vermietvorgaenge/{vermietvorgang}/confirm-transport/{type}', [VermietvorgangController::class, 'reopenTransport'])
        ->whereIn('type', ['start', 'end'])
        ->name('vermietvorgaenge.reopenTransport');
    Route::post('vermietvorgaenge/{vermietvorgang}/confirm-gerichtet', [VermietvorgangController::class, 'confirmGerichtet'])->name('vermietvorgaenge.confirmGerichtet');
    Route::delete('vermietvorgaenge/{vermietvorgang}/confirm-gerichtet', [VermietvorgangController::class, 'reopenGerichtet'])->name('vermietvorgaenge.reopenGerichtet');
    Route::post('vermietvorgaenge/{vermietvorgang}/confirm-vollstaendig-zurueck', [VermietvorgangController::class, 'confirmVollstaendigZurueck'])->name('vermietvorgaenge.confirmVollstaendigZurueck');
    Route::delete('vermietvorgaenge/{vermietvorgang}/confirm-vollstaendig-zurueck', [VermietvorgangController::class, 'reopenVollstaendigZurueck'])->name('vermietvorgaenge.reopenVollstaendigZurueck');

    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
});

// Alle eingeloggten User (inkl. Viewer) — nur lesend
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('/units', UnitController::class)->only(['index', 'show']);
    Route::get('/geraetetypen', [GeraetetypController::class, 'index'])->name('geraetetypen.index');
    Route::resource('/items', ItemController::class)->only(['index', 'show']);
    Route::resource('/productions', ProductionController::class)->only(['index', 'show']);
    Route::resource('/suppliers', SupplierController::class)->only(['index', 'show']);
    Route::resource('/mieter', MieterController::class)->only(['index', 'show']);
    Route::resource('/mailing-lists', MailingListController::class)->only(['index']);
    Route::resource('/mietvorgaenge', MietvorgangController::class)
        ->only(['index', 'show'])
        ->parameters(['mietvorgaenge' => 'mietvorgang']);
    Route::resource('/vermietvorgaenge', VermietvorgangController::class)
        ->only(['index', 'show'])
        ->parameters(['vermietvorgaenge' => 'vermietvorgang']);

    Route::get('/itemprods', [ItemproductionController::class, 'index'])->name('itemprods');
    Route::get('productions/{id}/pdf', [ProductionController::class, 'generatePDF'])->name('productions.pdf');
    Route::get('mietvorgaenge/{mietvorgang}/pdf', [MietvorgangController::class, 'pdf'])->name('mietvorgaenge.pdf');
    Route::get('vermietvorgaenge/{vermietvorgang}/pdf', [VermietvorgangController::class, 'pdf'])->name('vermietvorgaenge.pdf');
    Route::get('productions/{production}/vb-protokoll', [VbProtokollController::class, 'show'])->name('vb-protokoll.show');
    Route::get('productions/{production}/vb-protokoll/pdf', [VbProtokollController::class, 'generatePDF'])->name('vb-protokoll.pdf');
    Route::get('productions/{production}/vb-protokoll/pdf-abgleich', [VbProtokollController::class, 'generateAbgleichReportPDF'])->name('vb-protokoll.pdf-abgleich');

    Route::get('productions/{production}/packvorgang', [PackvorgangController::class, 'show'])->name('packvorgang.show');
    Route::post('productions/{production}/packvorgang/toggle/{item}', [PackvorgangController::class, 'toggle'])->name('packvorgang.toggle');
    Route::get('productions/{production}/packvorgang/pdf', [PackvorgangController::class, 'pdf'])->name('packvorgang.pdf');

    Route::get('/timeline/items', [TimelineController::class, 'items'])->name('timeline.items');
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin only — Benutzerverwaltung
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('/users', UserController::class);
    Route::post('/test-mail', [TestMailController::class, 'send'])->name('test-mail.send');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
