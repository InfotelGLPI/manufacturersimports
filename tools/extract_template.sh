#!/bin/bash

soft='GLPI - ManufacturersImports plugin'
version='1.9.1'
copyright='INDEPNET Development Team'

xgettext *.php */*.php --copyright-holder='$copyright' --package-name='$soft' --package-version='$version' -o locales/glpi.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po  \
	--keyword=_n:1,2,4t --keyword=__s:1,2t --keyword=__:1,2t --keyword=_e:1,2t --keyword=_x:1c,2,3t \
	--keyword=_ex:1c,2,3t --keyword=_nx:1c,2,3,5t --keyword=_sx:1c,2,3t



