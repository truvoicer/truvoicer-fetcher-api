#!/usr/bin/env bash
#
# switch-tf-db-read-core.sh
# ---------------------------
# Switch between local and production repository for truvoicer/tf-db-read-core.
#
# Usage:
#   ./switch-tf-db-read-core.sh local [version_constraint]
#   ./switch-tf-db-read-core.sh production [--tag [vX.Y.Z]] [version_constraint]
#
# Examples:
#   ./switch-tf-db-read-core.sh local
#   ./switch-tf-db-read-core.sh local dev-feature-branch
#   ./switch-tf-db-read-core.sh production
#   ./switch-tf-db-read-core.sh production --tag
#   ./switch-tf-db-read-core.sh production --tag v1.1.0 v1.1.0  # Constraint matches the new tag
#   ./switch-tf-db-read-core.sh production v1.0.* # Constraint for the update
#

set -e

PACKAGE_NAME="truvoicer/tf-db-read-core"
PACKAGE_DIR="packages/truvoicer/tf-db-read-core"
GITHUB_REPO="https://github.com/truvoicer/tf-db-read-core.git"
REPO_KEY="truvoicer"

usage() {
    echo "Usage:"
    echo "  $0 local [version_constraint]"
    echo "  $0 production [--tag [vX.Y.Z]] [version_constraint]"
    exit 1
}

if [ -z "$1" ]; then
    usage
fi

MODE=$1
CREATE_TAG=false
TAG_NAME=""
VERSION_CONSTRAINT=""

# --- Parse Arguments ---

# Case 1: production [--tag [vX.Y.Z]] [version_constraint]
if [ "$MODE" == "production" ]; then
    if [ "$2" == "--tag" ]; then
        CREATE_TAG=true
        if [ -n "$3" ] && [[ ! "$3" =~ ^-- ]]; then # Check if $3 is a value and not another flag
            TAG_NAME="$3"
            # Version constraint is the 4th arg if tag name is present, otherwise 3rd arg
            if [ -n "$4" ]; then
                VERSION_CONSTRAINT="$4"
            fi
        else
            # Tag name is not present, version constraint is the 3rd arg
            if [ -n "$3" ]; then
                VERSION_CONSTRAINT="$3"
            fi
        fi
    else
        # No --tag, version constraint is the 2nd arg
        if [ -n "$2" ]; then
            VERSION_CONSTRAINT="$2"
        fi
    fi
fi

# Case 2: local [version_constraint]
if [ "$MODE" == "local" ]; then
    # Version constraint is the 2nd arg
    if [ -n "$2" ]; then
        VERSION_CONSTRAINT="$2"
    fi
fi


# --- LOCAL MODE ---
if [ "$MODE" == "local" ]; then
    echo "🔧 Switching $PACKAGE_NAME to LOCAL development mode..."
    composer config repositories.$REPO_KEY path "$PACKAGE_DIR"

    # Default constraint for local mode
    if [ -z "$VERSION_CONSTRAINT" ]; then
        VERSION_CONSTRAINT="dev-develop"
    fi
    echo "📦 Installing local dev version with constraint: $VERSION_CONSTRAINT..."
    composer require "$PACKAGE_NAME:$VERSION_CONSTRAINT" --no-interaction || composer require "$PACKAGE_NAME:@dev" --no-interaction

    echo "⚙️  Optimizing autoload..."
    composer dump-autoload -o
    php artisan optimize:clear

    echo "✅ Composer now uses local path repository: $PACKAGE_DIR"
    exit 0
fi

# --- PRODUCTION MODE ---
if [ "$MODE" == "production" ]; then
    echo "🚀 Preparing $PACKAGE_NAME for PRODUCTION..."

    if [ ! -d "$PACKAGE_DIR/.git" ]; then
        echo "❌ Error: $PACKAGE_DIR is not a Git repository."
        exit 1
    fi

    cd "$PACKAGE_DIR"

    echo "📦 Committing local package changes..."
    git add .
    git diff-index --quiet HEAD || git commit -m "Auto: prepare package for production" || echo "⚠️ No changes to commit."

    echo "⬆️  Pushing current branch to remote..."
    git push origin main

    # --- Handle optional tagging ---
    if [ "$CREATE_TAG" = true ]; then
        echo "🏷️  Tagging enabled..."

        if [ -z "$TAG_NAME" ]; then
            CURRENT_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0")
            IFS='.' read -r major minor patch <<< "${CURRENT_TAG#v}"
            TAG_NAME="v$major.$minor.$((patch + 1))"
            echo "🔢 Auto-generating new tag: $TAG_NAME"
        else
            echo "🔖 Using provided tag: $TAG_NAME"
        fi

        git tag "$TAG_NAME"
        git push origin "$TAG_NAME"
        echo "✅ Tag $TAG_NAME created and pushed."

        # If a tag was created and no explicit version constraint was provided, use the new tag
        if [ -z "$VERSION_CONSTRAINT" ]; then
            VERSION_CONSTRAINT="$TAG_NAME"
        fi
    fi

    cd - >/dev/null

    echo "🔁 Switching Composer to PRODUCTION mode..."
    composer config --unset repositories.$REPO_KEY || true
    composer config repositories.$REPO_KEY vcs "$GITHUB_REPO"

    # Determine the constraint for Composer based on user input or tag creation
    if [ -z "$VERSION_CONSTRAINT" ]; then
        # Default production constraint if no tag was created and no constraint was specified
        echo "📦 Updating to latest remote version (no specific constraint)..."
        composer update "$PACKAGE_NAME" --no-interaction --prefer-dist
    else
        echo "📦 Installing/Updating with constraint $VERSION_CONSTRAINT..."
        composer require "$PACKAGE_NAME:$VERSION_CONSTRAINT" --no-interaction --prefer-dist
    fi

    echo "⚙️  Optimizing autoload..."
    composer dump-autoload -o
    php artisan optimize:clear

    echo ""
    echo "✅ Done! $PACKAGE_NAME pushed to GitHub and Composer is now using the correct source."
    if [ "$CREATE_TAG" = true ]; then
        echo "🔖 Tag: $TAG_NAME"
    fi
    echo "✨ Version Constraint Used: ${VERSION_CONSTRAINT:-latest}" # Show 'latest' if the default 'composer update' was run
    exit 0
fi

usage
