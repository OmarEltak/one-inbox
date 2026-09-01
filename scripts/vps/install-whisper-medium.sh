#!/usr/bin/env bash
set -euo pipefail

# Run as root on the prod VPS.
# Installs ffmpeg, builds whisper.cpp, downloads the medium ggml model.

apt-get update
apt-get install -y --no-install-recommends \
    build-essential cmake git ffmpeg curl ca-certificates

# Build whisper.cpp
if [[ ! -d /opt/whisper.cpp ]]; then
    git clone https://github.com/ggerganov/whisper.cpp.git /opt/whisper.cpp
fi
cd /opt/whisper.cpp
git pull --ff-only
cmake -B build
cmake --build build --config Release -j 2
install -m 0755 build/bin/whisper-cli /usr/local/bin/whisper.cpp

# Download the medium model (~1.5 GB)
mkdir -p /opt/whisper-models
if [[ ! -f /opt/whisper-models/ggml-medium.bin ]]; then
    curl -L --fail --retry 5 \
        -o /opt/whisper-models/ggml-medium.bin \
        https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-medium.bin
fi

# Sanity check
/usr/local/bin/whisper.cpp --help >/dev/null
echo "whisper.cpp installed OK. Model: /opt/whisper-models/ggml-medium.bin"
