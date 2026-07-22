const API_BASE = '/api/index.php';

let currentSession = null;
let items = [];

// DOM Elements
const sessionNameInput = document.getElementById('sessionName');
const newSessionBtn = document.getElementById('newSessionBtn');
const itemTypeSelect = document.getElementById('itemType');
const itemReferenceSelect = document.getElementById('itemReference');
const itemRootSelect = document.getElementById('itemRoot');
const addItemBtn = document.getElementById('addItemBtn');
const itemsList = document.getElementById('itemsList');
const calculateBtn = document.getElementById('calculateBtn');
const fretboard = document.getElementById('fretboard');
const influenceList = document.getElementById('influenceList');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadChords();
    loadScales();
    loadNotes();
    setupEventListeners();
});

// Load data from API
async function loadChords() {
    const response = await fetch(`${API_BASE}?endpoint=chords`);
    const chords = await response.json();
    updateReferenceSelect('CHORD', chords);
}

async function loadScales() {
    const response = await fetch(`${API_BASE}?endpoint=scales`);
    const scales = await response.json();
    updateReferenceSelect('SCALE', scales);
}

async function loadNotes() {
    const response = await fetch(`${API_BASE}?endpoint=notes`);
    const notes = await response.json();
    
    itemRootSelect.innerHTML = notes.map(note => 
        `<option value="${note.chromatic_position}">${note.name}</option>`
    ).join('');
}

function updateReferenceSelect(type, data) {
    if (itemTypeSelect.value === type) {
        itemReferenceSelect.innerHTML = data.map(item => 
            `<option value="${item.id}">${item.name}</option>`
        ).join('');
    }
}

// Event Listeners
function setupEventListeners() {
    itemTypeSelect.addEventListener('change', async () => {
        const type = itemTypeSelect.value;
        const endpoint = type === 'CHORD' ? 'chords' : 'scales';
        const response = await fetch(`${API_BASE}?endpoint=${endpoint}`);
        const data = await response.json();
        updateReferenceSelect(type, data);
    });
    
    newSessionBtn.addEventListener('click', createSession);
    addItemBtn.addEventListener('click', addItem);
    calculateBtn.addEventListener('click', calculateHarmony);
}

// Session management
async function createSession() {
    const name = sessionNameInput.value.trim();
    if (!name) {
        alert('Please enter a session name');
        return;
    }
    
    // Get default tuning (E Standard = 1)
    const tuningId = 1;
    
    const response = await fetch(`${API_BASE}?endpoint=sessions`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            name: name,
            tuning_id: tuningId
        })
    });
    
    currentSession = await response.json();
    items = [];
    renderItems();
}

// Item management
function addItem() {
    const type = itemTypeSelect.value;
    const referenceId = parseInt(itemReferenceSelect.value);
    const rootNote = parseInt(itemRootSelect.value);
    
    if (!referenceId) {
        alert('Please select a chord or scale');
        return;
    }
    
    const referenceName = itemReferenceSelect.options[itemReferenceSelect.selectedIndex]?.text || '';
    const rootNoteName = itemRootSelect.options[itemRootSelect.selectedIndex]?.text || '';
    
    items.push({
        type,
        reference_id: referenceId,
        root_note: rootNote,
        reference_name: referenceName,
        root_note_name: rootNoteName
    });
    
    renderItems();
}

function removeItem(index) {
    items.splice(index, 1);
    renderItems();
}

function renderItems() {
    itemsList.innerHTML = items.map((item, index) => {
        return `
            <li>
                <span>${item.root_note_name} ${item.reference_name} (${item.type})</span>
                <button class="remove-btn" onclick="removeItem(${index})">×</button>
            </li>
        `;
    }).join('');
}

// Calculate harmony
async function calculateHarmony() {
    if (items.length === 0) {
        alert('Please add at least one chord or scale');
        return;
    }
    
    const tuningId = 1; // E Standard
    
    const response = await fetch(`${API_BASE}?endpoint=harmony`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tuning_id: tuningId,
            items: items
        })
    });
    
    const data = await response.json();
    
    if (data.error) {
        alert(data.error);
        return;
    }
    
    renderFretboard(data.heatmap, data.influence);
    renderInfluence(data.influence);
}

