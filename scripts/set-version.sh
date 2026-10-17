#!/usr/bin/env bash
# Writes a release version into the three places WordPress and CI read it from:
# the plugin header, the LINKFORTY_VERSION constant, and readme.txt's Stable tag.
# Called by semantic-release (.releaserc.json) before the release commit.
set -euo pipefail

version="${1:?usage: scripts/set-version.sh <version>}"
if ! [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]]; then
  echo "not a semantic version: $version" >&2
  exit 1
fi

root="$(cd "$(dirname "$0")/.." && pwd)"

# perl -pi behaves the same with GNU and BSD tooling, unlike sed -i.
perl -pi -e "s/^(\s*\*\s*Version:\s*).*/\${1}$version/" "$root/linkforty.php"
perl -pi -e "s/define\( 'LINKFORTY_VERSION', '[^']*' \)/define( 'LINKFORTY_VERSION', '$version' )/" "$root/linkforty.php"
perl -pi -e "s/^Stable tag:.*/Stable tag: $version/" "$root/readme.txt"

header=$(grep -E '^\s*\*\s*Version:' "$root/linkforty.php" | awk '{print $NF}')
constant=$(grep -E "define\( 'LINKFORTY_VERSION'" "$root/linkforty.php" | sed -E "s/.*'([^']+)'.*/\1/")
stable=$(grep -E '^Stable tag:' "$root/readme.txt" | awk '{print $NF}')
test "$header" = "$version" && test "$constant" = "$version" && test "$stable" = "$version" || {
  echo "version not written everywhere: header=$header constant=$constant stable=$stable" >&2
  exit 1
}
echo "version set to $version"
