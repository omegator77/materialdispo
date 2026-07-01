<?php

namespace App\Models\Concerns;

trait HasReadableActivityDescription
{
    /**
     * Liefert die deutsche Bezeichnung des Modells für Log-Texte (z. B. "Gerät", "Produktion").
     */
    abstract protected function activityNoun(): string;

    /**
     * Liefert das Attribut, das das Modell in Log-Texten identifiziert (z. B. bezeichnung, name).
     */
    protected function activityLabel(): string
    {
        return $this->bezeichnung ?? $this->name ?? '';
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $noun = $this->activityNoun();
        $label = $this->activityLabel();

        return match ($eventName) {
            'created' => "{$noun} \"{$label}\" angelegt",
            'updated' => "{$noun} \"{$label}\" geändert",
            'deleted' => "{$noun} \"{$label}\" gelöscht",
            default => "{$noun} \"{$label}\": {$eventName}",
        };
    }
}
