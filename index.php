<?php
require_once __DIR__ . '/src/System.php';
// API endpoint for AJAX requests
if (isset($_GET['api']) && $_GET['api'] === 'data') {
    header('Content-Type: application/json');
    echo json_encode(getSystemData());
    exit;
}

$data = getSystemData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitor - <?php echo $data['system']['hostname']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #1a2332 100%);
            min-height: 100vh;
            padding: 15px;
            color: #fff;
            overflow-x: hidden;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-meta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        h1 {
            font-size: 2em;
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .last-update {
            font-size: 0.9em;
            color: #94a3b8;
        }

        .refresh-toggle {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid rgba(59, 130, 246, 0.5);
            background: rgba(59, 130, 246, 0.15);
            color: #e2e8f0;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .refresh-toggle:hover {
            border-color: rgba(59, 130, 246, 0.8);
            background: rgba(59, 130, 246, 0.25);
        }

        .refresh-toggle.off {
            border-color: rgba(148, 163, 184, 0.6);
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.5em;
            color: #60a5fa;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(59, 130, 246, 0.3);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 1.5em;
            }
            
            .section {
                margin-bottom: 30px;
            }
        }

        .card {
            background: rgba(30, 58, 138, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.6);
            box-shadow: 0 12px 40px rgba(59, 130, 246, 0.3);
        }

        .card-title {
            font-size: 1.2em;
            margin-bottom: 20px;
            color: #60a5fa;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .gauge-container {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto;
            z-index: 1;
        }

        @media (max-width: 768px) {
            .gauge-container {
                width: 150px;
                height: 150px;
            }
        }

        .gauge {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .gauge-bg {
            fill: none;
            stroke: rgba(59, 130, 246, 0.2);
            stroke-width: 12;
        }

        .gauge-fill {
            fill: none;
            stroke: #3b82f6;
            stroke-width: 12;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 0 8px rgba(59, 130, 246, 0.8));
        }

        .gauge-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .gauge-value {
            font-size: 2.2em;
            font-weight: bold;
            color: #3b82f6;
            text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }

        .gauge-label {
            font-size: 0.85em;
            color: #94a3b8;
            margin-top: 5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            padding-left: 10px;
            border-bottom-color: rgba(59, 130, 246, 0.3);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #94a3b8;
            font-size: 0.9em;
        }

        .info-value {
            color: #60a5fa;
            font-weight: 600;
            transition: color 0.3s ease;
            text-align: right;
            word-break: break-word;
        }

        .info-item:hover .info-value {
            color: #3b82f6;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .detail-card {
            background: rgba(59, 130, 246, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .detail-card:hover {
            background: rgba(59, 130, 246, 0.15);
            transform: translateY(-3px);
        }

        .detail-label {
            font-size: 0.8em;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.1em;
            color: #60a5fa;
            font-weight: 600;
        }

        .log-container {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.8em;
            line-height: 1.6;
            color: #cbd5e1;
            max-height: 200px;
            overflow-y: auto;
            position: relative;
            z-index: 1;
        }

        .log-container::-webkit-scrollbar {
            width: 8px;
        }

        .log-container::-webkit-scrollbar-track {
            background: rgba(59, 130, 246, 0.1);
            border-radius: 4px;
        }

        .log-container::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 4px;
        }

        .docker-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .docker-status.running {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.4);
        }

        .docker-status.stopped {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .docker-status.not-installed {
            background: rgba(156, 163, 175, 0.2);
            color: #9ca3af;
            border: 1px solid rgba(156, 163, 175, 0.4);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: blink 2s ease-in-out infinite;
        }

        .status-dot.green {
            background: #4ade80;
            box-shadow: 0 0 10px #4ade80;
        }

        .status-dot.red {
            background: #f87171;
            box-shadow: 0 0 10px #f87171;
        }

        .status-dot.gray {
            background: #9ca3af;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .container-card {
            background: rgba(59, 130, 246, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .container-card:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.4);
        }

        .compose-group {
            margin-bottom: 12px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(59, 130, 246, 0.05);
        }

        .compose-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            cursor: pointer;
            color: #e2e8f0;
            background: rgba(59, 130, 246, 0.12);
        }

        .compose-header:hover {
            background: rgba(59, 130, 246, 0.18);
        }

        .compose-title {
            font-weight: 700;
            color: #60a5fa;
        }

        .compose-count {
            font-size: 0.9em;
            color: #cbd5e1;
        }

        .compose-body {
            padding: 10px;
            display: block;
        }

        .compose-body.collapsed {
            display: none;
        }

        .usage-chart {
            margin-top: 20px;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 15px;
        }

        .usage-chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .usage-chart-title {
            color: #60a5fa;
            font-weight: 600;
        }

        .usage-chart-select {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #e2e8f0;
            padding: 6px 10px;
            border-radius: 8px;
            position: relative;
            z-index: 1000;
        }

        .chart-legend {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 8px;
            margin-top: 10px;
            font-size: 0.9em;
            color: #cbd5e1;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-swatch {
            width: 12px;
            height: 12px;
            border-radius: 4px;
        }

        .chart-empty {
            text-align: center;
            color: #94a3b8;
            padding: 12px 0;
        }

        .container-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .container-name {
            font-size: 1.1em;
            font-weight: 600;
            color: #60a5fa;
        }

        .container-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .container-status.up {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        .container-status.down {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .container-details {
            font-size: 0.85em;
            color: #94a3b8;
            line-height: 1.6;
        }

        .container-details div {
            margin-bottom: 5px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card, .section {
            animation: fadeIn 0.6s ease forwards;
        }

        .value-updating {
            animation: fadeValue 0.5s ease;
        }

        @keyframes fadeValue {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖥️ System Monitor Dashboard</h1>
            <div class="header-meta">
                <div class="last-update">Last update: <span id="timestamp"><?php echo $data['system']['timestamp']; ?></span></div>
                <button id="refreshToggle" class="refresh-toggle">Auto Refresh: On</button>
            </div>
        </div>
        
        <!-- Resources Section -->
        <div class="section">
            <h2 class="section-title">📊 System Resources</h2>
            <div class="grid">
                <!-- CPU Card -->
                <div class="card">
                    <div class="card-title">CPU Usage</div>
                    <div class="gauge-container">
                        <svg class="gauge" viewBox="0 0 200 200">
                            <circle class="gauge-bg" cx="100" cy="100" r="90"/>
                            <circle class="gauge-fill" id="cpuGauge" cx="100" cy="100" r="90"
                                    stroke-dasharray="565.48"
                                    stroke-dashoffset="<?php echo 565.48 * (1 - $data['cpu']['percent'] / 100); ?>"/>
                        </svg>
                        <div class="gauge-text">
                            <div class="gauge-value" id="cpuValue"><?php echo $data['cpu']['percent']; ?>%</div>
                            <div class="gauge-label">CPU</div>
                        </div>
                    </div>
                    <div class="detail-grid">
                        <div class="detail-card">
                            <div class="detail-label">Cores</div>
                            <div class="detail-value" id="cpuCores"><?php echo $data['cpu']['cores']; ?></div>
                        </div>
                        <div class="detail-card">
                            <div class="detail-label">Load (1m)</div>
                            <div class="detail-value" id="cpuLoad1"><?php echo $data['cpu']['load']['1min']; ?></div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <div class="info-item">
                            <span class="info-label">Model:</span>
                            <span class="info-value" id="cpuModel"><?php echo substr($data['cpu']['model'], 0, 30); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Load (5/15 min):</span>
                            <span class="info-value" id="cpuLoad"><?php echo $data['cpu']['load']['5min'] . ' / ' . $data['cpu']['load']['15min']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- RAM Card -->
                <div class="card">
                    <div class="card-title">Memory Usage</div>
                    <div class="gauge-container">
                        <svg class="gauge" viewBox="0 0 200 200">
                            <circle class="gauge-bg" cx="100" cy="100" r="90"/>
                            <circle class="gauge-fill" id="ramGauge" cx="100" cy="100" r="90"
                                    stroke-dasharray="565.48"
                                    stroke-dashoffset="<?php echo 565.48 * (1 - $data['memory']['percent'] / 100); ?>"/>
                        </svg>
                        <div class="gauge-text">
                            <div class="gauge-value" id="ramValue"><?php echo $data['memory']['percent']; ?>%</div>
                            <div class="gauge-label">RAM</div>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <div class="info-item">
                            <span class="info-label">Used:</span>
                            <span class="info-value" id="ramUsed"><?php echo $data['memory']['used']; ?> GB</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Available:</span>
                            <span class="info-value" id="ramAvailable"><?php echo $data['memory']['available']; ?> GB</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Cached:</span>
                            <span class="info-value" id="ramCached"><?php echo $data['memory']['cached']; ?> GB</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total:</span>
                            <span class="info-value" id="ramTotal"><?php echo $data['memory']['total']; ?> GB</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Swap:</span>
                            <span class="info-value" id="swapUsed"><?php echo $data['memory']['swap']['used'] . ' GB (' . $data['memory']['swap']['percent'] . '%)'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Disk Card -->
                <div class="card">
                    <div class="card-title">Disk Usage</div>
                    <div class="gauge-container">
                        <svg class="gauge" viewBox="0 0 200 200">
                            <circle class="gauge-bg" cx="100" cy="100" r="90"/>
                            <circle class="gauge-fill" id="diskGauge" cx="100" cy="100" r="90"
                                    stroke-dasharray="565.48"
                                    stroke-dashoffset="<?php echo 565.48 * (1 - $data['disk']['percent'] / 100); ?>"/>
                        </svg>
                        <div class="gauge-text">
                            <div class="gauge-value" id="diskValue"><?php echo $data['disk']['percent']; ?>%</div>
                            <div class="gauge-label">DISK</div>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <div class="info-item">
                            <span class="info-label">Used:</span>
                            <span class="info-value" id="diskUsed"><?php echo $data['disk']['used']; ?> GB</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Free:</span>
                            <span class="info-value" id="diskFree"><?php echo $data['disk']['free']; ?> GB</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total:</span>
                            <span class="info-value" id="diskTotal"><?php echo $data['disk']['total']; ?> GB</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Docker Section -->
        <div class="section">
            <h2 class="section-title">🐳 Docker Status</h2>
            <div class="card">
                <?php if ($data['docker']['installed']): ?>
                    <div class="docker-status <?php echo $data['docker']['running'] ? 'running' : 'stopped'; ?>">
                        <span class="status-dot <?php echo $data['docker']['running'] ? 'green' : 'red'; ?>"></span>
                        <span><?php echo $data['docker']['running'] ? 'Docker Running' : 'Docker Stopped'; ?></span>
                    </div>
                    
                    <?php if (isset($data['docker']['error']) && $data['docker']['error']): ?>
                        <div style="background: rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 10px; margin-bottom: 20px; color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);">
                            <strong>⚠️ Permission Error:</strong><br>
                            <?php echo $data['docker']['error']; ?><br>
                            <small style="color: #cbd5e1; margin-top: 10px; display: block;">
                                Run: <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">sudo usermod -aG docker www-data</code><br>
                                Then: <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">sudo systemctl restart php8.3-fpm</code>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <span class="info-label">Version:</span>
                        <span class="info-value" id="dockerVersion"><?php echo $data['docker']['version']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Containers:</span>
                        <span class="info-value" id="dockerTotal"><?php echo $data['docker']['total']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Running:</span>
                        <span class="info-value" id="dockerRunning"><?php echo $data['docker']['running_count']; ?></span>
                    </div>

                    <div class="usage-chart" id="usageChartBlock">
                        <div class="usage-chart-header">
                            <div class="usage-chart-title">Containers Usage</div>
                            <select id="usageMetric" class="usage-chart-select">
                                <option value="cpu">CPU %</option>
                                <option value="mem">RAM %</option>
                            </select>
                        </div>
                        <canvas id="containerUsageChart" width="320" height="320"></canvas>
                        <div class="chart-empty" id="chartEmpty">No usage data available</div>
                        <div class="chart-legend" id="containerUsageLegend"></div>
                    </div>
                    
                    <?php if ($data['docker']['running'] && !empty($data['docker']['containers']) && !$data['docker']['error']): ?>
                        <div style="margin-top: 25px;" id="dockerContainers">
                            <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 1.1em;">Container Details</h3>
                            <?php 
                                $grouped = [];
                                foreach ($data['docker']['containers'] as $container) {
                                    $project = isset($container['project']) && $container['project'] ? $container['project'] : 'Ungrouped';
                                    $grouped[$project][] = $container;
                                }
                            ?>
                            <?php foreach ($grouped as $project => $containers): ?>
                                <div class="compose-group">
                                    <div class="compose-header" data-project="<?php echo htmlspecialchars($project); ?>">
                                        <span class="compose-title"><?php echo htmlspecialchars($project); ?></span>
                                        <span class="compose-count"><?php echo count($containers); ?> containers</span>
                                    </div>
                                    <div class="compose-body">
                                        <?php foreach ($containers as $container): ?>
                                            <div class="container-card">
                                                <div class="container-header">
                                                    <div class="container-name"><?php echo htmlspecialchars($container['name']); ?></div>
                                                    <div class="container-status <?php echo $container['running'] ? 'up' : 'down'; ?>">
                                                        <span class="status-dot <?php echo $container['running'] ? 'green' : 'red'; ?>"></span>
                                                        <?php echo $container['running'] ? 'Running' : 'Stopped'; ?>
                                                    </div>
                                                </div>
                                                <div class="container-details">
                                                    <div><strong>Image:</strong> <?php echo htmlspecialchars($container['image']); ?></div>
                                                    <div><strong>Status:</strong> <?php echo htmlspecialchars($container['status']); ?></div>
                                                    <div><strong>ID:</strong> <?php echo htmlspecialchars($container['id']); ?></div>
                                                    <div><strong>CPU:</strong> <?php echo $container['cpu_percent'] !== null ? htmlspecialchars($container['cpu_percent']) . '%' : 'N/A'; ?></div>
                                                    <div><strong>RAM:</strong> <?php echo $container['mem_usage'] !== null ? htmlspecialchars($container['mem_usage']) . ' (' . htmlspecialchars($container['mem_percent']) . '%)' : 'N/A'; ?></div>
                                                    <?php if ($container['ports'] !== 'N/A' && !empty($container['ports'])): ?>
                                                        <div><strong>Ports:</strong> <?php echo htmlspecialchars($container['ports']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($data['docker']['running']): ?>
                        <div style="margin-top: 20px; text-align: center; color: #94a3b8;">
                            No containers found
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="docker-status not-installed">
                        <span class="status-dot gray"></span>
                        <span>Docker Not Installed</span>
                    </div>
                    <div style="margin-top: 15px; text-align: center; color: #94a3b8;">
                        Docker is not installed on this system
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Info Section -->
        <div class="section">
            <h2 class="section-title">ℹ️ System Information</h2>
            <div class="card">
                <div class="info-item">
                    <span class="info-label">Hostname:</span>
                    <span class="info-value" id="hostname"><?php echo $data['system']['hostname']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">OS:</span>
                    <span class="info-value" id="os"><?php echo $data['system']['os']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kernel:</span>
                    <span class="info-value" id="kernel"><?php echo $data['system']['kernel']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Uptime:</span>
                    <span class="info-value" id="uptime"><?php echo $data['system']['uptime']; ?></span>
                </div>
            </div>
        </div>

        <!-- SSH & Processes Section -->
        <div class="section">
            <h2 class="section-title">🔐 Security & Processes</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-title">Recent SSH Logins</div>
                    <div class="log-container" id="sshLogins"><?php echo $data['ssh_logins']; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Top Processes (by Memory)</div>
                    <div class="log-container" id="processes"><?php echo $data['processes']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let updateInterval;
        const REFRESH_INTERVAL = 3000;
        let autoRefreshEnabled = true;
        const composeCollapsed = {};
        
        function updateGauge(id, percent) {
            const gauge = document.getElementById(id);
            const offset = 565.48 * (1 - percent / 100);
            gauge.style.strokeDashoffset = offset;
        }

        function updateValue(id, value, addAnimation = true) {
            const element = document.getElementById(id);
            if (!element) return;
            
            if (addAnimation) {
                element.classList.add('value-updating');
                setTimeout(() => element.classList.remove('value-updating'), 500);
            }
            element.textContent = value;
        }

        function updateDockerContainers(containers) {
            const containerDiv = document.getElementById('dockerContainers');
            if (!containerDiv) return;

            if (containers.length === 0) {
                containerDiv.innerHTML = '<div style="margin-top: 20px; text-align: center; color: #94a3b8;">No containers found</div>';
                return;
            }

            const groups = {};
            containers.forEach(container => {
                const project = container.project && container.project !== '<no value>' ? container.project : 'Ungrouped';
                if (!groups[project]) groups[project] = [];
                groups[project].push(container);
            });

            const sortedProjects = Object.keys(groups).sort();
            let html = '<h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 1.1em;">Container Details</h3>';

            sortedProjects.forEach(project => {
                const collapsed = composeCollapsed[project] === true;
                html += `<div class="compose-group">
                    <div class="compose-header" data-project="${escapeHtml(project)}">
                        <span class="compose-title">${escapeHtml(project)}</span>
                        <span class="compose-count">${groups[project].length} containers</span>
                    </div>
                    <div class="compose-body ${collapsed ? 'collapsed' : ''}">
                `;

                groups[project].forEach(container => {
                    const statusClass = container.running ? 'up' : 'down';
                    const statusDot = container.running ? 'green' : 'red';
                    const statusText = container.running ? 'Running' : 'Stopped';
                    const cpu = container.cpu_percent !== null && container.cpu_percent !== undefined ? `${container.cpu_percent}%` : 'N/A';
                    const memUsage = container.mem_usage ? container.mem_usage : 'N/A';
                    const memPercent = container.mem_percent !== null && container.mem_percent !== undefined ? `${container.mem_percent}%` : 'N/A';

                    html += `
                        <div class="container-card">
                            <div class="container-header">
                                <div class="container-name">${escapeHtml(container.name)}</div>
                                <div class="container-status ${statusClass}">
                                    <span class="status-dot ${statusDot}"></span>
                                    ${statusText}
                                </div>
                            </div>
                            <div class="container-details">
                                <div><strong>Image:</strong> ${escapeHtml(container.image)}</div>
                                <div><strong>Status:</strong> ${escapeHtml(container.status)}</div>
                                <div><strong>ID:</strong> ${escapeHtml(container.id)}</div>
                                <div><strong>CPU:</strong> ${cpu}</div>
                                <div><strong>RAM:</strong> ${memUsage !== 'N/A' ? `${escapeHtml(memUsage)} (${memPercent})` : 'N/A'}</div>
                                ${container.ports !== 'N/A' && container.ports ? `<div><strong>Ports:</strong> ${escapeHtml(container.ports)}</div>` : ''}
                            </div>
                        </div>
                    `;
                });

                html += '</div></div>';
            });

            containerDiv.innerHTML = html;

            containerDiv.querySelectorAll('.compose-header').forEach(header => {
                header.addEventListener('click', () => {
                    const project = header.dataset.project || 'Ungrouped';
                    const body = header.nextElementSibling;
                    const isCollapsed = body.classList.toggle('collapsed');
                    composeCollapsed[project] = isCollapsed;
                });
            });
        }

        function renderContainerUsage(containers) {
            const canvas = document.getElementById('containerUsageChart');
            const legend = document.getElementById('containerUsageLegend');
            const empty = document.getElementById('chartEmpty');
            const block = document.getElementById('usageChartBlock');
            const metricSelect = document.getElementById('usageMetric');
            if (!canvas || !legend || !empty || !block || !metricSelect) return;

            const metric = metricSelect.value === 'mem' ? 'mem_percent' : 'cpu_percent';
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const filtered = containers.filter(c => c.running && c[metric] !== null && !isNaN(c[metric]) && c[metric] > 0);
            const total = filtered.reduce((sum, c) => sum + c[metric], 0);

            block.style.display = 'block';

            if (filtered.length === 0 || total === 0) {
                empty.style.display = 'block';
                legend.innerHTML = '';
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                return;
            }

            empty.style.display = 'none';

            const colors = [
                '#3b82f6', '#22d3ee', '#a855f7', '#f97316', '#ef4444',
                '#10b981', '#eab308', '#8b5cf6', '#06b6d4', '#fb7185'
            ];

            let startAngle = -Math.PI / 2;
            legend.innerHTML = '';

            filtered.forEach((c, idx) => {
                const value = c[metric];
                const angle = (value / total) * Math.PI * 2;
                const endAngle = startAngle + angle;
                const color = colors[idx % colors.length];

                ctx.beginPath();
                ctx.moveTo(160, 160);
                ctx.arc(160, 160, 140, startAngle, endAngle);
                ctx.closePath();
                ctx.fillStyle = color;
                ctx.fill();

                startAngle = endAngle;

                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                legend.innerHTML += `
                    <div class="legend-item">
                        <span class="legend-swatch" style="background:${color}"></span>
                        <span>${escapeHtml(c.name)} — ${value.toFixed(1)}% (${percent}%)</span>
                    </div>
                `;
            });

            ctx.beginPath();
            ctx.fillStyle = '#0f172a';
            ctx.arc(160, 160, 80, 0, Math.PI * 2);
            ctx.fill();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function updateData() {
            try {
                const response = await fetch('?api=data&t=' + Date.now());
                const data = await response.json();
                
                // Update timestamp
                updateValue('timestamp', data.system.timestamp, false);
                
                // Update CPU
                updateGauge('cpuGauge', data.cpu.percent);
                updateValue('cpuValue', data.cpu.percent + '%');
                updateValue('cpuCores', data.cpu.cores, false);
                updateValue('cpuLoad1', data.cpu.load['1min']);
                updateValue('cpuLoad', data.cpu.load['5min'] + ' / ' + data.cpu.load['15min']);
                
                // Update RAM
                updateGauge('ramGauge', data.memory.percent);
                updateValue('ramValue', data.memory.percent + '%');
                updateValue('ramUsed', data.memory.used + ' GB');
                updateValue('ramAvailable', data.memory.available + ' GB');
                updateValue('ramCached', data.memory.cached + ' GB');
                updateValue('swapUsed', data.memory.swap.used + ' GB (' + data.memory.swap.percent + '%)');
                
                // Update Disk
                updateGauge('diskGauge', data.disk.percent);
                updateValue('diskValue', data.disk.percent + '%');
                updateValue('diskUsed', data.disk.used + ' GB');
                updateValue('diskFree', data.disk.free + ' GB');
                
                // Update Docker
                if (data.docker.installed && data.docker.running) {
                    updateValue('dockerTotal', data.docker.total);
                    updateValue('dockerRunning', data.docker.running_count);
                    updateDockerContainers(data.docker.containers);
                    window.latestDockerData = data.docker.containers;
                    renderContainerUsage(data.docker.containers);
                }
                
                // Update System Info
                updateValue('uptime', data.system.uptime);
                
                // Update SSH Logins
                const sshDiv = document.getElementById('sshLogins');
                if (sshDiv) sshDiv.innerHTML = data.ssh_logins;
                
                // Update Processes
                const procDiv = document.getElementById('processes');
                if (procDiv) procDiv.innerHTML = data.processes;
                
            } catch (error) {
                console.error('Error updating data:', error);
            }
        }

        function stopAutoRefresh() {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        }

        function startAutoRefresh() {
            stopAutoRefresh();
            updateInterval = setInterval(updateData, REFRESH_INTERVAL);
        }

        function updateToggleLabel() {
            const btn = document.getElementById('refreshToggle');
            if (!btn) return;
            btn.textContent = autoRefreshEnabled ? 'Auto Refresh: On' : 'Auto Refresh: Off';
            btn.classList.toggle('off', !autoRefreshEnabled);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('refreshToggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    autoRefreshEnabled = !autoRefreshEnabled;
                    if (autoRefreshEnabled) {
                        startAutoRefresh();
                    } else {
                        stopAutoRefresh();
                    }
                    updateToggleLabel();
                });
            }

            const metricSelect = document.getElementById('usageMetric');
            if (metricSelect) {
                metricSelect.addEventListener('change', () => {
                    if (window.latestDockerData) {
                        renderContainerUsage(window.latestDockerData);
                    }
                });
            }

            updateToggleLabel();
            updateData();
            startAutoRefresh();
        });
    </script>
</body>
</html>
