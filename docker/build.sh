#!/bin/bash

set -e

echo "Building Docker images..."

# Build PHP image
echo "Building PHP image..."
docker-compose build app

# Build Node image
echo "Building Node image..."
docker-compose build node

echo "Build complete!"
