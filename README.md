# manufacturersimports
Plugin manufacturersimports pour GLPI

Ce plugin est sur Transifex - Aidez-nous à le traduire :
https://www.transifex.com/infotelGLPI/GLPI_manufacturersimports/

This plugin is on Transifex - Help us to translate :
https://www.transifex.com/infotelGLPI/GLPI_manufacturersimports/


Ce plugin vous permet d'injecter des informations financières depuis les sites fabricants directement dans GLPI.
> * Vous sélectionnez vos types de matériels et si au préalable vous avez fourni numéro de série et numéro de modèle (selon les fabricants) vous pourrez importer la garantie, la date d'achat, ainsi qu'enregistrer la page hmtl des fabricants.
> * Fonctionne avec Dell, HP, Toshiba, Lenovo (> 1.5.0), Fujitsu-Siemens et Wortmann AG


This plugin allows you to inject financials informations from manufacturers web site files in GLPI.
> * You select your type of equipment in advance and if you provided serial number and model number (different from manufacturers) you can import the warranty, the date of purchase and save the page HMTL manufacturers.
> * Works with Dell, HP, Toshiba, Lenovo (> 1.5.0), Fujitsu-Siemens and Wortmann AG


Fabricants / Manufacturers

> * Pour Dell, il faut maintenant s'enregistrer afin d'avoir une clé API : 

https://techdirect.dell.com

URL dans le plugin :
- Url du fabricant : https://www.dell.com/support/home/product-support/servicetag/
- Adresse API du token d’accès : https://apigtwb2c.us.dell.com/auth/oauth/v2/token
- Adresse API des garanties : https://apigtwb2c.us.dell.com/PROD/sbil/eapi/v5/asset-entitlements?servicetags=

> * Pour HP, il faut maintenant s'enregistrer afin d'avoir une clé API :

Send email to apigarantie.matinfo@hp.com

URL dans le plugin :
- Url du fabricant : https://support.hp.com/fr-fr/check-warranty/
- Adresse API du token d’accès : https://warranty.api.hp.com/oauth/v1/token
- Adresse API des garanties : https://warranty.api.hp.com/productwarranty/v2/queries


> * Pour Lenovo, il faut maintenant s'enregistrer chez Lenovo afin d'avoir une clé API:

URL dans le plugin :
Url du fabricant : https://supportapi.lenovo.com/v2.5/warranty

> * Pour Toshiba

URL dans le plugin :
Url du fabricant : http://aps2.toshiba-tro.de/unit-details-php/unitdetails.aspx?


> * Pour Fujitsu

URL dans le plugin :
Url du fabricant : https://support.ts.fujitsu.com/ProductCheck/Default.aspx?Lng=en&GotoDiv=Warranty/WarrantyStatus&DivID=indexwarranty&GotoUrl=IndexWarranty&RegionID=1&Token=${$i$M$f$u&Ident=
