#!/bin/bash

# Usage: ./scripts/update-env.sh KEY VALUE
# Example: ./scripts/update-env.sh DB_PASSWORD secret

set -e

ENV_FILE=".env"

# Ensure .env exists
if [ ! -f "$ENV_FILE" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example "$ENV_FILE"
        echo "📄 Created .env from .env.example"
    else
        touch "$ENV_FILE"
        echo "📄 Created new empty .env"
    fi
fi

if [ "$#" -ne 2 ]; then
    echo "❌ Usage: $0 KEY VALUE"
    exit 1
fi

KEY=$1
VALUE=$2

# Update existing or append new
if grep -q "^${KEY}=" "$ENV_FILE"; then
    # Use | as delimiter in sed to handle values with /
    sed -i "s|^${KEY}=.*|${KEY}=${VALUE}|" "$ENV_FILE"
    echo "✅ Updated ${KEY}"
else
    echo "${KEY}=${VALUE}" >> "$ENV_FILE"
    echo "➕ Added ${KEY}"
fi
