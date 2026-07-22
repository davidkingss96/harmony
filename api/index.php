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
    case 'songs':
        handleSongs($method);
        break;
    case 'song-sections':
        handleSongSections($method);
        break;
    case 'song-measures':
        handleSongMeasures($method);
        break;
    case 'song-events':
        handleSongEvents($method);
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

// ============================================
// SONG BUILDER HANDLERS
// ============================================

function handleSongs($method) {
    $db = (new Database())->getConnection();
    
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $db->prepare("SELECT * FROM songs WHERE id = ?");
                $stmt->execute([$id]);
                $song = $stmt->fetch();
                
                if ($song) {
                    // Get sections with measures and events
                    $stmt = $db->prepare("SELECT * FROM song_sections WHERE song_id = ? ORDER BY position");
                    $stmt->execute([$id]);
                    $sections = $stmt->fetchAll();
                    
                    foreach ($sections as &$section) {
                        $stmt = $db->prepare("SELECT * FROM song_measures WHERE section_id = ? ORDER BY position");
                        $stmt->execute([$section['id']]);
                        $measures = $stmt->fetchAll();
                        
                        foreach ($measures as &$measure) {
                            $stmt = $db->prepare("
                                SELECT se.*, 
                                       CASE 
                                           WHEN se.element_type = 'CHORD' THEN c.name
                                           WHEN se.element_type = 'SCALE' THEN s.name
                                       END as element_name,
                                       n.name as root_note_name
                                FROM song_events se
                                LEFT JOIN chords c ON se.element_type = 'CHORD' AND se.element_id = c.id
                                LEFT JOIN scales s ON se.element_type = 'SCALE' AND se.element_id = s.id
                                LEFT JOIN notes n ON se.root_note = n.chromatic_position
                                WHERE se.measure_id = ?
                                ORDER BY se.beat
                            ");
                            $stmt->execute([$measure['id']]);
                            $measure['events'] = $stmt->fetchAll();
                        }
                        
                        $section['measures'] = $measures;
                    }
                    
                    $song['sections'] = $sections;
                    echo json_encode($song);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Song not found']);
                }
            } else {
                $stmt = $db->query("SELECT * FROM songs ORDER BY created_at DESC");
                echo json_encode($stmt->fetchAll());
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['bpm'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and BPM are required']);
                return;
            }
            
            $tuningId = $data['tuning_id'] ?? 1;
            
            $stmt = $db->prepare("INSERT INTO songs (name, bpm, tuning_id) VALUES (?, ?, ?)");
            $stmt->execute([$data['name'], $data['bpm'], $tuningId]);
            
            http_response_code(201);
            echo json_encode([
                'id' => $db->lastInsertId(),
                'name' => $data['name'],
                'bpm' => $data['bpm'],
                'tuning_id' => $tuningId
            ]);
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $db->prepare("DELETE FROM songs WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['message' => 'Song deleted']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Song ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleSongSections($method) {
    $db = (new Database())->getConnection();
    
    switch($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['song_id']) || !isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'song_id and name are required']);
                return;
            }
            
            // Get next position
            $stmt = $db->prepare("SELECT COALESCE(MAX(position), -1) + 1 as next_pos FROM song_sections WHERE song_id = ?");
            $stmt->execute([$data['song_id']]);
            $nextPos = $stmt->fetch()['next_pos'];
            
            $color = $data['color'] ?? '#e94560';
            
            $stmt = $db->prepare("INSERT INTO song_sections (song_id, name, color, position) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['song_id'], $data['name'], $color, $nextPos]);
            
            http_response_code(201);
            echo json_encode([
                'id' => $db->lastInsertId(),
                'song_id' => $data['song_id'],
                'name' => $data['name'],
                'color' => $color,
                'position' => $nextPos
            ]);
            break;
            
        case 'PUT':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $data = json_decode(file_get_contents('php://input'), true);
                
                $fields = [];
                $values = [];
                
                if (isset($data['name'])) {
                    $fields[] = "name = ?";
                    $values[] = $data['name'];
                }
                if (isset($data['color'])) {
                    $fields[] = "color = ?";
                    $values[] = $data['color'];
                }
                if (isset($data['position'])) {
                    $fields[] = "position = ?";
                    $values[] = $data['position'];
                }
                
                if (!empty($fields)) {
                    $values[] = $id;
                    $sql = "UPDATE song_sections SET " . implode(', ', $fields) . " WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($values);
                }
                
                echo json_encode(['message' => 'Section updated']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Section ID required']);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $db->prepare("DELETE FROM song_sections WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['message' => 'Section deleted']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Section ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleSongMeasures($method) {
    $db = (new Database())->getConnection();
    
    switch($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['section_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'section_id is required']);
                return;
            }
            
            // Get next position
            $stmt = $db->prepare("SELECT COALESCE(MAX(position), -1) + 1 as next_pos FROM song_measures WHERE section_id = ?");
            $stmt->execute([$data['section_id']]);
            $nextPos = $stmt->fetch()['next_pos'];
            
            $stmt = $db->prepare("INSERT INTO song_measures (section_id, position) VALUES (?, ?)");
            $stmt->execute([$data['section_id'], $nextPos]);
            
            http_response_code(201);
            echo json_encode([
                'id' => $db->lastInsertId(),
                'section_id' => $data['section_id'],
                'position' => $nextPos
            ]);
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $db->prepare("DELETE FROM song_measures WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['message' => 'Measure deleted']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Measure ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleSongEvents($method) {
    $db = (new Database())->getConnection();
    
    switch($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['measure_id']) || !isset($data['beat']) || 
                !isset($data['element_type']) || !isset($data['element_id']) || 
                !isset($data['root_note'])) {
                http_response_code(400);
                echo json_encode(['error' => 'measure_id, beat, element_type, element_id, and root_note are required']);
                return;
            }
            
            // Check if event already exists for this measure and beat
            $stmt = $db->prepare("SELECT id FROM song_events WHERE measure_id = ? AND beat = ?");
            $stmt->execute([$data['measure_id'], $data['beat']]);
            $existing = $stmt->fetch();
            
            $notes = $data['notes'] ?? null;
            
            if ($existing) {
                // Update existing event
                $stmt = $db->prepare("UPDATE song_events SET element_type = ?, element_id = ?, root_note = ?, notes = ? WHERE id = ?");
                $stmt->execute([$data['element_type'], $data['element_id'], $data['root_note'], $notes, $existing['id']]);
                $eventId = $existing['id'];
            } else {
                // Create new event
                $stmt = $db->prepare("INSERT INTO song_events (measure_id, beat, element_type, element_id, root_note, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$data['measure_id'], $data['beat'], $data['element_type'], $data['element_id'], $data['root_note'], $notes]);
                $eventId = $db->lastInsertId();
            }
            
            http_response_code(201);
            echo json_encode([
                'id' => $eventId,
                'measure_id' => $data['measure_id'],
                'beat' => $data['beat'],
                'element_type' => $data['element_type'],
                'element_id' => $data['element_id'],
                'root_note' => $data['root_note'],
                'notes' => $notes
            ]);
            break;
            
        case 'PUT':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $data = json_decode(file_get_contents('php://input'), true);
                
                $fields = [];
                $values = [];
                
                if (isset($data['beat'])) {
                    $fields[] = "beat = ?";
                    $values[] = $data['beat'];
                }
                if (isset($data['element_type'])) {
                    $fields[] = "element_type = ?";
                    $values[] = $data['element_type'];
                }
                if (isset($data['element_id'])) {
                    $fields[] = "element_id = ?";
                    $values[] = $data['element_id'];
                }
                if (isset($data['root_note'])) {
                    $fields[] = "root_note = ?";
                    $values[] = $data['root_note'];
                }
                if (array_key_exists('notes', $data)) {
                    $fields[] = "notes = ?";
                    $values[] = $data['notes'];
                }
                
                if (!empty($fields)) {
                    $values[] = $id;
                    $sql = "UPDATE song_events SET " . implode(', ', $fields) . " WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($values);
                }
                
                echo json_encode(['message' => 'Event updated']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Event ID required']);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $db->prepare("DELETE FROM song_events WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['message' => 'Event deleted']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Event ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
