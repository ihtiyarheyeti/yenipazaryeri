#!/usr/bin/env bash
set -e

cd httpdocs/frontend-react
npm ci || npm install
npm run build

# Çıktıyı site köküne yayımla
if [ -d dist ]; then
  rsync -a --delete dist/ ../
elif [ -d build ]; then
  rsync -a --delete build/ ../
fi
