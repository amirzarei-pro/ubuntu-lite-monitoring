<?php

function getSystemData() {
    return [
        'cpu' => getCpuUsage(),
        'memory' => getMemoryUsage(),
        'disk' => getDiskUsage(),
        'system' => getSystemInfo(),
        'processes' => getTopProcesses(),
        'ssh_logins' => getLastSSHLogins(),
        'docker' => getDockerStatus()
    ];
}

// Get CPU usage with detailed info
function getCpuUsage() {
    $load = sys_getloadavg();
    $cpuCount = (int)shell_exec('nproc');
    $cpuInfo = shell_exec('lscpu | grep "Model name"');
    $cpuModel = trim(str_replace('Model name:', '', $cpuInfo));
    
    return [
        'percent' => min(round(($load[0] / $cpuCount) * 100, 1), 100),
        'cores' => $cpuCount,
        'model' => $cpuModel ?: 'N/A',
        'load' => [
            '1min' => round($load[0], 2),
            '5min' => round($load[1], 2),
            '15min' => round($load[2], 2)
        ]
    ];
}

// Get detailed RAM usage
function getMemoryUsage() {
    // Try using `free` first
    $freeOutput = shell_exec('free -b 2>/dev/null');
    $memTotal = 0; $memUsed = 0; $memFree = 0; $memAvailable = 0; $memCached = 0;
    $swapTotal = 0; $swapUsed = 0;

    if (!empty($freeOutput)) {
        $lines = preg_split('/\r?\n/', trim($freeOutput));
        foreach ($lines as $line) {
            if (strpos($line, 'Mem:') === 0) {
                $parts = array_values(array_filter(preg_split('/\s+/', $line)));
                // Expected: ["Mem:", total, used, free, shared, buff/cache, available]
                $memTotal    = isset($parts[1]) ? (int)$parts[1] : 0;
                $memUsed     = isset($parts[2]) ? (int)$parts[2] : 0;
                $memFree     = isset($parts[3]) ? (int)$parts[3] : 0;
                $memCached   = isset($parts[5]) ? (int)$parts[5] : 0;
                $memAvailable= isset($parts[6]) ? (int)$parts[6] : 0;
            } elseif (strpos($line, 'Swap:') === 0) {
                $parts = array_values(array_filter(preg_split('/\s+/', $line)));
                // Expected: ["Swap:", total, used, free]
                $swapTotal = isset($parts[1]) ? (int)$parts[1] : 0;
                $swapUsed  = isset($parts[2]) ? (int)$parts[2] : 0;
            }
        }
    }

    // Fallback to /proc/meminfo if needed
    if ($memTotal === 0) {
        if (is_readable('/proc/meminfo')) {
            $info = [];
            $lines = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ((array)$lines as $line) {
                if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                    $info[$m[1]] = (int)$m[2]; // values in kB
                }
            }
            $memTotal     = isset($info['MemTotal']) ? $info['MemTotal'] * 1024 : 0;
            $memFree      = isset($info['MemFree']) ? $info['MemFree'] * 1024 : 0;
            $memAvailable = isset($info['MemAvailable']) ? $info['MemAvailable'] * 1024 : 0;
            $memCached    = isset($info['Cached']) ? $info['Cached'] * 1024 : 0;
            // Approximate used as total - available when available exists, else total - free - cached
            if ($memAvailable > 0) {
                $memUsed = max($memTotal - $memAvailable, 0);
            } else {
                $memUsed = max($memTotal - $memFree - $memCached, 0);
            }

            $swapTotal    = isset($info['SwapTotal']) ? $info['SwapTotal'] * 1024 : 0;
            $swapFree     = isset($info['SwapFree']) ? $info['SwapFree'] * 1024 : 0;
            $swapUsed     = max($swapTotal - $swapFree, 0);
        }
    }

    $totalGB     = $memTotal > 0 ? $memTotal / 1024 / 1024 / 1024 : 0;
    $usedGB      = $memUsed / 1024 / 1024 / 1024;
    $freeGB      = $memFree / 1024 / 1024 / 1024;
    $availGB     = $memAvailable / 1024 / 1024 / 1024;
    $cachedGB    = $memCached / 1024 / 1024 / 1024;
    $swapTotalGB = $swapTotal / 1024 / 1024 / 1024;
    $swapUsedGB  = $swapUsed / 1024 / 1024 / 1024;

    return [
        'total' => round($totalGB, 2),
        'used' => round($usedGB, 2),
        'free' => round($freeGB, 2),
        'available' => round($availGB, 2),
        'cached' => round($cachedGB, 2),
        'percent' => $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0,
        'swap' => [
            'total' => round($swapTotalGB, 2),
            'used' => round($swapUsedGB, 2),
            'percent' => $swapTotal > 0 ? round(($swapUsed / $swapTotal) * 100, 1) : 0
        ]
    ];
}

// Get disk usage
function getDiskUsage() {
    $total = disk_total_space('/');
    $free = disk_free_space('/');
    $used = $total - $free;
    
    return [
        'total' => round($total / 1024 / 1024 / 1024, 2),
        'used' => round($used / 1024 / 1024 / 1024, 2),
        'free' => round($free / 1024 / 1024 / 1024, 2),
        'percent' => round(($used / $total) * 100, 1)
    ];
}

