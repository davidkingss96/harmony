-- Harmony Database Schema - Baseline (Phase 1)
-- Idempotent: safe to apply on any state (fresh or pre-existing).

-- ============================================
-- CATALOG TABLES
-- ============================================

-- Notes (chromatic positions 0-11)
CREATE TABLE IF NOT EXISTS notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(5) NOT NULL UNIQUE,
    chromatic_position INT NOT NULL UNIQUE
);

INSERT IGNORE INTO notes (id, name, chromatic_position) VALUES
(1, 'C', 0), (2, 'C#', 1), (3, 'D', 2), (4, 'D#', 3), (5, 'E', 4), (6, 'F', 5),
(7, 'F#', 6), (8, 'G', 7), (9, 'G#', 8), (10, 'A', 9), (11, 'A#', 10), (12, 'B', 11);

-- Chords
CREATE TABLE IF NOT EXISTS chords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    formula VARCHAR(50) NOT NULL
);

INSERT IGNORE INTO chords (name, formula) VALUES
('Major', '0,4,7'),
('Minor', '0,3,7'),
('Diminished', '0,3,6'),
('Augmented', '0,4,8'),
('Major 7', '0,4,7,11'),
('Minor 7', '0,3,7,10'),
('Dominant 7', '0,4,7,10'),
('Diminished 7', '0,3,6,9'),
('Half-Diminished 7', '0,3,6,10'),
('Minor-Major 7', '0,3,7,11'),
('Augmented 7', '0,4,8,10'),
('Sus2', '0,2,7'),
('Sus4', '0,5,7'),
('Add9', '0,4,7,14'),
('Minor Add9', '0,3,7,14');

-- Scales
CREATE TABLE IF NOT EXISTS scales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    formula VARCHAR(50) NOT NULL
);

INSERT IGNORE INTO scales (name, formula) VALUES
('Major', '0,2,4,5,7,9,11'),
('Natural Minor', '0,2,3,5,7,8,10'),
('Harmonic Minor', '0,2,3,5,7,8,11'),
('Melodic Minor', '0,2,3,5,7,9,11'),
('Pentatonic Major', '0,2,4,7,9'),
('Pentatonic Minor', '0,3,5,7,10'),
('Blues', '0,3,5,6,7,10'),
('Dorian', '0,2,3,5,7,9,10'),
('Phrygian', '0,1,3,5,7,8,10'),
('Lydian', '0,2,4,6,7,9,11'),
('Mixolydian', '0,2,4,5,7,9,10'),
('Locrian', '0,1,3,5,6,8,10'),
('Whole Tone', '0,2,4,6,8,10'),
('Chromatic', '0,1,2,3,4,5,6,7,8,9,10,11');

-- Tunings
CREATE TABLE IF NOT EXISTS tunings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    notes VARCHAR(100) NOT NULL
);

INSERT IGNORE INTO tunings (id, name, notes) VALUES
(1, 'E Standard', '4,9,2,7,11,4');

-- ============================================
-- USER DATA TABLES
-- ============================================

-- Sessions
CREATE TABLE IF NOT EXISTS sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    tuning_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tuning_id) REFERENCES tunings(id)
);

-- Session items (chords or scales)
CREATE TABLE IF NOT EXISTS session_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    type ENUM('CHORD', 'SCALE') NOT NULL,
    reference_id INT NOT NULL,
    root_note INT NOT NULL,
    position INT DEFAULT 0,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
);

-- ============================================
-- SONG BUILDER TABLES
-- ============================================

-- Songs
CREATE TABLE IF NOT EXISTS songs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    bpm DECIMAL(10,2) NOT NULL,
    tuning_id INT NOT NULL,
    time_signature_num INT DEFAULT 4,
    time_signature_den INT DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tuning_id) REFERENCES tunings(id)
);

-- Song sections (Intro, Verse, Chorus, etc. - free names)
CREATE TABLE IF NOT EXISTS song_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    song_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#e94560',
    position INT NOT NULL,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- Song measures
CREATE TABLE IF NOT EXISTS song_measures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    position INT NOT NULL,
    FOREIGN KEY (section_id) REFERENCES song_sections(id) ON DELETE CASCADE
);

-- Song events (only store changes, not every beat)
CREATE TABLE IF NOT EXISTS song_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    measure_id INT NOT NULL,
    beat INT NOT NULL,
    element_type ENUM('CHORD','SCALE') NOT NULL,
    element_id INT NOT NULL,
    root_note INT NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (measure_id) REFERENCES song_measures(id) ON DELETE CASCADE
);

-- ============================================
-- INDEXES (guarded: idempotent for pre-existing databases)
-- ============================================

SET @dbname = DATABASE();

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'session_items' AND INDEX_NAME = 'idx_session_items_session');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_session_items_session ON session_items(session_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'sessions' AND INDEX_NAME = 'idx_sessions_tuning');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_sessions_tuning ON sessions(tuning_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'songs' AND INDEX_NAME = 'idx_songs_tuning');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_songs_tuning ON songs(tuning_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'song_sections' AND INDEX_NAME = 'idx_song_sections_song');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_song_sections_song ON song_sections(song_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'song_measures' AND INDEX_NAME = 'idx_song_measures_section');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_song_measures_section ON song_measures(section_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'song_events' AND INDEX_NAME = 'idx_song_events_measure');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_song_events_measure ON song_events(measure_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
