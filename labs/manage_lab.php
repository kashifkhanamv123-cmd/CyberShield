<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

// Security check: only logged in users
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$lab    = $_GET['lab']    ?? '';

// Whitelist of allowed labs and their docker images
$allowed_labs = [
    'juiceshop' => [
        'image' => 'bkimminich/juice-shop',
        'port'  => '3000',
        'container_name' => 'cybershield-juiceshop'
    ],
    'dvwa' => [
        'image' => 'vulnerables/web-dvwa',
        'port'  => '80',
        'container_name' => 'cybershield-dvwa'
    ],
    'bwapp' => [
        'image' => 'raesene/bwapp',
        'port'  => '80',
        'container_name' => 'cybershield-bwapp'
    ]
];

if (!array_key_exists($lab, $allowed_labs)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid lab specified']);
    exit();
}

$lab_config = $allowed_labs[$lab];
$container  = $lab_config['container_name'];

header('Content-Type: application/json');

switch ($action) {
    case 'start':
        // 1. Check if already running
        $check = shell_exec("docker ps -q -f name=$container");
        if ($check) {
            echo json_encode(['status' => 'success', 'message' => 'Lab is already running', 'port' => $lab_config['port']]);
            break;
        }

        // 2. Check if exists but stopped
        $exists = shell_exec("docker ps -aq -f name=$container");
        if ($exists) {
            shell_exec("docker start $container");
            echo json_encode(['status' => 'success', 'message' => 'Lab started', 'port' => $lab_config['port']]);
        } else {
            // 3. Create and run new container
            $image = $lab_config['image'];
            $port  = $lab_config['port'];
            // We map internal port to a unique host port if needed, 
            // but for simplicity we'll try to use the same or a range.
            // Juice shop: 3000, DVWA: 8081, bWAPP: 8082
            $host_port = ($lab === 'juiceshop') ? '3000' : (($lab === 'dvwa') ? '8081' : '8082');
            
            $cmd = "docker run -d --name $container -p $host_port:$port $image";
            shell_exec($cmd);
            echo json_encode(['status' => 'success', 'message' => 'Lab container created and started', 'port' => $host_port]);
        }
        break;

    case 'stop':
        shell_exec("docker stop $container");
        echo json_encode(['status' => 'success', 'message' => 'Lab stopped']);
        break;

    case 'reset':
        shell_exec("docker stop $container");
        shell_exec("docker rm $container");
        // Trigger start again
        header("Location: manage_lab.php?action=start&lab=$lab");
        break;

    case 'status':
        $running = shell_exec("docker ps -q -f name=$container");
        echo json_encode(['status' => $running ? 'running' : 'stopped']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        break;
}
