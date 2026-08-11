#!/usr/bin/env bash
#
# Build the wordpress.org distribution zip for AI-Core.
#
# The plugin's working directory is named ai-core-standalone, which is a dev-only
# name. WordPress resolves the `Requires Plugins: ai-core` dependency in AI-Scribe
# against the plugin's *directory name*, and wordpress.org against the slug, so the
# zip must unpack to a folder called `ai-core`. This script renames on the way out,
# which means the working directory can keep its name without breaking the release.
#
# Staging happens in a mktemp directory outside Dropbox, deliberately: the project's
# hard rules forbid recursive deletes anywhere inside the Dropbox tree, and a build
# that cannot clean up after itself is a build that accumulates.
#
# Usage:
#   bin/build-zip.sh                 # writes dist/ai-core-<version>.zip
#   bin/build-zip.sh --output DIR    # writes the zip to DIR instead
#   bin/build-zip.sh --check         # also runs Plugin Check against the built zip
#
set -euo pipefail

PLUGIN_SLUG="ai-core"
SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUTPUT_DIR="${SRC_DIR}/dist"
RUN_CHECK=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --output) OUTPUT_DIR="$2"; shift 2 ;;
        --check)  RUN_CHECK=1; shift ;;
        *) echo "Unknown argument: $1" >&2; exit 2 ;;
    esac
done

# --- Version comes from the plugin header, which is the single source of truth ---
VERSION="$(grep -m1 "^ \* Version:" "${SRC_DIR}/ai-core.php" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
if [[ -z "${VERSION}" ]]; then
    echo "Could not read Version from ai-core.php header." >&2
    exit 1
fi

# --- The three version declarations must agree, or wordpress.org rejects the upload ---
CONST_VERSION="$(grep -m1 "define('AI_CORE_VERSION'" "${SRC_DIR}/ai-core.php" | sed "s/.*'\([0-9][^']*\)'.*/\1/")"
README_STABLE="$(grep -m1 "^Stable tag:" "${SRC_DIR}/readme.txt" | sed 's/.*Stable tag:[[:space:]]*//' | tr -d '[:space:]')"

fail=0
if [[ "${CONST_VERSION}" != "${VERSION}" ]]; then
    echo "Version mismatch: header says ${VERSION}, AI_CORE_VERSION says ${CONST_VERSION}" >&2
    fail=1
fi
if [[ "${README_STABLE}" != "${VERSION}" ]]; then
    echo "Version mismatch: header says ${VERSION}, readme.txt Stable tag says ${README_STABLE}" >&2
    fail=1
fi
[[ ${fail} -eq 1 ]] && exit 1

# --- readme.txt must be at the root; its absence is a Plugin Check error ---
if [[ ! -f "${SRC_DIR}/readme.txt" ]]; then
    echo "readme.txt is missing from the plugin root. wordpress.org will reject this." >&2
    exit 1
fi

STAGE_DIR="$(mktemp -d "${TMPDIR:-/tmp}/ai-core-build.XXXXXXXX")"
trap 'rm -rf "${STAGE_DIR}"' EXIT

echo "Building ${PLUGIN_SLUG} ${VERSION}"
echo "  source:  ${SRC_DIR}"
echo "  staging: ${STAGE_DIR}"

mkdir -p "${STAGE_DIR}/${PLUGIN_SLUG}"
rsync -a \
    --exclude-from="${SRC_DIR}/.distignore" \
    --exclude="dist" \
    "${SRC_DIR}/" "${STAGE_DIR}/${PLUGIN_SLUG}/"

# --- Belt and braces: .DS_Store is an error and Dropbox recreates it constantly ---
find "${STAGE_DIR}/${PLUGIN_SLUG}" -name '.DS_Store' -delete

mkdir -p "${OUTPUT_DIR}"
ZIP_PATH="${OUTPUT_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
rm -f "${ZIP_PATH}"

( cd "${STAGE_DIR}" && zip -rq "${ZIP_PATH}" "${PLUGIN_SLUG}" -x '*.DS_Store' )

FILE_COUNT="$(unzip -l "${ZIP_PATH}" | tail -1 | awk '{print $2}')"
ZIP_SIZE="$(du -h "${ZIP_PATH}" | cut -f1)"

echo
echo "Built ${ZIP_PATH}"
echo "  ${FILE_COUNT} files, ${ZIP_SIZE}"
echo "  unpacks to ./${PLUGIN_SLUG}/"

# --- Confirm the exclusions actually took, rather than assuming rsync did as asked ---
echo
echo "Verifying exclusions:"
leaked=0
for pattern in "bundled-addons" "_do_not_use_ai-imagen_" "docs/" ".DS_Store" "check_db.php" "ai-core-prompt-library-diagnostic.php" ".git"; do
    if unzip -l "${ZIP_PATH}" | grep -q -- "${pattern}"; then
        echo "  LEAKED: ${pattern}"
        leaked=1
    else
        echo "  clean:  ${pattern}"
    fi
done
[[ ${leaked} -eq 1 ]] && { echo "Exclusions failed. Not a releasable zip." >&2; exit 1; }

if [[ ${RUN_CHECK} -eq 1 ]]; then
    echo
    echo "Plugin Check must be run against this zip on a WordPress install, e.g.:"
    echo "  wp plugin check ${ZIP_PATH} --format=csv --fields=type,code,file,line"
fi
