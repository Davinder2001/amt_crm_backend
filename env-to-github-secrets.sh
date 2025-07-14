#!/bin/bash

# Usage: ./env-to-github-secrets.sh <github-repo>
# Example: ./env-to-github-secrets.sh yourusername/yourrepo

if [ -z "$1" ]; then
  echo "Usage: $0 <github-repo>"
  exit 1
fi

REPO="$1"
ENV_FILE=".env.docker"

if [ ! -f "$ENV_FILE" ]; then
  echo "$ENV_FILE not found!"
  exit 1
fi

while IFS='=' read -r key value
do
  # Skip comments and empty lines
  if [[ "$key" =~ ^#.*$ ]] || [[ -z "$key" ]]; then
    continue
  fi
  # Remove possible quotes and whitespace
  key=$(echo "$key" | xargs)
  value=$(echo "$value" | sed 's/^\(["'\'']\)//;s/["'\'']$//' | xargs)
  if [ -n "$key" ]; then
    echo "Setting secret: $key"
    gh secret set "$key" -b"$value" -R "$REPO"
  fi
done < "$ENV_FILE" 