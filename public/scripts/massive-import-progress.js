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
 * Progress page of the massive import (massive_import_progress.html.twig): posts the
 * selected devices to the run endpoint and follows it with the core ProgressIndicator.
 * Every value comes from the data-* attributes of the container.
 */

const container = document.getElementById('massive-import-progress');

if (container !== null) {
    const params = JSON.parse(container.dataset.params);

    const form_data = new FormData();
    for (const [key, val] of Object.entries(params)) {
        if (key === 'item' && val !== null && typeof val === 'object') {
            for (const [id, checked] of Object.entries(val)) {
                form_data.append(`item[${id}]`, checked);
            }
        } else {
            form_data.append(key, val ?? '');
        }
    }
    form_data.append('_glpi_csrf_token', getAjaxCsrfToken());

    const { ProgressIndicator } = await import(`${CFG_GLPI.root_doc}/js/modules/ProgressIndicator.js`);

    const add_back_button = () => {
        const link       = document.createElement('a');
        link.href        = container.dataset.backUrl;
        link.className   = 'btn btn-secondary mt-3';
        link.textContent = container.dataset.backLabel;
        container.appendChild(link);
    };

    const pi = new ProgressIndicator({
        container,
        request: new Request(container.dataset.runUrl, { method: 'POST', body: form_data }),
        success_callback: add_back_button,
        error_callback:   add_back_button,
    });

    pi.start();
}