// Get system info
function getSystemInfo() {
    $hostname = gethostname();
    $uptime = shell_exec('uptime -p');
    $kernel = php_uname('r');
    $os = shell_exec('lsb_release -d | cut -f2');
    
    return [
        'hostname' => $hostname,
        'uptime' => trim($uptime),
        'kernel' => $kernel,
        'os' => trim($os),
        'timestamp' => date('H:i:s')
    ];
}

// Get top processes
function getTopProcesses() {
    $output = shell_exec("ps aux --sort=-%mem | head -6");
    return nl2br(htmlspecialchars($output));
}

// Get last SSH logins
function getLastSSHLogins() {
    $logins = shell_exec("last -n 10 -w | grep -v 'reboot' | grep -v 'wtmp' | head -5");
    return nl2br(htmlspecialchars($logins ?: 'No recent SSH logins found'));
}

// Get Docker status
function getDockerStatus() {
    // Check if Docker is installed
    $dockerInstalled = shell_exec('which docker 2>/dev/null');
    
    if (empty($dockerInstalled)) {
        return [
            'installed' => false,
            'running' => false,
            'version' => 'Not installed',
            'containers' => [],
            'error' => null
        ];
    }
    
    // Check if Docker daemon is running
    $dockerRunning = shell_exec('systemctl is-active docker 2>/dev/null');
    $isRunning = trim($dockerRunning) === 'active';
    
    // Get Docker version
    $version = shell_exec('docker --version 2>/dev/null');
    
    $containers = [];
    $allContainers = [];
    $error = null;
    $statsMap = [];
    
    if ($isRunning) {
        // Try to get running containers with docker ps
        $runningList = shell_exec('docker ps --format "{{.ID}}|{{.Names}}|{{.Status}}|{{.Image}}|{{.Ports}}" 2>&1');
        
        // Check for permission errors
        if (strpos($runningList, 'permission denied') !== false || strpos($runningList, 'Cannot connect') !== false) {
            $error = 'Permission denied. Add www-data user to docker group.';
        } elseif (!empty($runningList)) {
            $lines = explode("\n", trim($runningList));
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                $parts = explode('|', $line);
                if (count($parts) >= 4) {
                    $containers[] = [
                        'id' => substr($parts[0], 0, 12),
                        'name' => $parts[1],
                        'status' => $parts[2],
                        'image' => $parts[3],
                        'ports' => isset($parts[4]) ? $parts[4] : 'N/A',
                        'running' => true
                    ];
                }
            }
        }
        
        // Get all containers (including stopped) with docker ps -a
        if ($error === null) {
            $allList = shell_exec('docker ps -a --format "{{.ID}}|{{.Names}}|{{.Status}}|{{.Image}}|{{.Ports}}" 2>&1');
            
            if (!empty($allList) && strpos($allList, 'permission denied') === false) {
                $lines = explode("\n", trim($allList));
                foreach ($lines as $line) {
                    if (empty($line)) continue;
                    
                    $parts = explode('|', $line);
                    if (count($parts) >= 4) {
                        $status = $parts[2];
                        $isUp = strpos(strtolower($status), 'up') !== false;
                        
                        $allContainers[] = [
                            'id' => substr($parts[0], 0, 12),
                            'name' => $parts[1],
                            'status' => $status,
                            'image' => $parts[3],
                            'ports' => isset($parts[4]) ? $parts[4] : 'N/A',
                            'running' => $isUp
                        ];
                    }
                }
            }
        }

        // Collect live resource stats for running containers
        if ($error === null) {
            $statsOutput = shell_exec('docker stats --no-stream --format "{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}" 2>&1');
            if (!empty($statsOutput) && strpos($statsOutput, 'permission denied') === false) {
                $lines = explode("\n", trim($statsOutput));
                foreach ($lines as $line) {
                    if (empty($line)) {
                        continue;
                    }
                    $parts = explode('|', $line);
                    if (count($parts) >= 4) {
                        $cpu = floatval(str_replace('%', '', trim($parts[1])));
                        $memPercent = floatval(str_replace('%', '', trim($parts[3])));
                        $statsMap[$parts[0]] = [
                            'cpu_percent' => round($cpu, 1),
                            'mem_usage' => trim($parts[2]),
                            'mem_percent' => round($memPercent, 1)
                        ];
                    }
                }
            }
        }
    }
    
    return [
        'installed' => true,
        'running' => $isRunning,
        'version' => trim($version),
        'containers' => array_map(function ($container) use ($statsMap) {
            $name = $container['name'];
            if (isset($statsMap[$name])) {
                $container['cpu_percent'] = $statsMap[$name]['cpu_percent'];
                $container['mem_usage'] = $statsMap[$name]['mem_usage'];
                $container['mem_percent'] = $statsMap[$name]['mem_percent'];
            } else {
                $container['cpu_percent'] = null;
                $container['mem_usage'] = null;
                $container['mem_percent'] = null;
            }
            return $container;
        }, $allContainers),
        'total' => count($allContainers),
        'running_count' => count($containers),
        'error' => $error
    ];
}
