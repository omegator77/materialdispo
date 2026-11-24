<?php

namespace App\Services;

use App\Models\Bestellung;

/**
 * Block-Kit-Aufbau der beiden Bestell-Formulare. Block-ID und Action-ID sind
 * jeweils der Feldname, damit SlackInteractionController::fieldValue() die
 * Eingaben aus view.state.values.<feld>.<feld> lesen kann.
 */
class BestellungModals
{
    public const CALLBACK_NEU = 'bestellung_neu';

    public const CALLBACK_SENDUNG = 'bestellung_sendung';

    public static function neu(): array
    {
        return [
            'type' => 'modal',
            'callback_id' => self::CALLBACK_NEU,
            'title' => ['type' => 'plain_text', 'text' => 'Neue Bestellung'],
            'submit' => ['type' => 'plain_text', 'text' => 'Speichern'],
            'close' => ['type' => 'plain_text', 'text' => 'Abbrechen'],
            'blocks' => [
                self::input('bestellt_bei', 'Bestellt bei'),
                self::input('bestellt_was', 'Bestellt was', multiline: true),
                self::input('bezahlt_mit', 'Bezahlt mit'),
            ],
        ];
    }

    public static function sendung(Bestellung $bestellung): array
    {
        $options = [];
        foreach (Bestellung::CARRIERS as $key => $label) {
            $options[] = ['text' => ['type' => 'plain_text', 'text' => $label], 'value' => $key];
        }

        $select = [
            'type' => 'static_select',
            'action_id' => 'versanddienstleister',
            'placeholder' => ['type' => 'plain_text', 'text' => 'Versanddienstleister wählen'],
            'options' => $options,
        ];

        if (isset(Bestellung::CARRIERS[$bestellung->versanddienstleister ?? ''])) {
            $select['initial_option'] = [
                'text' => ['type' => 'plain_text', 'text' => Bestellung::CARRIERS[$bestellung->versanddienstleister]],
                'value' => $bestellung->versanddienstleister,
            ];
        }

        return [
            'type' => 'modal',
            'callback_id' => self::CALLBACK_SENDUNG,
            'private_metadata' => (string) $bestellung->id,
            'title' => ['type' => 'plain_text', 'text' => 'Sendung eintragen'],
            'submit' => ['type' => 'plain_text', 'text' => 'Speichern'],
            'close' => ['type' => 'plain_text', 'text' => 'Abbrechen'],
            'blocks' => [
                [
                    'type' => 'input',
                    'block_id' => 'versanddienstleister',
                    'label' => ['type' => 'plain_text', 'text' => 'Versanddienstleister'],
                    'element' => $select,
                ],
                self::input('sendungsnummer', 'Sendungsnummer', initial: $bestellung->sendungsnummer),
            ],
        ];
    }

    private static function input(string $name, string $label, bool $multiline = false, ?string $initial = null): array
    {
        $element = ['type' => 'plain_text_input', 'action_id' => $name, 'multiline' => $multiline];

        if ($initial !== null && $initial !== '') {
            $element['initial_value'] = $initial;
        }

        return [
            'type' => 'input',
            'block_id' => $name,
            'label' => ['type' => 'plain_text', 'text' => $label],
            'element' => $element,
        ];
    }
}
