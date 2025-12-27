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
    $free = shell_exec('free -b');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    
    $swap = explode(" ", $free_arr[2]);
    $swap = array_filter($swap);
    $swap = array_merge($swap);
    
    $memTotal = $mem[1];
    $memUsed = $mem[2];
    $memFree = $mem[3];
    $memAvailable = $mem[6];
    $memCached = $mem[5];
    
    $swapTotal = $swap[1];
    $swapUsed = $swap[2];
    
    return [
        'total' => round($memTotal / 1024 / 1024 / 1024, 2),
        'used' => round($memUsed / 1024 / 1024 / 1024, 2),
        'free' => round($memFree / 1024 / 1024 / 1024, 2),
        'available' => round($memAvailable / 1024 / 1024 / 1024, 2),
        'cached' => round($memCached / 1024 / 1024 / 1024, 2),
        'percent' => round(($memUsed / $memTotal) * 100, 1),
        'swap' => [
            'total' => round($swapTotal / 1024 / 1024 / 1024, 2),
            'used' => round($swapUsed / 1024 / 1024 / 1024, 2),
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
    }
    
    return [
        'installed' => true,
        'running' => $isRunning,
        'version' => trim($version),
        'containers' => $allContainers,
        'total' => count($allContainers),
        'running_count' => count($containers),
        'error' => $error
    ];
}
