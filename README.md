# Ubuntu Lite Monitoring

A lightweight PHP-based dashboard to visualize system metrics: CPU, memory, disk usage, uptime and OS info, top processes, recent SSH logins, and Docker status (when available).

## Features
- CPU load, model and core count
- RAM totals, usage, available, cached and swap usage
- Disk total/used/free and percentage
- System info: hostname, uptime, kernel, OS description
- Top processes by memory usage
- Recent SSH logins
- Docker daemon/version and container list

## Project Structure
```
.
├── index.php              # Entry point and UI
├── src/
│   └── System.php         # Modularized metrics functions
├── Dockerfile             # Container image definition
├── docker-compose.yml     # Optional orchestration
└── .dockerignore          # Build context filter
```

## Run Locally (no Docker)
Requires PHP 8+. Start the built-in web server:

```bash
php -S 127.0.0.1:8000
```
Open http://127.0.0.1:8000 in your browser.

## Run with Docker
Build and run using Docker:

```bash
# Build image
docker build -t ubuntu-lite-monitor:latest .

# Run container (basic)
docker run --rm -p 8080:80 ubuntu-lite-monitor:latest
```
Open http://localhost:8080.

### Using docker-compose
```bash
docker compose up --build
```
Open http://localhost:8080.

## Host Metrics vs Container Metrics
By default, when running inside Docker, commands like `ps`, `uptime`, and `last` reflect the container environment, not the host. To gather host-level metrics, you can run the container with elevated privileges and mount specific host resources:

```yaml
# docker-compose.yml (uncomment and adjust as needed)
services:
  web:
    build: .
    ports:
      - "8080:80"
    pid: "host"          # Access host process namespace
    privileged: true      # Broader system access (use with caution)
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock  # Docker info
      - /var/log/wtmp:/var/log/wtmp:ro            # SSH login history
      - /proc:/host_proc:ro                       # Host /proc for system data
    environment:
      - PROC_MOUNT=/host_proc
```

> Note: Elevated privileges have security implications. Only enable these settings in trusted environments.

## Permissions for Docker Info
If you mount the Docker socket, the web server user (`www-data`) may need access. This project detects and reports permission errors; you may see a hint to add the user to the `docker` group. Inside containers, group IDs may differ; using `privileged: true` or proper ACLs typically resolves it.

## API Endpoint
The UI fetches fresh data via `/?api=data` returning JSON. You can integrate or extend it by consuming this endpoint.

## Development Notes
- Core logic is modularized in `src/System.php`.
- `index.php` focuses on routing and rendering.
- For more structure, you can further split UI into templates or adopt a framework, but this code intentionally remains simple.

## License
Proprietary or internal use (no explicit license). Adjust as needed.
