#!/bin/bash

# Check if a version argument is provided
if [ -z "$1" ]; then
  echo "Usage: $0 <version>"
  exit 1
fi

VERSION=$1

# Update composer.json version
sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json

## Commit the change
git add composer.json
git commit -m "Bump version to $VERSION"

# Create a new Git tag
git tag -a "v$VERSION" -m "Release version $VERSION"

# Push the changes and the tag
git push origin main
git push origin "v$VERSION"