// Render fretboard
function renderFretboard(heatmap, influence) {
    const numStrings = 6;
    const numFrets = 13;
    
    // String thickness: index 0 = string 1 (low E, thickest), index 5 = string 6 (high E, thinnest)
    const stringWidths = [3, 2.5, 2, 1.6, 1.3, 1];
    
    const svgWidth = numFrets * 60 + 100;
    const svgHeight = numStrings * 50 + 80;
    
    const stringSpacing = 50;
    const fretSpacing = 60;
    const startX = 80;
    const startY = 40;
    
    let svg = `
        <svg width="${svgWidth}" height="${svgHeight}" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <style>
                    .fret { stroke: #666; stroke-width: 1; }
                    .note-circle { cursor: pointer; transition: all 0.2s; }
                    .note-circle:hover { stroke: #fff; stroke-width: 2; }
                    .note-text { fill: #fff; font-size: 10px; text-anchor: middle; dominant-baseline: central; pointer-events: none; }
                    .fret-marker { fill: #666; font-size: 10px; text-anchor: middle; }
                </style>
            </defs>
    `;
    
    // Draw fret markers (dots)
    const markerFrets = [3, 5, 7, 9, 12];
    markerFrets.forEach(fret => {
        const x = startX + fret * fretSpacing - fretSpacing / 2;
        svg += `<circle cx="${x}" cy="${svgHeight - 15}" r="4" fill="#444"/>`;
    });
    
    // Draw strings (top = string 6 high E thin, bottom = string 1 low E thick)
    for (let i = 0; i < numStrings; i++) {
        const y = startY + i * stringSpacing;
        const stringNum = numStrings - i; // 6 at top, 1 at bottom
        const thickness = stringWidths[stringNum - 1];
        svg += `<line x1="${startX}" y1="${y}" x2="${startX + (numFrets - 1) * fretSpacing}" y2="${y}" stroke="#888" stroke-width="${thickness}"/>`;
    }
    
    // Draw frets
    for (let i = 0; i < numFrets; i++) {
        const x = startX + i * fretSpacing;
        svg += `<line x1="${x}" y1="${startY}" x2="${x}" y2="${startY + (numStrings - 1) * stringSpacing}" class="fret"/>`;
        
        // Fret number
        if (i > 0) {
            svg += `<text x="${x}" y="${startY - 15}" class="fret-marker">${i}</text>`;
        }
    }
    
    // Draw notes (skip notes with 0 influence)
    heatmap.forEach(pos => {
        if (pos.percentage === 0) return;
        
        const x = startX + pos.fret * fretSpacing - fretSpacing / 2;
        // Flip: string 6 (high E) at top, string 1 (low E) at bottom
        const y = startY + (numStrings - pos.string) * stringSpacing;
        
        // Opacity based on influence percentage (min 0.1, max 1.0)
        const opacity = Math.max(0.1, pos.percentage / 100);
        
        svg += `
            <circle cx="${x}" cy="${y}" r="18" class="note-circle" 
                    fill="#e94560" fill-opacity="${opacity}"
                    data-note="${pos.note}" data-influence="${pos.influence}"/>
            <text x="${x}" y="${y}" class="note-text">${pos.note}</text>
        `;
    });
    
    svg += '</svg>';
    fretboard.innerHTML = svg;
}

// Render influence list
function renderInfluence(influence) {
    const notes = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    const maxInfluence = Math.max(...influence);
    
    const sortedNotes = influence
        .map((count, index) => ({ note: notes[index], count }))
        .filter(item => item.count > 0)
        .sort((a, b) => b.count - a.count);
    
    influenceList.innerHTML = sortedNotes.map(item => {
        const percentage = maxInfluence > 0 ? (item.count / maxInfluence) * 100 : 0;
        
        return `
            <div class="influence-item">
                <span class="note">${item.note}</span>
                <div class="bar">
                    <div class="bar-fill" style="width: ${percentage}%"></div>
                </div>
                <span class="count">${item.count}</span>
            </div>
        `;
    }).join('');
}
