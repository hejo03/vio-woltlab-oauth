set PACKAGE_NAME=com.hejo03.viooauth
set PACKAGE_TYPES=(acptemplates files templates)

:: Erstelle TAR-Dateien für die Ordner, wenn sie existieren
for %%i in %PACKAGE_TYPES% do (
    if exist .\%%i (
        del .\%%i.tar
        7z a -ttar -mx=9 .\%%i.tar .\%%i\*
    ) else (
        echo %%i Ordner nicht gefunden.
    )
)

:: Lösche alte Tar-Dateien, bevor neue erstellt werden
del .\%PACKAGE_NAME%.tar
del .\%PACKAGE_NAME%.tar.gz

:: Erstelle das Archiv für das gesamte Paket
if exist .\%PACKAGE_NAME% (
    7z a -ttar -mx=9 .\%PACKAGE_NAME%.tar .\* -x!acptemplates -x!files -x!templates -x!%PACKAGE_NAME%.tar -x!%PACKAGE_NAME%.tar.gz -x!.git -x!.gitignore -x!.gitattributes -x!make.sh -x!make.bat -x!.github -x!php_cs.dist -x!.phpcs.xml -x!Readme.md -x!pictures -x!node_modules -x!package-lock.json -x!package.json -x!tsconfig.json -x!ts -x!constants.php
) else (
    echo Fehler: Das Paketverzeichnis %PACKAGE_NAME% wurde nicht gefunden.
)

:: Erstelle die .tar.gz-Datei
if exist .\%PACKAGE_NAME%.tar (
    7z a %PACKAGE_NAME%.tar.gz %PACKAGE_NAME%.tar
    del ".\%PACKAGE_NAME%.tar"
) else (
    echo Fehler: %PACKAGE_NAME%.tar konnte nicht gefunden werden.
)

:: Lösche temporäre Tar-Dateien
for %%i in %PACKAGE_TYPES% do (
    del .\%%i.tar
)
