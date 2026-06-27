# Documentation — Plugin Manufacturersimports pour GLPI

**Licence :** GNU GPL v3+  
**Auteurs :** Infotel, Xavier CAILLAUD  
**Dépôt :** https://github.com/InfotelGLPI/manufacturersimports

---

## Table des matières

1. [Présentation](#présentation)
2. [Prérequis](#prérequis)
3. [Installation](#installation)
4. [Fabricants supportés](#fabricants-supportés)
   - [Dell](#dell)
   - [HP](#hp)
   - [Lenovo](#lenovo)
   - [Toshiba / Dynabook](#toshiba--dynabook)
   - [Fujitsu](#fujitsu)
   - [Wortmann AG (Terra)](#wortmann-ag-terra)
5. [Configuration d'un fabricant](#configuration-dun-fabricant)
6. [Utilisation](#utilisation)
   - [Importation manuelle depuis la liste](#importation-manuelle-depuis-la-liste)
   - [Importation depuis la fiche matériel](#importation-depuis-la-fiche-matériel)
   - [Réinitialisation d'une importation](#réinitialisation-dune-importation)
7. [Importation automatique (cron)](#importation-automatique-cron)
8. [Données importées](#données-importées)
9. [Gestion des droits](#gestion-des-droits)
10. [Désinstallation](#désinstallation)

---

## Présentation

Le plugin **Manufacturersimports** (anciennement *suppliertag*) permet d'importer automatiquement les informations financières de garantie depuis les sites web des fabricants directement dans les informations financières (infocoms) de GLPI.

Pour chaque matériel dont le fabricant est configuré et dont le numéro de série est renseigné, le plugin peut récupérer :

- La **date d'achat**
- La **date de début de garantie**
- La **durée de la garantie** (calculée automatiquement)
- Le **fournisseur** à associer
- Optionnellement : un **document HTML** de la page fabricant et une **ligne de commentaire** dans les infocoms

Les types de matériels supportés sont : **Ordinateurs, Moniteurs, Équipements réseau, Périphériques, Imprimantes**.

---

## Prérequis

- GLPI 11.0 à 12.0
- PHP avec les extensions : **soap**, **curl**, **json**
- Pour les fabricants utilisant une API OAuth (Dell, HP, Lenovo) : clés API valides obtenues auprès du fabricant
- Numéro de série renseigné sur le matériel dans GLPI
- Pour certains fabricants (HP, Lenovo) : numéro de modèle (`product_number`) renseigné sur le modèle du matériel

---

## Installation

1. Télécharger le plugin depuis [GitHub](https://github.com/InfotelGLPI/manufacturersimports) ou la marketplace GLPI.
2. Décompresser l'archive dans le dossier `plugins/` (ou `marketplace/`) de votre installation GLPI.
3. Se connecter à GLPI en tant qu'administrateur.
4. Aller dans **Configuration › Plugins**, cliquer sur **Installer** puis **Activer** pour *Suppliers imports*.

---

## Fabricants supportés

### Dell

Dell utilise une **API OAuth2**. Un compte développeur sur [TechDirect Dell](https://techdirect.dell.com) est nécessaire.

| Champ | Valeur |
|-------|--------|
| URL du fabricant | `https://www.dell.com/support/home/product-support/servicetag/` |
| URL du token d'accès | `https://apigtwb2c.us.dell.com/auth/oauth/v2/token` |
| URL API des garanties | `https://apigtwb2c.us.dell.com/PROD/sbil/eapi/v5/asset-entitlements?servicetags=` |

→ Renseigner la **clé API** (Client ID) et le **secret API** (Client Secret) dans la configuration du fabricant.

---

### HP

HP (hors serveurs HPE) utilise également une **API OAuth2**. L'accès est obtenu via le programme partenaire HP.

**Démarche d'obtention des clés HP :**
1. Faire une demande d'accès à l'API via son HP account manager / commercial HP.
2. L'account manager soumet la demande dans le système HP.
3. Le client reçoit un email de `warrantyapi.customers@hp.com` avec les étapes à suivre.
4. La clé est **valable 90 jours** et doit être renouvelée via un lien envoyé par email.

| Champ | Valeur |
|-------|--------|
| URL du fabricant | `https://support.hp.com/fr-fr/check-warranty/` |
| URL du token d'accès | `https://warranty.api.hp.com/oauth/v1/token` |
| URL API des garanties | `https://warranty.api.hp.com/productwarranty/v2/queries` |

→ Renseigner la **clé API** (Client ID) et le **secret API** (Client Secret).

---

### Lenovo

Lenovo utilise une API avec une **clé Client ID**.

| Champ | Valeur |
|-------|--------|
| URL du fabricant | `https://supportapi.lenovo.com/v2.5/warranty` |

→ Renseigner la **clé API** dans le champ « clé API ».  
→ Le plugin utilise également le `product_number` du modèle comme numéro de part number (PN).

---

### Toshiba / Dynabook

Toshiba (Dynabook) fonctionne via une **URL directe** sans authentification API.

| Champ | Valeur |
|-------|--------|
| URL du fabricant | `https://support.dynabook.com/support/warrantyResults?` |

---

### Fujitsu

Fujitsu fonctionne via une **URL directe** sans authentification API.

| Champ | Valeur |
|-------|--------|
| URL du fabricant | `https://support.ts.fujitsu.com/ProductCheck/Default.aspx?Lng=en&GotoDiv=Warranty/WarrantyStatus&DivID=indexwarranty&GotoUrl=IndexWarranty&RegionID=1&Token=${$i$M$f$u&Ident=` |

---

### Wortmann AG (Terra)

Wortmann AG (marque Terra) fonctionne via une **URL directe** sans authentification API.

---

## Configuration d'un fabricant

Accès : **Outils › Suppliers imports** → bouton **Ajouter** ou cliquer sur un fabricant existant

Pour chaque fabricant, on crée une configuration avec les champs suivants :

| Champ | Description |
|-------|-------------|
| **Nom** | Identifiant du fabricant parmi les valeurs prédéfinies (Dell, HP, Lenovo, Fujitsu, Toshiba, Wortmann_ag) |
| **Entité** | Entité GLPI concernée ; la configuration peut être récursive |
| **Récursif** | Si coché, la configuration s'applique aux sous-entités (les configurations en doublon dans les sous-entités sont automatiquement supprimées) |
| **Fabricant associé** | Fabricant GLPI (`glpi_manufacturers`) dont les matériels seront traités |
| **URL du fabricant** | URL de base de l'API ou de la page support du fabricant |
| **URL du token d'accès** | URL OAuth pour obtenir le jeton d'accès (Dell, HP uniquement) |
| **URL des garanties** | URL spécifique de l'API de garanties (Dell, HP uniquement) |
| **Clé API** | Client ID ou clé d'API du fabricant |
| **Secret API** | Client Secret (Dell, HP uniquement) |
| **Fournisseur par défaut** | Fournisseur GLPI (`glpi_suppliers`) à associer automatiquement lors de l'import |
| **Nouvelle garantie attachée** | Durée de garantie par défaut (en mois) si l'API ne fournit pas de date de fin |
| **Ajout automatique de document** | Si activé, enregistre la page HTML du support fabricant comme document GLPI lié au matériel (non disponible pour Dell et HP) |
| **Rubrique de document** | Catégorie de document GLPI utilisée pour les documents ajoutés automatiquement |
| **Ajouter une ligne de commentaire** | Si activé, ajoute une ligne dans le commentaire des infocoms du matériel indiquant la source de l'import et la date |

### Préconfiguration rapide

Sur le formulaire de création, un menu **Préconfigurer** permet de préremplir automatiquement les URLs correspondant au fabricant sélectionné.

### Test de connexion

Après configuration, un bouton **Tester la connexion** (pour les fabricants API) ou **Tester la page garantie** (pour les autres) permet de vérifier que les URLs et clés sont valides.

---

## Utilisation

### Importation manuelle depuis la liste

Accès : **Outils › Suppliers imports**

1. Sélectionner un **fabricant configuré** dans le menu déroulant.
2. Sélectionner le **type de matériel** (Ordinateur, Moniteur, etc.).
3. Sélectionner le **statut d'importation** :
   - **Appareils non importés** : matériels dont la garantie n'a jamais été importée
   - **Appareils déjà importés** : matériels dont la garantie a été importée avec succès
   - **Appareils avec erreur d'import** : matériels dont l'import a échoué
4. Cliquer sur **Rechercher**.
5. La liste affiche les matériels correspondants avec : nom, entité (mode multi-entités), numéro de série, numéro de modèle (PN), infocoms actuels, fournisseur, durée de garantie, lien vers le site fabricant, statut.
6. Cocher les matériels à importer et utiliser l'**action de masse** :
   - **Importer** : lance l'importation pour les matériels sélectionnés
   - **Réinitialiser l'importation** : efface les données d'import pour relancer ultérieurement

L'importation en masse utilise une **barre de progression** affichant l'avancement en temps réel.

---

### Importation depuis la fiche matériel

Sur la fiche d'un matériel (onglet **Informations financières et administratives**), si le fabricant du matériel est configuré dans le plugin :

- Un lien **Informations fabricant** pointe vers la page support du fabricant.
- Un bouton **Récupérer la garantie depuis le fabricant** permet de déclencher l'importation pour ce seul matériel.

Si le matériel n'a pas encore été importé, une **alerte visuelle** (bandeau orange) s'affiche en haut de la fiche pour inviter à importer la garantie.

---

### Réinitialisation d'une importation

L'action de masse **Réinitialiser l'importation** supprime l'entrée dans le journal d'import et le document GLPI associé (si existant), permettant de relancer l'import depuis zéro.

---

## Importation automatique (cron)

Le plugin enregistre une tâche planifiée GLPI **DataWarrantyImport** qui importe automatiquement les garanties pour Dell et HP.

- La tâche traite les matériels de type **Ordinateur** dont l'import n'a pas encore été réalisé, **y compris** les imports précédemment en erreur (contrairement à l'import manuel qui ignore les erreurs passées).
- Accès et configuration : **Configuration › Actions automatiques › DataWarrantyImport**

---

## Données importées

Lors d'un import réussi, les champs suivants des **informations financières et administratives** du matériel sont mis à jour :

| Champ GLPI | Données récupérées |
|------------|--------------------|
| **Fournisseur** | Fournisseur sélectionné lors de l'import (ou par défaut depuis la config) |
| **Date d'achat** | Date d'achat fournie par le fabricant |
| **Date de début de garantie** | Date de début de garantie fournie par le fabricant |
| **Durée de garantie** | Calculée automatiquement (différence entre date de début et date de fin) ou valeur par défaut de la configuration. Mis à `-1` (Illimitée) si la durée calculée dépasse 120 mois. |
| **Informations garantie** | Informations complémentaires sur la garantie (type, niveau de service) |
| **Commentaire** | Ligne ajoutée si « Ajouter une ligne de commentaire » est activé |

Un **document GLPI** (page HTML du site fabricant) peut être automatiquement créé et lié au matériel (Fujitsu, Toshiba, Lenovo, Wortmann AG uniquement — pas Dell ni HP).

---

## Gestion des droits

Accès : **Administration › Profils › [profil] › onglet Suppliers imports**

| Droit | Champ | Description |
|-------|-------|-------------|
| **Suppliers imports** | `plugin_manufacturersimports` | Lecture, création, modification, suppression des configurations fabricants et accès au menu d'import |

À l'installation, le profil Super-Admin reçoit tous les droits (`READ + CREATE + UPDATE + PURGE`).

Le menu **Outils › Suppliers imports** n'est visible que pour les profils ayant au moins le droit `READ`.

La page de **configuration** (lien depuis le menu Outils) n'est accessible qu'aux utilisateurs ayant le droit `config UPDATE` sur GLPI.

---

## Désinstallation

1. Aller dans **Configuration › Plugins**.
2. Cliquer sur **Désactiver** puis **Désinstaller** pour *Suppliers imports*.

> **Attention :** La désinstallation supprime les tables du plugin (`glpi_plugin_manufacturersimports_configs`, `glpi_plugin_manufacturersimports_logs`, `glpi_plugin_manufacturersimports_models`) et toutes les données associées (configurations fabricants, journaux d'import).
