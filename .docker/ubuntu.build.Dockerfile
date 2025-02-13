# Use the official Rust image to compile oxipng.
# hadolint ignore=DL3007
FROM rust:latest@sha256:738ae99a3d75623f41e6882566b4ef37e38a9840244a47efd4a0ca22e9628b88 as oxipng-builder

# Install oxipng
RUN cargo install oxipng --version 9.1.3

# hadolint ignore=DL3007
FROM ubuntu:latest@sha256:72297848456d5d37d1262630108ab308d3e9ec7ed1c3286a32fe09856619a782

LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Ubuntu-based image for building a new release."

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /srv/app

# Copy project files
COPY . .

# Install apt-get packages
RUN apt-get update \
    && apt-get upgrade -y \
    && xargs apt-get install --no-install-recommends -y < apt_packages.txt \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy the oxipng binary from the builder stage
COPY --from=oxipng-builder /usr/local/cargo/bin/oxipng /usr/local/bin/oxipng

# Install the rest of dev dependencies
RUN npm ci
