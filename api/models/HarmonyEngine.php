<?php

class HarmonyEngine {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = (new Database())->getConnection();
    }
    
    /**
     * Calculate heatmap for given tuning and items (chords/scales)
     * Returns array of positions with influence values
     */
    public function calculateHeatmap($tuningId, $items) {
        // Get tuning
        $stmt = $this->db->prepare("SELECT * FROM tunings WHERE id = ?");
        $stmt->execute([$tuningId]);
        $tuning = $stmt->fetch();
        
        if (!$tuning) {
            return ['error' => 'Tuning not found'];
        }
        
        // Get tuning notes as array
        $tuningNotes = array_map('intval', explode(',', $tuning['notes']));
        
        // Calculate influence for each note (0-11)
        $influence = array_fill(0, 12, 0);
        
        foreach ($items as $item) {
            $notes = $this->resolveItem($item['type'], $item['reference_id'], $item['root_note']);
            foreach ($notes as $note) {
                $influence[$note]++;
            }
        }
        
        // Generate heatmap for all string/fret positions
        $heatmap = [];
        $numStrings = count($tuningNotes);
        $numFrets = 13; // 0 to 12
        
        $maxInfluence = max($influence);
        
        for ($string = 0; $string < $numStrings; $string++) {
            for ($fret = 0; $fret < $numFrets; $fret++) {
                // Calculate note at this position
                $noteAtPosition = ($tuningNotes[$string] + $fret) % 12;
                
                // Get influence value
                $influenceValue = $influence[$noteAtPosition];
                
                // Calculate percentage
                $percentage = $maxInfluence > 0 
                    ? round(($influenceValue / $maxInfluence) * 100) 
                    : 0;
                
                // Get note name
                $noteName = $this->getNoteName($noteAtPosition);
                
                $heatmap[] = [
                    'string' => $string + 1,
                    'fret' => $fret,
                    'note' => $noteName,
                    'note_position' => $noteAtPosition,
                    'influence' => $influenceValue,
                    'percentage' => $percentage
                ];
            }
        }
        
        return [
            'tuning' => $tuning,
            'influence' => $influence,
            'heatmap' => $heatmap
        ];
    }
    
    /**
     * Resolve notes for a chord or scale
     */
    private function resolveItem($type, $referenceId, $rootNote) {
        if ($type === 'CHORD') {
            return $this->resolveChord($referenceId, $rootNote);
        } else {
            return $this->resolveScale($referenceId, $rootNote);
        }
    }
    
    /**
     * Resolve chord formula to actual notes
     */
    private function resolveChord($chordId, $rootNote) {
        $stmt = $this->db->prepare("SELECT formula FROM chords WHERE id = ?");
        $stmt->execute([$chordId]);
        $chord = $stmt->fetch();
        
        if (!$chord) {
            return [];
        }
        
        return $this->resolveFormula($chord['formula'], $rootNote);
    }
    
    /**
     * Resolve scale formula to actual notes
     */
    private function resolveScale($scaleId, $rootNote) {
        $stmt = $this->db->prepare("SELECT formula FROM scales WHERE id = ?");
        $stmt->execute([$scaleId]);
        $scale = $stmt->fetch();
        
        if (!$scale) {
            return [];
        }
        
        return $this->resolveFormula($scale['formula'], $rootNote);
    }
    
    /**
     * Resolve formula string to array of notes
     * Formula is comma-separated intervals (e.g., "0,4,7" for major chord)
     * Root note is chromatic position (0-11)
     */
    private function resolveFormula($formula, $rootNote) {
        $intervals = array_map('intval', explode(',', $formula));
        
        return array_map(function($interval) use ($rootNote) {
            return ($rootNote + $interval) % 12;
        }, $intervals);
    }
    
    /**
     * Get note name from chromatic position
     */
    private function getNoteName($position) {
        $notes = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
        return $notes[$position] ?? '?';
    }
}
