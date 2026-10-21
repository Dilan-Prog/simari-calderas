// Selector de fecha y hora (24 h) para los formularios de Tareas. Cualquier <input data-due-picker>
// se convierte en un calendario con hora; el valor real viaja como "YYYY-MM-DD HH:mm".
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.css';
import './task-date-picker.css';

function addDays(n) {
    const d = new Date();
    d.setDate(d.getDate() + n);
    return d;
}

function initPicker(input) {
    const fp = flatpickr(input, {
        locale: Spanish,
        enableTime: true,
        time_24hr: true,
        minuteIncrement: 5,
        defaultHour: 9,
        defaultMinute: 0,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'D d M Y · H:i',
        allowInput: false,
        disableMobile: true,
        monthSelectorType: 'dropdown',
        position: 'auto',
    });
    fp.altInput.placeholder = 'Selecciona fecha y hora';
    fp.altInput.setAttribute('autocomplete', 'off');

    // Atajos para elegir más rápido.
    const quick = document.createElement('div');
    quick.className = 'task-due-quick';
    [['Hoy', 0], ['Mañana', 1], ['En 3 días', 3], ['En 1 semana', 7]].forEach(([label, days]) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        b.addEventListener('click', () => {
            const d = addDays(days);
            const cur = fp.selectedDates[0];
            d.setHours(cur ? cur.getHours() : 9, cur ? cur.getMinutes() : 0, 0, 0);
            fp.setDate(d, true);
        });
        quick.appendChild(b);
    });
    const clear = document.createElement('button');
    clear.type = 'button';
    clear.className = 'is-clear';
    clear.textContent = 'Quitar';
    clear.addEventListener('click', () => fp.clear());
    quick.appendChild(clear);
    fp.altInput.insertAdjacentElement('afterend', quick);
}

document.querySelectorAll('input[data-due-picker]').forEach(initPicker);
