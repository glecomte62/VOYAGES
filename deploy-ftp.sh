#!/bin/bash
# Script de déploiement FTP pour le projet VOYAGES
# Nécessite lftp (brew install lftp)

HOST="ftp.kica7829.odns.fr"
USER="voyages@kica7829.odns.fr"
PASS="Corvus2024@LFQJ"
REMOTE_DIR="/"
LOCAL_DIR="$(pwd)"

# Exclure certains dossiers/fichiers (ex: .git, logs, cache)
EXCLUDES="--exclude-glob .git* --exclude-glob logs/* --exclude-glob cache/*"

lftp -c "set ftp:list-options -a;
open -u $USER,$PASS $HOST;
mirror --reverse --delete --verbose $EXCLUDES $LOCAL_DIR $REMOTE_DIR"

echo "Déploiement FTP terminé !"