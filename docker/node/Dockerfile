FROM node:20-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    python3 \
    make \
    g++

# Set working directory
WORKDIR /var/www/html

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production=false

# Copy application files
COPY . .

# Build assets
RUN npm run build

# Keep container running (optional, for development)
CMD ["tail", "-f", "/dev/null"]
