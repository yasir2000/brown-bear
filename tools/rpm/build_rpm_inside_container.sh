#!/usr/bin/env bash

set -ex

TULEAP_SOURCES="/tuleap"
RPM_BUILD="/rpms"

mkdir -p /tmp
mkdir -p "$RPM_BUILD/"{BUILD,RPMS,SOURCES,SPECS,SRPMS}
cp "$TULEAP_SOURCES/"*tar.gz "$RPM_BUILD/SOURCES/"
OS='rhel7' make -C "$TULEAP_SOURCES/tools/rpm" rpm RPM_TMP="$RPM_BUILD"
find "$TULEAP_SOURCES" \( -wholename "$TULEAP_SOURCES/src/additional-packages/*.nix" -o -wholename "$TULEAP_SOURCES/plugins/*/additional-packages/*.nix" \) \
    -type f -print0 | while IFS= read -r -d '' nix_file; do
    echo "Processing $nix_file"
    nix-build "$nix_file" --out-link /tmp/result
    cp -v -r /tmp/result/. "$RPM_BUILD/RPMS/noarch/"
done
chmod -v --recursive u+w "$RPM_BUILD/RPMS/noarch/"