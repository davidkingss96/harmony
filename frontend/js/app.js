const API_BASE = '/api/index.php';

let currentSession = null;
let items = [];
let lastHeatmap = null;
let lastInfluence = null;

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
const fullscreenBtn = document.getElementById('fullscreenBtn');
const landscapeBtn = document.getElementById('landscapeBtn');
const landscapeOverlay = document.getElementById('landscapeOverlay');
const landscapeFretboard = document.getElementById('landscapeFretboard');
const closeLandscapeBtn = document.getElementById('closeLandscapeBtn');

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
    
    // Fullscreen
    fullscreenBtn.addEventListener('click', toggleFullscreen);
    fullscreenBtn.addEventListener('touchend', (e) => {
        e.preventDefault();
        toggleFullscreen();
    });
    
    // Landscape mode
    landscapeBtn.addEventListener('click', openLandscape);
    landscapeBtn.addEventListener('touchend', (e) => {
        e.preventDefault();
        openLandscape();
    });
    
    closeLandscapeBtn.addEventListener('click', closeLandscape);
    closeLandscapeBtn.addEventListener('touchend', (e) => {
        e.preventDefault();
        closeLandscape();
    });
    
    // Close landscape on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !landscapeOverlay.classList.contains('hidden')) {
            closeLandscape();
        }
    });
}

// Fullscreen
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.log('Fullscreen not supported:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Landscape mode
function openLandscape() {
    if (!lastHeatmap || !lastInfluence) {
        alert('Primero calcula un mapa');
        return;
    }
    
    landscapeOverlay.classList.remove('hidden');
    renderFretboardInContainer(landscapeFretboard, lastHeatmap, true);
    
    // Try to lock to landscape
    if (screen.orientation && screen.orientation.lock) {
        screen.orientation.lock('landscape').catch(() => {});
    }
}

function closeLandscape() {
    landscapeOverlay.classList.add('hidden');
    
    // Unlock orientation
    if (screen.orientation && screen.orientation.unlock) {
        screen.orientation.unlock();
    }
}

// Session management
async function createSession() {
    const name = sessionNameInput.value.trim();
    if (!name) {
        alert('Please enter a session name');
        return;
    }
    
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
    
    const tuningId = 1;
    
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
    
    lastHeatmap = data.heatmap;
    lastInfluence = data.influence;
    
    renderFretboard(data.heatmap, data.influence);
    renderInfluence(data.influence);
}

// Get responsive sizes based on container width
function getSizes(containerWidth, landscape = false) {
    const padding = landscape ? 40 : 32;
    const availableWidth = containerWidth - padding;
    
    // Calculate fret spacing to fit container
    const maxFretSpacing = landscape ? 70 : 55;
    const minFretSpacing = 35;
    let fretSpacing = Math.min(maxFretSpacing, availableWidth / 13);
    fretSpacing = Math.max(minFretSpacing, fretSpacing);
    
    const stringSpacing = fretSpacing * 0.8;
    const startX = fretSpacing * 1.2;
    const startY = stringSpacing * 0.8;
    const noteRadius = fretSpacing * 0.3;
    const fontSize = Math.max(8, fretSpacing * 0.16);
    
    return {
        fretSpacing,
        stringSpacing,
        startX,
        startY,
        noteRadius,
        fontSize,
        svgWidth: startX + (12 * fretSpacing) + fretSpacing,
        svgHeight: startY + (5 * stringSpacing) + stringSpacing + 30
    };
}

// Render fretboard
function renderFretboard(heatmap, influence) {
    const containerWidth = fretboard.parentElement.clientWidth - 32;
    renderFretboardInContainer(fretboard, heatmap, false, containerWidth);
}

function renderFretboardInContainer(container, heatmap, landscape = false, containerWidth = null) {
    const numStrings = 6;
    const numFrets = 13;
    
    const stringWidths = [3, 2.5, 2, 1.6, 1.3, 1];
    
    if (!containerWidth) {
        containerWidth = container.parentElement.clientWidth - 32;
    }
    
    const sizes = getSizes(containerWidth, landscape);
    
    let svg = `
        <svg width="${sizes.svgWidth}" height="${sizes.svgHeight}" viewBox="0 0 ${sizes.svgWidth} ${sizes.svgHeight}" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <style>
                    .fret { stroke: #666; stroke-width: 1; }
                    .note-circle { cursor: pointer; transition: all 0.2s; }
                    .note-circle:hover { stroke: #fff; stroke-width: 2; }
                    .note-text { fill: #fff; font-size: ${sizes.fontSize}px; text-anchor: middle; dominant-baseline: central; pointer-events: none; font-weight: 600; }
                    .fret-marker { fill: #666; font-size: ${sizes.fontSize}px; text-anchor: middle; }
                </style>
            </defs>
    `;
    
    // Fret markers (dots)
    const markerFrets = [3, 5, 7, 9, 12];
    markerFrets.forEach(fret => {
        const x = sizes.startX + fret * sizes.fretSpacing - sizes.fretSpacing / 2;
        svg += `<circle cx="${x}" cy="${sizes.svgHeight - 12}" r="3" fill="#444"/>`;
    });
    
    // Strings (top = string 6 high E thin, bottom = string 1 low E thick)
    for (let i = 0; i < numStrings; i++) {
        const y = sizes.startY + i * sizes.stringSpacing;
        const stringNum = numStrings - i;
        const thickness = stringWidths[stringNum - 1];
        svg += `<line x1="${sizes.startX}" y1="${y}" x2="${sizes.startX + (numFrets - 1) * sizes.fretSpacing}" y2="${y}" stroke="#888" stroke-width="${thickness}"/>`;
    }
    
    // Frets
    for (let i = 0; i < numFrets; i++) {
        const x = sizes.startX + i * sizes.fretSpacing;
        svg += `<line x1="${x}" y1="${sizes.startY}" x2="${x}" y2="${sizes.startY + (numStrings - 1) * sizes.stringSpacing}" class="fret"/>`;
        
        if (i > 0) {
            svg += `<text x="${x}" y="${sizes.startY - 8}" class="fret-marker">${i}</text>`;
        }
    }
    
    // Notes
    heatmap.forEach(pos => {
        if (pos.percentage === 0) return;
        
        const x = sizes.startX + pos.fret * sizes.fretSpacing - sizes.fretSpacing / 2;
        const y = sizes.startY + (numStrings - pos.string) * sizes.stringSpacing;
        
        const opacity = Math.max(0.1, pos.percentage / 100);
        
        svg += `
            <circle cx="${x}" cy="${y}" r="${sizes.noteRadius}" class="note-circle" 
                    fill="#e94560" fill-opacity="${opacity}"
                    data-note="${pos.note}" data-influence="${pos.influence}"/>
            <text x="${x}" y="${y}" class="note-text">${pos.note}</text>
        `;
    });
    
    svg += '</svg>';
    container.innerHTML = svg;
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

// Re-render fretboard on resize
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        if (lastHeatmap && lastInfluence) {
            renderFretboard(lastHeatmap, lastInfluence);
        }
    }, 250);
});
