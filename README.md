## Manufacturersimports plugin for GLPI

[![License](https://img.shields.io/badge/License-GNU%20v2-blue.svg?style=flat-square)](https://github.com/InfotelGLPI/manufacturersimports/blob/master/LICENSE)
[![Web](https://img.shields.io/badge/Web-Infotel-blue.svg?style=flat-square)](https://blogglpi.infotel.com)
[![Translate](https://img.shields.io/badge/Translate-Transifex-cyan)](https://explore.transifex.com/infotelGLPI/GLPI_manufacturersimports/)

---

### English

This plugin imports warranty and financial information from manufacturer websites directly into GLPI asset infocoms.

* For each asset with a configured manufacturer and a serial number, automatically retrieve the **purchase date**, **warranty start date**, and **warranty duration**.
* Supports **Dell**, **HP**, **Lenovo**, **Toshiba/Dynabook**, **Fujitsu**, and **Wortmann AG (Terra)**.
* Dell and HP use **OAuth2 APIs** (API keys required); Lenovo uses a **Client ID key**; Toshiba, Fujitsu, and Wortmann AG work via direct URL.
* Optionally saves the manufacturer HTML support page as a **GLPI document** linked to the asset.
* Optionally adds a **comment line** to infocoms indicating the import source and date.
* Supports **bulk import** with a real-time progress bar, and **individual import** from the asset form.
* Includes a GLPI **cron task** (`DataWarrantyImport`) for automatic nightly imports of Dell and HP warranties.
* Applicable to: Computers, Monitors, Network Equipment, Peripherals, Printers.

**[Full English documentation →](docs/en/index.md)**

---

### Français

Ce plugin importe les informations de garantie et financières depuis les sites fabricants directement dans les infocoms des matériels GLPI.

* Pour chaque matériel dont le fabricant est configuré et dont le numéro de série est renseigné, récupère automatiquement la **date d’achat**, la **date de début de garantie** et la **durée de garantie**.
* Supporte **Dell**, **HP**, **Lenovo**, **Toshiba/Dynabook**, **Fujitsu** et **Wortmann AG (Terra)**.
* Dell et HP utilisent des **API OAuth2** (clés API requises) ; Lenovo utilise un **Client ID** ; Toshiba, Fujitsu et Wortmann AG fonctionnent par URL directe.
* Enregistre optionnellement la page HTML du support fabricant comme **document GLPI** lié au matériel.
* Ajoute optionnellement une **ligne de commentaire** dans les infocoms indiquant la source et la date de l’import.
* Supporte l’**import en masse** avec barre de progression en temps réel, et l’**import unitaire** depuis la fiche matériel.
* Inclut une **tâche automatique GLPI** (`DataWarrantyImport`) pour l’import nocturne automatique des garanties Dell et HP.
* Applicable à : Ordinateurs, Moniteurs, Équipements réseau, Périphériques, Imprimantes.

**[Documentation complète en français →](docs/fr/index.md)**
