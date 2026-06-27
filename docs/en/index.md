# Documentation — Manufacturersimports Plugin for GLPI

**License:** GNU GPL v3+  
**Authors:** Infotel, Xavier CAILLAUD  
**Repository:** https://github.com/InfotelGLPI/manufacturersimports

---

## Table of Contents

1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Supported manufacturers](#supported-manufacturers)
   - [Dell](#dell)
   - [HP](#hp)
   - [Lenovo](#lenovo)
   - [Toshiba / Dynabook](#toshiba--dynabook)
   - [Fujitsu](#fujitsu)
   - [Wortmann AG (Terra)](#wortmann-ag-terra)
5. [Configuring a manufacturer](#configuring-a-manufacturer)
6. [Usage](#usage)
   - [Manual import from the list](#manual-import-from-the-list)
   - [Import from the asset form](#import-from-the-asset-form)
   - [Resetting an import](#resetting-an-import)
7. [Automatic import (cron)](#automatic-import-cron)
8. [Imported data](#imported-data)
9. [Rights management](#rights-management)
10. [Uninstallation](#uninstallation)

---

## Overview

The **Manufacturersimports** plugin (formerly *suppliertag*) allows you to automatically import warranty and financial information from manufacturer websites directly into the financial information (infocoms) of GLPI assets.

For each asset whose manufacturer is configured and whose serial number is set, the plugin can retrieve:

- The **purchase date**
- The **warranty start date**
- The **warranty duration** (calculated automatically)
- The **supplier** to associate
- Optionally: an **HTML document** from the manufacturer's support page and a **comment line** in the infocoms

Supported asset types: **Computers, Monitors, Network Equipment, Peripherals, Printers**.

---

## Requirements

- GLPI 11.0 to 12.0
- PHP with extensions: **soap**, **curl**, **json**
- For API-based manufacturers (Dell, HP, Lenovo): valid API keys obtained from the manufacturer
- Serial number set on the asset in GLPI
- For some manufacturers (HP, Lenovo): model number (`product_number`) set on the asset model

---

## Installation

1. Download the plugin from [GitHub](https://github.com/InfotelGLPI/manufacturersimports) or the GLPI marketplace.
2. Extract the archive into the `plugins/` (or `marketplace/`) directory of your GLPI installation.
3. Log in to GLPI as an administrator.
4. Go to **Setup › Plugins**, then click **Install** and **Enable** for *Suppliers imports*.

---

## Supported manufacturers

### Dell

Dell uses an **OAuth2 API**. A developer account on [TechDirect Dell](https://techdirect.dell.com) is required.

| Field | Value |
|-------|-------|
| Manufacturer URL | `https://www.dell.com/support/home/product-support/servicetag/` |
| Token URL | `https://apigtwb2c.us.dell.com/auth/oauth/v2/token` |
| Warranty API URL | `https://apigtwb2c.us.dell.com/PROD/sbil/eapi/v5/asset-entitlements?servicetags=` |

→ Enter the **API key** (Client ID) and **API secret** (Client Secret) in the manufacturer configuration.

---

### HP

HP (excluding HPE servers) also uses an **OAuth2 API**. Access is obtained through the HP partner program.

**How to obtain HP API keys:**
1. Submit an access request through your HP account manager or HP sales representative.
2. The account manager submits the request into HP's system.
3. The customer receives an email from `warrantyapi.customers@hp.com` with the steps to complete.
4. The key is **valid for 90 days** and must be renewed via a link sent by email.

| Field | Value |
|-------|-------|
| Manufacturer URL | `https://support.hp.com/check-warranty/` |
| Token URL | `https://warranty.api.hp.com/oauth/v1/token` |
| Warranty API URL | `https://warranty.api.hp.com/productwarranty/v2/queries` |

→ Enter the **API key** (Client ID) and **API secret** (Client Secret).

---

### Lenovo

Lenovo uses an API with a **Client ID key**.

| Field | Value |
|-------|-------|
| Manufacturer URL | `https://supportapi.lenovo.com/v2.5/warranty` |

→ Enter the **API key** in the "API key" field.  
→ The plugin also uses the `product_number` from the asset model as the part number (PN).

---

### Toshiba / Dynabook

Toshiba (Dynabook) works via a **direct URL** with no API authentication.

| Field | Value |
|-------|-------|
| Manufacturer URL | `https://support.dynabook.com/support/warrantyResults?` |

---

### Fujitsu

Fujitsu works via a **direct URL** with no API authentication.

| Field | Value |
|-------|-------|
| Manufacturer URL | `https://support.ts.fujitsu.com/ProductCheck/Default.aspx?Lng=en&GotoDiv=Warranty/WarrantyStatus&DivID=indexwarranty&GotoUrl=IndexWarranty&RegionID=1&Token=${$i$M$f$u&Ident=` |

---

### Wortmann AG (Terra)

Wortmann AG (Terra brand) works via a **direct URL** with no API authentication.

---

## Configuring a manufacturer

Access: **Tools › Suppliers imports** → **Add** button, or click an existing manufacturer

For each manufacturer, create a configuration with the following fields:

| Field | Description |
|-------|-------------|
| **Name** | Manufacturer identifier, chosen from predefined values: Dell, HP, Lenovo, Fujitsu, Toshiba, Wortmann_ag |
| **Entity** | GLPI entity this configuration applies to; can be set as recursive |
| **Recursive** | If checked, the configuration applies to child entities (duplicate configs in child entities are automatically removed) |
| **Associated manufacturer** | GLPI manufacturer (`glpi_manufacturers`) whose assets will be processed |
| **Manufacturer URL** | Base URL of the manufacturer's API or support page |
| **Token URL** | OAuth URL to obtain the access token (Dell, HP only) |
| **Warranty URL** | Specific warranty API URL (Dell, HP only) |
| **API key** | Client ID or API key provided by the manufacturer |
| **API secret** | Client Secret (Dell, HP only) |
| **Default supplier** | GLPI supplier (`glpi_suppliers`) to automatically associate on import |
| **New warranty attached** | Default warranty duration (in months) if the API does not return an end date |
| **Auto add document** | If enabled, saves the manufacturer support page as a GLPI document linked to the asset (not available for Dell and HP) |
| **Document category** | GLPI document category used for automatically added documents |
| **Add a comment line** | If enabled, appends a comment line to the asset's infocoms indicating the import source and date |

### Quick pre-configuration

On the creation form, a **Pre-configure** dropdown prefills the URLs for the selected manufacturer automatically.

### Connection test

After configuration, a **Test connection** button (API manufacturers) or **Test warranty page** button (others) allows you to verify that the URLs and keys are valid.

---

## Usage

### Manual import from the list

Access: **Tools › Suppliers imports**

1. Select a **configured manufacturer** from the dropdown.
2. Select the **asset type** (Computer, Monitor, etc.).
3. Select the **import status**:
   - **Devices not imported**: assets whose warranty has never been imported
   - **Devices already imported**: assets whose warranty was imported successfully
   - **Devices with import error**: assets whose import failed
4. Click **Search**.
5. The list displays matching assets with: name, entity (multi-entity mode), serial number, model number (PN), current infocoms, supplier, warranty duration, link to the manufacturer's website, status.
6. Check the assets to import and use the **mass action**:
   - **Import**: launches the import for the selected assets
   - **Reset the import**: clears the import data to allow re-importing later

The bulk import uses a **progress bar** showing real-time advancement.

---

### Import from the asset form

On the asset form (tab **Financial and administrative information**), if the asset's manufacturer is configured in the plugin:

- A **Manufacturer information** link points to the manufacturer's support page.
- A **Retrieve warranty from manufacturer** button triggers the import for that single asset.

If the asset has not yet been imported, a **visual alert** (orange banner) is shown at the top of the asset form to prompt the user to import the warranty.

---

### Resetting an import

The mass action **Reset the import** deletes the import log entry and the associated GLPI document (if any), allowing the import to be run again from scratch.

---

## Automatic import (cron)

The plugin registers a GLPI cron task **DataWarrantyImport** that automatically imports warranties for Dell and HP.

- The task processes **Computer** assets whose import has not yet been completed, **including** previously failed imports (unlike manual import, which skips past errors).
- Access and configuration: **Setup › Automatic actions › DataWarrantyImport**

---

## Imported data

When an import succeeds, the following fields in the asset's **Financial and administrative information** are updated:

| GLPI field | Imported data |
|------------|---------------|
| **Supplier** | Supplier selected at import time (or default from the configuration) |
| **Purchase date** | Purchase date provided by the manufacturer |
| **Warranty start date** | Warranty start date provided by the manufacturer |
| **Warranty duration** | Calculated automatically (difference between start and end dates) or default from configuration. Set to `-1` (Lifelong) if the calculated duration exceeds 120 months. |
| **Warranty information** | Additional warranty information (type, service level) |
| **Comment** | Line appended if "Add a comment line" is enabled |

A **GLPI document** (HTML page from the manufacturer's site) can be automatically created and linked to the asset (Fujitsu, Toshiba, Lenovo, Wortmann AG only — not Dell or HP).

---

## Rights management

Access: **Administration › Profiles › [profile] › Suppliers imports tab**

| Right | Field | Description |
|-------|-------|-------------|
| **Suppliers imports** | `plugin_manufacturersimports` | Read, create, update, delete manufacturer configurations, and access the import menu |

At installation, the Super-Admin profile receives full rights (`READ + CREATE + UPDATE + PURGE`).

The **Tools › Suppliers imports** menu entry is only visible to profiles with at least `READ` right.

The **configuration page** (linked from the Tools menu) is only accessible to users with GLPI's `config UPDATE` right.

---

## Uninstallation

1. Go to **Setup › Plugins**.
2. Click **Disable** then **Uninstall** for *Suppliers imports*.

> **Warning:** Uninstalling removes all plugin tables (`glpi_plugin_manufacturersimports_configs`, `glpi_plugin_manufacturersimports_logs`, `glpi_plugin_manufacturersimports_models`) and all associated data (manufacturer configurations, import logs).
