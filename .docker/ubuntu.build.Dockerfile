# Use an official Rust image to compile oxipng.
FROM rust:1-bookworm as oxipng-builder

# Install oxipng
RUN cargo install oxipng --version 9.0.0

FROM ubuntu:23.10

LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Ubuntu image for building a new release."

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /srv/app

# Copy project files
COPY . .

# Install apt-get packages
RUN apt-get update \
    && apt-get upgrade -y \
    && xargs apt-get install --no-install-recommends -y < apt-packages.txt \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy the oxipng binary from the builder stage
COPY --from=oxipng-builder /usr/local/cargo/bin/oxipng /usr/local/bin/oxipng

# Install the rest of dev dependencies
RUN npm ci
