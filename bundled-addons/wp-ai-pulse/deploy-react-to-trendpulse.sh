#!/bin/bash

# Deploy React App to TrendPulse Repository
# This script extracts the React app and pushes it to the TrendPulse repo

set -e  # Exit on error

echo "🚀 AI-Pulse React App → TrendPulse Repository Deployment"
echo "=========================================================="
echo ""

# Configuration
REACT_APP_SOURCE="bundled-addons/wp-ai-pulse/react-app"
TEMP_DIR="$HOME/temp-trendpulse"
REPO_URL="https://github.com/OpaceDigitalAgency/TrendPulse.git"

# Check if source exists
if [ ! -d "$REACT_APP_SOURCE" ]; then
    echo "❌ Error: React app source not found at $REACT_APP_SOURCE"
    exit 1
fi

echo "📁 Source: $REACT_APP_SOURCE"
echo "📁 Temp directory: $TEMP_DIR"
echo "🔗 Repository: $REPO_URL"
echo ""

# Ask for confirmation
read -p "Continue? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Cancelled"
    exit 1
fi

# Clean up temp directory if it exists
if [ -d "$TEMP_DIR" ]; then
    echo "🧹 Cleaning up existing temp directory..."
    rm -rf "$TEMP_DIR"
fi

# Create temp directory
echo "📁 Creating temp directory..."
mkdir -p "$TEMP_DIR"

# Copy React app files
echo "📋 Copying React app files..."
cp -r "$REACT_APP_SOURCE"/* "$TEMP_DIR/"
cp -r "$REACT_APP_SOURCE"/.* "$TEMP_DIR/" 2>/dev/null || true

# Navigate to temp directory
cd "$TEMP_DIR"

# Initialize git if not already initialized
if [ ! -d ".git" ]; then
    echo "🔧 Initializing git repository..."
    git init
    git branch -M main
fi

# Add remote if not already added
if ! git remote | grep -q "origin"; then
    echo "🔗 Adding remote repository..."
    git remote add origin "$REPO_URL"
else
    echo "🔗 Remote already exists, updating URL..."
    git remote set-url origin "$REPO_URL"
fi

# Stage all files
echo "📦 Staging files..."
git add .

# Show status
echo ""
echo "📊 Git status:"
git status --short

# Ask for commit message
echo ""
read -p "Enter commit message (or press Enter for default): " COMMIT_MSG
if [ -z "$COMMIT_MSG" ]; then
    COMMIT_MSG="Update AI-Pulse React app"
fi

# Commit
echo "💾 Committing changes..."
git commit -m "$COMMIT_MSG" || echo "⚠️  No changes to commit"

# Ask before pushing
echo ""
read -p "Push to GitHub? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🚀 Pushing to GitHub..."
    git push -u origin main
    echo ""
    echo "✅ Successfully pushed to TrendPulse repository!"
    echo ""
    echo "Next steps:"
    echo "1. Go to https://app.netlify.com"
    echo "2. Import the TrendPulse repository"
    echo "3. Set build command: npm run build"
    echo "4. Set publish directory: dist"
    echo "5. Add environment variable: VITE_GOOGLE_API_KEY"
    echo ""
else
    echo "⏸️  Push cancelled. You can push manually later with:"
    echo "   cd $TEMP_DIR"
    echo "   git push -u origin main"
fi

echo ""
echo "📁 React app is ready at: $TEMP_DIR"
echo "✅ Done!"

