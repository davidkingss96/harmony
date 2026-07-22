-- Harmony Database Schema
-- Guitar Improvisation Guide

USE harmony;

-- ============================================
-- CATALOG TABLES
-- ============================================

-- Notes (chromatic positions 0-11)
CREATE TABLE notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(5) NOT NULL UNIQUE,
    chromatic_position INT NOT NULL UNIQUE
);

-- Insert all 12 notes
INSERT INTO notes (name, chromatic_position) VALUES
('C', 0), ('C#', 1), ('D', 2), ('D#', 3), ('E', 4), ('F', 5),
('F#', 6), ('G', 7), ('G#', 8), ('A', 9), ('A#', 10), ('B', 11);

-- Chords
CREATE TABLE chords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    formula VARCHAR(50) NOT NULL
);

-- Insert basic chords
INSERT INTO chords (name, formula) VALUES
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
CREATE TABLE scales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    formula VARCHAR(50) NOT NULL
);

-- Insert basic scales
INSERT INTO scales (name, formula) VALUES
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
CREATE TABLE tunings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    notes VARCHAR(100) NOT NULL
);

-- Insert E Standard tuning: E A D G B E (4, 9, 2, 7, 11, 4)
INSERT INTO tunings (name, notes) VALUES
('E Standard', '4,9,2,7,11,4');

-- ============================================
-- USER DATA TABLES
-- ============================================

-- Sessions
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    tuning_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tuning_id) REFERENCES tunings(id)
);

-- Session items (chords or scales)
CREATE TABLE session_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    type ENUM('CHORD', 'SCALE') NOT NULL,
    reference_id INT NOT NULL,
    root_note INT NOT NULL,
    position INT DEFAULT 0,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
);

-- ============================================
-- INDEXES
-- ============================================

CREATE INDEX idx_session_items_session ON session_items(session_id);
CREATE INDEX idx_sessions_tuning ON sessions(tuning_id);
