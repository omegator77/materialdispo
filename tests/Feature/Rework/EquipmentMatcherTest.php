<?php

use App\Models\Geraetetyp;
use App\Models\Unit;
use App\Services\EquipmentMatcher;

beforeEach(function () {
    $this->kameras = Unit::create(['bezeichnung' => 'Kameras']);
    $this->stative = Unit::create(['bezeichnung' => 'Stativ']);
    $this->ldx86 = Geraetetyp::create(['units_id' => $this->kameras->id, 'bezeichnung' => 'LDX 86N WorldCam']);
    $this->ldx100 = Geraetetyp::create(['units_id' => $this->kameras->id, 'bezeichnung' => 'LDX 100 Elite']);
    $this->sach = Geraetetyp::create(['units_id' => $this->stative->id, 'bezeichnung' => 'Sachtler Ace']);
    $this->matcher = app(EquipmentMatcher::class);
});

test('parseLines understands counts, number ranges and separators', function () {
    $lines = $this->matcher->parseLines("3x LDX, 2 Stativ; Optik #21-23\n5 Sachtler");

    expect($lines)->toHaveCount(4);
    expect($lines[0])->toMatchArray(['anzahl' => 3, 'query' => 'LDX']);
    expect($lines[1])->toMatchArray(['anzahl' => 2, 'query' => 'Stativ']);
    expect($lines[2])->toMatchArray(['anzahl' => 1, 'query' => 'Optik', 'nummern' => '21-23']);
    expect($lines[3])->toMatchArray(['anzahl' => 5, 'query' => 'Sachtler']);
});

test('parseLines handles trailing counts and ignores empty chunks', function () {
    $lines = $this->matcher->parseLines("Stativ 4x\n\n  ;");

    expect($lines)->toHaveCount(1);
    expect($lines[0])->toMatchArray(['anzahl' => 4, 'query' => 'Stativ']);
});

test('an ambiguous query pools all equally good types', function () {
    $m = $this->matcher->match('LDX');

    expect($m['kind'])->toBe('typ')
        ->and($m['geraetetyp_ids'])->toEqualCanonicalizing([$this->ldx86->id, $this->ldx100->id]);
});

test('a specific query resolves to one type', function () {
    $m = $this->matcher->match('ldx 86n');

    expect($m['geraetetyp_ids'])->toBe([$this->ldx86->id]);
});

test('plural and typos still match, group names match as fallback', function () {
    expect($this->matcher->match('Kamera')['unit_id'])->toBe($this->kameras->id);
    expect($this->matcher->match('Sachtlr Ace')['geraetetyp_ids'])->toBe([$this->sach->id]);
    expect($this->matcher->match('Stative')['unit_id'])->toBe($this->stative->id);
});

test('unrelated text does not match', function () {
    expect($this->matcher->match('Kaffeemaschine')['kind'])->toBeNull();
});
