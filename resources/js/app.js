import './bootstrap';

import Alpine from 'alpinejs';

import flatpickr from 'flatpickr';
import { German } from 'flatpickr/dist/l10n/de.js';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;

Alpine.start();

// Idempotent, damit dynamisch eingefügte Felder (Alpine x-for) nachinitialisiert werden können.
window.initDatepickers = () => {
    document.querySelectorAll('.datepicker:not(.flatpickr-input)').forEach((el) => {
        flatpickr(el, { locale: German, dateFormat: 'd.m.Y', allowInput: true });
    });
};

document.addEventListener('DOMContentLoaded', window.initDatepickers);

// Felder, die Alpine erst nach dem Laden rendert (z. B. Schnellerfassung).
new MutationObserver(() => window.initDatepickers()).observe(document.documentElement, { childList: true, subtree: true });