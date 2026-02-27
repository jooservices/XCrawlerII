#!/usr/bin/env sh
set -eu

empty_tree_hash() {
  git hash-object -t tree /dev/null
}

resolve_base() {
  if UPSTREAM="$(git rev-parse --abbrev-ref --symbolic-full-name '@{u}' 2>/dev/null)"; then
    git merge-base HEAD "$UPSTREAM"
    return
  fi

  if git show-ref --verify --quiet refs/remotes/origin/main; then
    git merge-base HEAD origin/main
    return
  fi

  if git show-ref --verify --quiet refs/remotes/origin/master; then
    git merge-base HEAD origin/master
    return
  fi

  if git rev-parse --verify --quiet HEAD~1 >/dev/null; then
    git rev-parse HEAD~1
    return
  fi

  empty_tree_hash
}

BASE="$(resolve_base)"
EMPTY_TREE="$(empty_tree_hash)"

if [ "$BASE" = "$EMPTY_TREE" ]; then
  CHANGED_FILES="$(git diff --name-only "$EMPTY_TREE" HEAD)"
else
  CHANGED_FILES="$(git diff --name-only "$BASE...HEAD")"
fi

if printf '%s\n' "$CHANGED_FILES" | grep -E -q \
'^(package\.json|package-lock\.json|pnpm-lock\.yaml|yarn\.lock|tsconfig[^/]*\.json|vite\.config\.[^/]+|webpack\.[^/]+|rollup\.config\.[^/]+|src/|app/|resources/|public/)'
then
  echo "FE/build changes detected, run build"
  exit 0
fi

echo "No FE/build changes, skip build"
exit 1
