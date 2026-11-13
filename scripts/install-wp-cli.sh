#!/usr/bin/env bash
# WP-CLI 2.12.0, verified against its official release SHA-512 asset.
set -euo pipefail
cs_tools_dir="${CS_TOOLS_DIR:-/tmp/codeslot-tools}"
cs_wp_sha512='be928f6b8ca1e8dfb9d2f4b75a13aa4aee0896f8a9a0a1c45cd5d2c98605e6172e6d014dda2e27f88c98befc16c040cbb2bd1bfa121510ea5cdf5f6a30fe8832'
mkdir -p "$cs_tools_dir"
if ! printf '%s  %s\n' "$cs_wp_sha512" "$cs_tools_dir/wp" | sha512sum --check --status; then
  curl --fail --location --silent --show-error --retry 3 \
    https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar \
    -o "$cs_tools_dir/wp"
fi
printf '%s  %s\n' "$cs_wp_sha512" "$cs_tools_dir/wp" | sha512sum --check
chmod 755 "$cs_tools_dir/wp"
"$cs_tools_dir/wp" --version
if [ -n "${GITHUB_PATH:-}" ]; then
  printf '%s\n' "$cs_tools_dir" >> "$GITHUB_PATH"
fi
