#!/bin/bash
# Script complet : commit git + push + déploiement FTP
# Usage : ./deploy-all.sh "Votre message de commit"

set -e

COMMIT_MSG="$1"
if [ -z "$COMMIT_MSG" ]; then
  echo "Usage : $0 \"Votre message de commit\""
  exit 1
fi

echo "[GIT] Ajout des fichiers..."
git add .

echo "[GIT] Commit..."
git commit -m "$COMMIT_MSG"

echo "[GIT] Push..."
git push

echo "[FTP] Déploiement FTP..."
bash ./deploy-ftp.sh

echo "✅ Commit, push et déploiement FTP terminés !"