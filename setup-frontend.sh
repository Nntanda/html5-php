#!/bin/bash

# SACCO Management System - Frontend Setup Script
# This script initializes both React applications (Admin and Client Portal)

echo "Setting up SACCO Management System Frontend Applications..."

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "Error: npm is not installed. Please install Node.js first."
    echo "Visit: https://nodejs.org/"
    exit 1
fi

# Check Node.js version
NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 18 ]; then
    echo "Warning: Node.js version 18 or higher is recommended."
fi

# Create Admin Application
echo ""
echo "Creating Admin Application..."
npm create vite@latest admin-app -- --template react-ts

# Navigate to admin-app and install dependencies
cd admin-app
echo "Installing admin app dependencies..."
npm install

# Install additional dependencies
echo "Installing additional packages..."
npm install react-router-dom axios zustand react-hook-form @hookform/resolvers zod
npm install -D @types/react-router-dom tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Create .env.example
cat > .env.example << 'EOF'
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=SACCO Admin Portal
EOF

cp .env.example .env

# Navigate back to root
cd ..

# Create Client Portal
echo ""
echo "Creating Client Portal..."
npm create vite@latest client-portal -- --template react-ts

# Navigate to client-portal and install dependencies
cd client-portal
echo "Installing client portal dependencies..."
npm install

# Install additional dependencies
echo "Installing additional packages..."
npm install react-router-dom axios zustand react-hook-form @hookform/resolvers zod
npm install -D @types/react-router-dom tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Create .env.example
cat > .env.example << 'EOF'
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=SACCO Client Portal
EOF

cp .env.example .env

# Navigate back to root
cd ..

echo ""
echo "Frontend setup complete!"
echo ""
echo "Next steps:"
echo "Admin Application:"
echo "  cd admin-app && npm run dev"
echo ""
echo "Client Portal:"
echo "  cd client-portal && npm run dev"
