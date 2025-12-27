# Simple PHP-Apache container for Ubuntu Lite Monitoring
FROM php:8.2-apache

# Install tools used by the monitoring scripts
RUN apt-get update && apt-get install -y \
    procps \
    iproute2 \
    lsb-release \
    coreutils \
    util-linux \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy application code into Apache document root
COPY . /var/www/html

# Ensure correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose web port
EXPOSE 80

# Optional: Healthcheck (basic)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost/ || exit 1
