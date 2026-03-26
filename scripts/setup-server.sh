#!/bin/bash
set -e

echo "🚀 Starting server setup..."

# 1. Update and install basic dependencies
sudo apt-get update && sudo apt-get install -y ca-certificates curl gnupg lsb-release

# 2. Install Docker using the official script
if ! command -v docker &> /dev/null; then
    echo "🐋 Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    sudo usermod -aG docker $USER
else
    echo "✅ Docker is already installed."
fi

# 3. Ensure Docker Compose V2 is installed
echo "🐳 Installing Docker Compose V2..."
sudo apt-get update
sudo apt-get install -y docker-compose-plugin

# 4. Setup Firewall
echo "🛡️ Configuring Firewall..."
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
echo "y" | sudo ufw enable

echo "📊 Verifying installation..."
docker --version
docker compose version

echo "🎉 Server setup complete! Please LOG OUT and LOG BACK IN to enable docker permissions."
