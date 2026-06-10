import './bootstrap';

import Alpine from 'alpinejs';

import flatpickr from 'flatpickr';
import { German } from 'flatpickr/dist/l10n/de.js';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    flatpickr('.datepicker', {
        locale: German,
        dateFormat: 'd.m.Y',
        allowInput: true,
    });
});