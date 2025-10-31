import './bootstrap';
import Cleave from 'cleave.js';

document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.getElementById('amount');
    if (amountInput) {
        new Cleave(amountInput, {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand',
            delimiter: '.',
            numeralDecimalMark: ',',
            numeralDecimalScale: 2
        });
    }
});
