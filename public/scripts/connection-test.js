/**
 * -------------------------------------------------------------------------
 * manufacturersimports plugin for GLPI
 * Copyright (C) 2015-2026 by the manufacturersimports Development Team.
 *
 * https://github.com/InfotelGLPI/manufacturersimports
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of manufacturersimports.
 *
 * manufacturersimports is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * manufacturersimports is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

/*
 * Connection test button of the manufacturer configuration form (config_form.html.twig).
 * The button carries its settings as data-* attributes: no template value reaches a script.
 */

/**
 * Render the outcome with the message as a text node: the server "message" (and any
 * raw/error body) must never be parsed as HTML.
 */
const showResult = (result, ok, msg) => {
    const span = document.createElement('span');
    span.className = ok ? 'text-success' : 'text-danger';
    const icon = document.createElement('i');
    icon.className = `${ok ? 'ti ti-circle-check' : 'ti ti-circle-x'} me-1`;
    span.append(icon, document.createTextNode(msg !== null && msg !== undefined ? String(msg) : ''));
    result.replaceChildren(span);
};

const fieldValue = (form, name) => {
    const input = (form ?? document).querySelector(`input[name="${CSS.escape(name)}"]`);
    return input !== null ? input.value : '';
};

document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-manufacturersimports-test]');
    if (btn === null) {
        return;
    }

    const result = document.getElementById(btn.dataset.resultId);
    const params = new URLSearchParams({
        test_connection: 1,
        token_url: fieldValue(btn.form, btn.dataset.testField) || btn.dataset.baseUrl,
        test_mode: btn.dataset.testMode,
    });
    if (btn.dataset.testMode === 'oauth') {
        params.set('supplier_key', fieldValue(btn.form, 'supplier_key'));
        params.set('supplier_secret', fieldValue(btn.form, 'supplier_secret'));
    }

    btn.disabled = true;
    const spinner = document.createElement('i');
    spinner.className = 'ti ti-loader-2 ti-spin';
    result.replaceChildren(spinner);

    fetch(btn.dataset.manufacturersimportsTest, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: params,
    })
        .then((response) => response.text())
        .then((text) => {
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                showResult(result, false, text.substring(0, 200));
                return;
            }
            showResult(result, !!data.success, data.message);
        })
        .catch((error) => showResult(result, false, error.message))
        .finally(() => {
            btn.disabled = false;
        });
});
