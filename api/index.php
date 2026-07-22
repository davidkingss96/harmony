<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

switch($endpoint) {
    case 'notes':
        handleNotes($method);
        break;
    case 'chords':
        handleChords($method);
        break;
    case 'scales':
        handleScales($method);
        break;
    case 'tunings':
        handleTunings($method);
        break;
    case 'sessions':
        handleSessions($method);
        break;
    case 'harmony':
        handleHarmony($method);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

function handleNotes($method) {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM notes ORDER BY chromatic_position");
        echo json_encode($stmt->fetchAll());
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleChords($method) {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM chords ORDER BY name");
        echo json_encode($stmt->fetchAll());
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleScales($method) {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM scales ORDER BY name");
        echo json_encode($stmt->fetchAll());
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleTunings($method) {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM tunings ORDER BY name");
        echo json_encode($stmt->fetchAll());
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleSessions($method) {
    $db = (new Database())->getConnection();
    
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific session with items
                $id = $_GET['id'];
                $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
                $stmt->execute([$id]);
                $session = $stmt->fetch();
                
                if ($session) {
                    $stmt = $db->prepare("
                        SELECT si.*, 
                               CASE 
                                   WHEN si.type = 'CHORD' THEN c.name
                                   WHEN si.type = 'SCALE' THEN s.name
                               END as reference_name,
                               n.name as root_note_name
                        FROM session_items si
                        LEFT JOIN chords c ON si.type = 'CHORD' AND si.reference_id = c.id
                        LEFT JOIN scales s ON si.type = 'SCALE' AND si.reference_id = s.id
                        LEFT JOIN notes n ON si.root_note = n.chromatic_position
                        WHERE si.session_id = ?
                        ORDER BY si.position
                    ");
                    $stmt->execute([$id]);
                    $session['items'] = $stmt->fetchAll();
                    
                    echo json_encode($session);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Session not found']);
                }
            } else {
                // Get all sessions
                $stmt = $db->query("SELECT * FROM sessions ORDER BY created_at DESC");
                echo json_encode($stmt->fetchAll());
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['tuning_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and tuning_id are required']);
                return;
            }
            
            $stmt = $db->prepare("INSERT INTO sessions (name, tuning_id) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['tuning_id']]);
            
            http_response_code(201);
            echo json_encode([
                'id' => $db->lastInsertId(),
                'name' => $data['name'],
                'tuning_id' => $data['tuning_id']
            ]);
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['message' => 'Session deleted']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Session ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleHarmony($method) {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['tuning_id']) || !isset($data['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'tuning_id and items are required']);
            return;
        }
        
        require_once __DIR__ . '/models/HarmonyEngine.php';
        
        $engine = new HarmonyEngine();
        $heatmap = $engine->calculateHeatmap(
            $data['tuning_id'],
            $data['items']
        );
        
        echo json_encode($heatmap);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}
