#!/bin/bash

echo "Setting up SACCO Admin Application..."

cd admin-app

echo "Installing dependencies..."
npm install

echo "Creating .env file..."
if [ ! -f .env ]; then
  cp .env.example .env
  echo ".env file created. Please update it with your API URL if needed."
fi

echo ""
echo "✅ Admin app setup complete!"
echo ""
echo "To start the development server, run:"
echo "  cd admin-app"
echo "  npm run dev"
echo ""
echo "The app will be available at http://localhost:5173"
