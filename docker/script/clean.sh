#!/usr/bin/env bash

rm -fr .git/.idea >/dev/null 2>/dev/null || true
mv .idea .git/.idea >/dev/null 2>/dev/null || true
git clean -dfx >/dev/null 2>/dev/null || true
mv .git/.idea .idea >/dev/null 2>/dev/null || true
