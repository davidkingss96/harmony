const API_BASE = '/api/index.php';

// Fretboard state
let currentSession = null;
let items = [];
let lastHeatmap = null;
let lastInfluence = null;

// Song Builder state
let currentSong = null;
let selectedMeasureId = null;
let clipboard = null;

// DOM Elements - Fretboard
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

// DOM Elements - Song Builder
const songListView = document.getElementById('songListView');
const songEditorView = document.getElementById('songEditorView');
const songList = document.getElementById('songList');
const newSongForm = document.getElementById('newSongForm');
const newSongBtn = document.getElementById('newSongBtn');
const songNameInput = document.getElementById('songName');
const songBpmInput = document.getElementById('songBpm');
const createSongBtn = document.getElementById('createSongBtn');
const cancelSongBtn = document.getElementById('cancelSongBtn');
const backToListBtn = document.getElementById('backToListBtn');
const currentSongName = document.getElementById('currentSongName');
const currentSongBpm = document.getElementById('currentSongBpm');
const songElementTypeSelect = document.getElementById('songElementType');
const songElementRefSelect = document.getElementById('songElementRef');
const songElementRootSelect = document.getElementById('songElementRoot');
const songEventNotesInput = document.getElementById('songEventNotes');
const selectedMeasureInfo = document.getElementById('selectedMeasureInfo');
const selectedMeasureLabel = document.getElementById('selectedMeasureLabel');
const clearSelectionBtn = document.getElementById('clearSelectionBtn');
const assignBtn = document.getElementById('assignBtn');
const pasteBtn = document.getElementById('pasteBtn');
const sectionsContainer = document.getElementById('sectionsContainer');
const addSectionBtn = document.getElementById('addSectionBtn');

// Tab navigation
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadChords();
    loadScales();
    loadNotes();
    loadTunings();
    setupEventListeners();
    loadSongs();
    loadPlayerSongs();
});

// ============================================
// TAB NAVIGATION
// ============================================

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');

        if (btn.dataset.tab === 'player') {
            loadPlayerSongs();
        }
    });
});

// ============================================
// DATA LOADING
// ============================================

async function loadChords() {
    const response = await fetch(`${API_BASE}?endpoint=chords`);
    const chords = await response.json();
    updateReferenceSelect('CHORD', chords);
    updateSongElementRef('CHORD', chords);
}

async function loadScales() {
    const response = await fetch(`${API_BASE}?endpoint=scales`);
    const scales = await response.json();
    updateReferenceSelect('SCALE', scales);
    updateSongElementRef('SCALE', scales);
}

async function loadNotes() {
    const response = await fetch(`${API_BASE}?endpoint=notes`);
    const notes = await response.json();
    
    const html = notes.map(n => `<option value="${n.chromatic_position}">${n.name}</option>`).join('');
    itemRootSelect.innerHTML = html;
    songElementRootSelect.innerHTML = html;
}

async function loadTunings() {
    const response = await fetch(`${API_BASE}?endpoint=tunings`);
    const tunings = await response.json();
}

function updateReferenceSelect(type, data) {
    if (itemTypeSelect.value === type) {
        itemReferenceSelect.innerHTML = data.map(item => 
            `<option value="${item.id}">${item.name}</option>`
        ).join('');
    }
}

function updateSongElementRef(type, data) {
    if (songElementTypeSelect.value === type) {
        songElementRefSelect.innerHTML = data.map(item => 
            `<option value="${item.id}">${item.name}</option>`
        ).join('');
    }
}

// ============================================
// EVENT LISTENERS
// ============================================

function setupEventListeners() {
    // Fretboard
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
    fullscreenBtn.addEventListener('click', toggleFullscreen);
    landscapeBtn.addEventListener('click', openLandscape);
    closeLandscapeBtn.addEventListener('click', closeLandscape);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !landscapeOverlay.classList.contains('hidden')) {
            closeLandscape();
        }
    });

    // Song Builder - Song list
    newSongBtn.addEventListener('click', () => {
        newSongForm.classList.remove('hidden');
        newSongBtn.classList.add('hidden');
        songNameInput.value = '';
        songBpmInput.value = '';
        songNameInput.focus();
    });
    
    cancelSongBtn.addEventListener('click', () => {
        newSongForm.classList.add('hidden');
        newSongBtn.classList.remove('hidden');
    });
    
    createSongBtn.addEventListener('click', createSong);
    
    // Song Builder - Editor
    backToListBtn.addEventListener('click', goToSongList);
    clearSelectionBtn.addEventListener('click', clearMeasureSelection);
    assignBtn.addEventListener('click', assignToSelectedMeasure);
    pasteBtn.addEventListener('click', pasteMeasure);
    addSectionBtn.addEventListener('click', addSection);
    
    songElementTypeSelect.addEventListener('change', async () => {
        const type = songElementTypeSelect.value;
        const endpoint = type === 'CHORD' ? 'chords' : 'scales';
        const response = await fetch(`${API_BASE}?endpoint=${endpoint}`);
        const data = await response.json();
        updateSongElementRef(type, data);
    });
}

// ============================================
// FULLSCREEN & LANDSCAPE
// ============================================

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
}

function openLandscape() {
    if (!lastHeatmap || !lastInfluence) {
        alert('Primero calcula un mapa');
        return;
    }
    landscapeOverlay.classList.remove('hidden');
    renderFretboardInContainer(landscapeFretboard, lastHeatmap, true);
    if (screen.orientation && screen.orientation.lock) {
        screen.orientation.lock('landscape').catch(() => {});
    }
}

function closeLandscape() {
    landscapeOverlay.classList.add('hidden');
    if (screen.orientation && screen.orientation.unlock) {
        screen.orientation.unlock();
    }
}

// ============================================
// FRETBOARD - SESSION MANAGEMENT
// ============================================

async function createSession() {
    const name = sessionNameInput.value.trim();
    if (!name) { alert('Ingresa un nombre'); return; }
    
    const response = await fetch(`${API_BASE}?endpoint=sessions`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, tuning_id: 1 })
    });
    
    currentSession = await response.json();
    items = [];
    renderItems();
}

function addItem() {
    const type = itemTypeSelect.value;
    const referenceId = parseInt(itemReferenceSelect.value);
    const rootNote = parseInt(itemRootSelect.value);
    
    if (!referenceId) { alert('Selecciona un elemento'); return; }
    
    const referenceName = itemReferenceSelect.options[itemReferenceSelect.selectedIndex]?.text || '';
    const rootNoteName = itemRootSelect.options[itemRootSelect.selectedIndex]?.text || '';
    
    items.push({ type, reference_id: referenceId, root_note: rootNote, reference_name: referenceName, root_note_name: rootNoteName });
    renderItems();
}

function removeItem(index) {
    items.splice(index, 1);
    renderItems();
}

function renderItems() {
    itemsList.innerHTML = items.map((item, index) => `
        <li>
            <span>${item.root_note_name} ${item.reference_name} (${item.type})</span>
            <button class="remove-btn" onclick="removeItem(${index})">×</button>
        </li>
    `).join('');
}

// ============================================
// FRETBOARD - CALCULATE HARMONY
// ============================================

async function calculateHarmony() {
    if (items.length === 0) { alert('Agrega al menos un elemento'); return; }
    
    const response = await fetch(`${API_BASE}?endpoint=harmony`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tuning_id: 1, items })
    });
    
    const data = await response.json();
    if (data.error) { alert(data.error); return; }
    
    lastHeatmap = data.heatmap;
    lastInfluence = data.influence;
    renderFretboard(data.heatmap, data.influence);
    renderInfluence(data.influence);
}

// ============================================
// FRETBOARD - SVG RENDERING
// ============================================

function getSizes(containerWidth, landscape = false) {
    const padding = landscape ? 40 : 32;
    const availableWidth = containerWidth - padding;
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
        fretSpacing, stringSpacing, startX, startY, noteRadius, fontSize,
        svgWidth: startX + (12 * fretSpacing) + fretSpacing,
        svgHeight: startY + (5 * stringSpacing) + stringSpacing + 30
    };
}

function renderFretboard(heatmap, influence) {
    const containerWidth = fretboard.parentElement.clientWidth - 32;
    renderFretboardInContainer(fretboard, heatmap, false, containerWidth);
}

function renderFretboardInContainer(container, heatmap, landscape = false, containerWidth = null) {
    const numStrings = 6;
    const numFrets = 13;
    const stringWidths = [3, 2.5, 2, 1.6, 1.3, 1];
    
    if (!containerWidth) containerWidth = container.parentElement.clientWidth - 32;
    const s = getSizes(containerWidth, landscape);
    
    let svg = `<svg width="${s.svgWidth}" height="${s.svgHeight}" viewBox="0 0 ${s.svgWidth} ${s.svgHeight}" xmlns="http://www.w3.org/2000/svg">
        <defs><style>
            .fret { stroke: #666; stroke-width: 1; }
            .note-circle { cursor: pointer; transition: all 0.2s; }
            .note-circle:hover { stroke: #fff; stroke-width: 2; }
            .note-text { fill: #fff; font-size: ${s.fontSize}px; text-anchor: middle; dominant-baseline: central; pointer-events: none; font-weight: 600; }
            .fret-marker { fill: #666; font-size: ${s.fontSize}px; text-anchor: middle; }
        </style></defs>`;
    
    [3, 5, 7, 9, 12].forEach(fret => {
        svg += `<circle cx="${s.startX + fret * s.fretSpacing - s.fretSpacing / 2}" cy="${s.svgHeight - 12}" r="3" fill="#444"/>`;
    });
    
    for (let i = 0; i < numStrings; i++) {
        const y = s.startY + i * s.stringSpacing;
        const stringNum = numStrings - i;
        svg += `<line x1="${s.startX}" y1="${y}" x2="${s.startX + (numFrets - 1) * s.fretSpacing}" y2="${y}" stroke="#888" stroke-width="${stringWidths[stringNum - 1]}"/>`;
    }
    
    for (let i = 0; i < numFrets; i++) {
        const x = s.startX + i * s.fretSpacing;
        svg += `<line x1="${x}" y1="${s.startY}" x2="${x}" y2="${s.startY + (numStrings - 1) * s.stringSpacing}" class="fret"/>`;
        if (i > 0) svg += `<text x="${x}" y="${s.startY - 8}" class="fret-marker">${i}</text>`;
    }
    
    heatmap.forEach(pos => {
        if (pos.percentage === 0) return;
        const x = s.startX + pos.fret * s.fretSpacing - s.fretSpacing / 2;
        const y = s.startY + (numStrings - pos.string) * s.stringSpacing;
        const opacity = Math.max(0.1, pos.percentage / 100);
        svg += `<circle cx="${x}" cy="${y}" r="${s.noteRadius}" class="note-circle" fill="#e94560" fill-opacity="${opacity}" data-note="${pos.note}" data-influence="${pos.influence}"/>
                <text x="${x}" y="${y}" class="note-text">${pos.note}</text>`;
    });
    
    svg += '</svg>';
    container.innerHTML = svg;
}

function renderInfluence(influence) {
    const notes = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    const maxInfluence = Math.max(...influence);
    const sorted = influence.map((count, i) => ({ note: notes[i], count })).filter(n => n.count > 0).sort((a, b) => b.count - a.count);
    
    influenceList.innerHTML = sorted.map(item => {
        const pct = maxInfluence > 0 ? (item.count / maxInfluence) * 100 : 0;
        return `<div class="influence-item">
            <span class="note">${item.note}</span>
            <div class="bar"><div class="bar-fill" style="width: ${pct}%"></div></div>
            <span class="count">${item.count}</span>
        </div>`;
    }).join('');
}

// ============================================
// SONG BUILDER - VIEWS
// ============================================

function goToSongList() {
    songListView.classList.remove('hidden');
    songEditorView.classList.add('hidden');
    selectedMeasureId = null;
    loadSongs();
}

function openSongEditor(song) {
    currentSong = song;
    songListView.classList.add('hidden');
    songEditorView.classList.remove('hidden');
    currentSongName.textContent = currentSong.name;
    currentSongBpm.textContent = currentSong.bpm + ' BPM';
    selectedMeasureId = null;
    selectedMeasureInfo.classList.add('hidden');
    renderSongSections();
}

// ============================================
// SONG BUILDER - CRUD
// ============================================

async function createSong() {
    const name = songNameInput.value.trim();
    const bpm = parseFloat(songBpmInput.value);
    
    if (!name) { alert('Ingresa el nombre de la cancion'); return; }
    if (isNaN(bpm) || bpm <= 0) { alert('Ingresa un BPM valido'); return; }
    
    const response = await fetch(`${API_BASE}?endpoint=songs`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, bpm: bpm.toFixed(2), tuning_id: 1 })
    });
    
    const song = await response.json();
    song.sections = [];
    newSongForm.classList.add('hidden');
    newSongBtn.classList.remove('hidden');
    openSongEditor(song);
}

async function loadSongs() {
    const response = await fetch(`${API_BASE}?endpoint=songs`);
    const songs = await response.json();
    
    if (songs.length === 0) {
        songList.innerHTML = '<div class="song-list-empty">No hay canciones creadas</div>';
        return;
    }
    
    songList.innerHTML = songs.map(song => `
        <div class="song-list-item" onclick="loadAndOpenSong(${song.id})">
            <div class="song-list-info">
                <span class="song-list-name">${song.name}</span>
                <span class="song-list-bpm">${song.bpm} BPM</span>
            </div>
            <button class="song-list-delete" onclick="event.stopPropagation(); deleteSong(${song.id})">×</button>
        </div>
    `).join('');
}

async function loadAndOpenSong(songId) {
    const response = await fetch(`${API_BASE}?endpoint=songs&id=${songId}`);
    const song = await response.json();
    openSongEditor(song);
}

async function deleteSong(songId) {
    if (!confirm('Eliminar esta cancion?')) return;
    await fetch(`${API_BASE}?endpoint=songs&id=${songId}`, { method: 'DELETE' });
    loadSongs();
}

async function addSection() {
    if (!currentSong) { alert('Primero crea una cancion'); return; }
    
    const name = prompt('Nombre de la seccion (Intro, Verso, Solo, Jam...):');
    if (!name) return;
    
    if (!currentSong.sections) currentSong.sections = [];
    const colors = ['#3498db', '#2ecc71', '#e94560', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22'];
    const color = colors[currentSong.sections.length % colors.length];
    
    const response = await fetch(`${API_BASE}?endpoint=song-sections`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ song_id: currentSong.id, name, color })
    });
    
    const section = await response.json();
    currentSong.sections.push({ ...section, measures: [] });
    renderSongSections();
}

async function deleteSection(sectionId) {
    if (!confirm('Eliminar esta seccion y todos sus compases?')) return;
    await fetch(`${API_BASE}?endpoint=song-sections&id=${sectionId}`, { method: 'DELETE' });
    currentSong.sections = currentSong.sections.filter(s => s.id !== sectionId);
    renderSongSections();
}

async function addMeasure(sectionId) {
    const response = await fetch(`${API_BASE}?endpoint=song-measures`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ section_id: sectionId })
    });
    
    const measure = await response.json();
    const section = currentSong.sections.find(s => s.id === sectionId);
    if (section) {
        if (!section.measures) section.measures = [];
        section.measures.push({ ...measure, events: [] });
        renderSongSections();
    }
}

async function deleteMeasure(measureId) {
    if (!confirm('Eliminar este compas?')) return;
    await fetch(`${API_BASE}?endpoint=song-measures&id=${measureId}`, { method: 'DELETE' });
    currentSong.sections.forEach(section => {
        if (section.measures) {
            section.measures = section.measures.filter(m => m.id !== measureId);
        }
    });
    if (selectedMeasureId === measureId) clearMeasureSelection();
    renderSongSections();
}

// ============================================
// SONG BUILDER - MEASURES
// ============================================

function selectMeasure(measureId) {
    selectedMeasureId = measureId;
    const measure = findMeasureById(measureId);
    if (!measure) return;
    
    const globalPos = getGlobalMeasurePosition(measure);
    selectedMeasureLabel.textContent = `#${globalPos}`;
    selectedMeasureInfo.classList.remove('hidden');
    
    if (measure.events && measure.events.length > 0) {
        const firstEvent = measure.events[0];
        if (firstEvent.element_type) {
            songElementTypeSelect.value = firstEvent.element_type;
            songElementTypeSelect.dispatchEvent(new Event('change'));
            setTimeout(() => {
                songElementRefSelect.value = firstEvent.element_id;
                songElementRootSelect.value = firstEvent.root_note;
            }, 50);
        }
        if (firstEvent.notes) {
            songEventNotesInput.value = firstEvent.notes;
        }
    }
    
    renderSongSections();
}

function clearMeasureSelection() {
    selectedMeasureId = null;
    selectedMeasureInfo.classList.add('hidden');
    songEventNotesInput.value = '';
    renderSongSections();
}

function findMeasureById(measureId) {
    for (const section of currentSong.sections || []) {
        for (const m of section.measures || []) {
            if (m.id === measureId) return m;
        }
    }
    return null;
}

function getGlobalMeasurePosition(measure) {
    let position = 0;
    for (const section of currentSong.sections || []) {
        for (const m of section.measures || []) {
            position++;
            if (m.id === measure.id) return position;
        }
    }
    return position;
}

async function applyToMeasure(measureId) {
    const type = songElementTypeSelect.value;
    const elementId = parseInt(songElementRefSelect.value);
    const rootNote = parseInt(songElementRootSelect.value);
    const notes = songEventNotesInput.value.trim() || null;
    
    if (!elementId) { alert('Selecciona un elemento'); return; }
    
    await fetch(`${API_BASE}?endpoint=song-events`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            measure_id: measureId,
            beat: 1,
            element_type: type,
            element_id: elementId,
            root_note: rootNote,
            notes
        })
    });
    
    const response = await fetch(`${API_BASE}?endpoint=songs&id=${currentSong.id}`);
    currentSong = await response.json();
    renderSongSections();
}

function assignToSelectedMeasure() {
    if (!selectedMeasureId) {
        alert('Selecciona un compas primero');
        return;
    }
    applyToMeasure(selectedMeasureId);
}

// ============================================
// SONG BUILDER - COPY / PASTE
// ============================================

function copyMeasure(measureId) {
    const measure = findMeasureById(measureId);
    if (!measure || !measure.events || measure.events.length === 0) {
        alert('Este compas esta vacio');
        return;
    }
    clipboard = JSON.parse(JSON.stringify(measure.events));
    pasteBtn.disabled = false;
}

function pasteMeasure() {
    if (!selectedMeasureId || !clipboard) return;
    pasteEventsToMeasure(selectedMeasureId, clipboard);
}

async function pasteEventsToMeasure(measureId, events) {
    for (const event of events) {
        await fetch(`${API_BASE}?endpoint=song-events`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                measure_id: measureId,
                beat: event.beat,
                element_type: event.element_type,
                element_id: event.element_id,
                root_note: event.root_note,
                notes: event.notes || null
            })
        });
    }
    const response = await fetch(`${API_BASE}?endpoint=songs&id=${currentSong.id}`);
    currentSong = await response.json();
    renderSongSections();
}

// ============================================
// SONG BUILDER - RENDER
// ============================================

function renderSongSections() {
    if (!currentSong || !currentSong.sections) {
        sectionsContainer.innerHTML = '';
        return;
    }
    
    sectionsContainer.innerHTML = currentSong.sections.map(section => {
        const measuresHtml = (section.measures || []).map(measure => {
            const firstEvent = measure.events && measure.events.length > 0 ? measure.events[0] : null;
            const hasEvent = firstEvent !== null;
            const chordDisplay = hasEvent ? `${firstEvent.root_note_name}${firstEvent.element_name.charAt(0)}` : '';
            const globalPos = getGlobalMeasurePosition(measure);
            const isSelected = selectedMeasureId === measure.id;
            
            return `<div class="measure-card ${hasEvent ? 'has-event' : ''} ${isSelected ? 'selected' : ''}" onclick="selectMeasure(${measure.id})">
                <div class="measure-card-header">
                    <div class="measure-number">#${globalPos}</div>
                    <div class="measure-actions">
                        <button class="measure-action-btn" onclick="event.stopPropagation(); copyMeasure(${measure.id})" title="Copiar">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2"/>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                        </button>
                        <button class="measure-action-btn danger" onclick="event.stopPropagation(); deleteMeasure(${measure.id})" title="Eliminar">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="measure-chord">${chordDisplay || '&nbsp;'}</div>
                <div class="measure-beats">
                    ${[1,2,3,4].map(b => {
                        const hasBeat = measure.events && measure.events.some(e => e.beat === b);
                        return `<div class="beat-dot ${hasBeat ? 'filled' : ''}"></div>`;
                    }).join('')}
                </div>
            </div>`;
        }).join('');
        
        return `<div class="section-card" style="border-left-color: ${section.color}">
            <div class="section-header">
                <div class="section-name">
                    <span class="section-color-dot" style="background: ${section.color}"></span>
                    ${section.name}
                </div>
                <div class="section-actions">
                    <button class="add-measure-btn" onclick="addMeasure(${section.id})">+ Compas</button>
                    <button class="delete-section-btn" onclick="deleteSection(${section.id})">×</button>
                </div>
            </div>
            <div class="measures-grid">
                ${measuresHtml || '<div style="padding: 12px; color: var(--text-muted); font-size: 0.85rem;">Sin compases</div>'}
            </div>
        </div>`;
    }).join('');
}

// ============================================
// RESIZE HANDLER
// ============================================

let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        if (lastHeatmap && lastInfluence) {
            renderFretboard(lastHeatmap, lastInfluence);
        }
    }, 250);
});

// ============================================
// PLAYER
// ============================================

const audioEngine = new AudioEngine();
const player = new Player(audioEngine);
let songMap = null;
let playerSongData = null;

// DOM - Player
const playerSetup = document.getElementById('playerSetup');
const playerActive = document.getElementById('playerActive');
const playerSongListEl = document.getElementById('playerSongList');
const playerStartConfig = document.getElementById('playerStartConfig');
const playerStartMeasure = document.getElementById('playerStartMeasure');
const playerPlayBtn = document.getElementById('playerPlayBtn');
const playerStopBtn = document.getElementById('playerStopBtn');
const playerPauseBtn = document.getElementById('playerPauseBtn');
const playerLoopBtn = document.getElementById('playerLoopBtn');
const playerPerformanceBtn = document.getElementById('playerPerformanceBtn');
const playerBackBtn = document.getElementById('playerBackBtn');
const playerFretboard = document.getElementById('playerFretboard');
const playerNextChord = document.getElementById('playerNextChord');
const nextChordName = document.getElementById('nextChordName');
const hudSectionName = document.getElementById('hudSectionName');
const hudSectionColor = document.getElementById('hudSectionColor');
const hudBeatDots = document.getElementById('hudBeatDots');
const hudBeatsRemaining = document.getElementById('hudBeatsRemaining');
const hudMeasureNum = document.getElementById('hudMeasureNum');
const hudMeasureTotal = document.getElementById('hudMeasureTotal');
const hudBpm = document.getElementById('hudBpm');
const hudTimeSig = document.getElementById('hudTimeSig');
const playFromEditorBtn = document.getElementById('playFromEditorBtn');
const songMapContainer = document.getElementById('songMap');

function setupPlayerListeners() {
    playerPlayBtn.addEventListener('click', startPlayer);
    playerStopBtn.addEventListener('click', stopPlayer);
    playerPauseBtn.addEventListener('click', togglePause);
    playerLoopBtn.addEventListener('click', togglePlayerLoop);
    playerPerformanceBtn.addEventListener('click', togglePerformanceMode);
    playerBackBtn.addEventListener('click', exitPlayer);
    playFromEditorBtn.addEventListener('click', playFromEditor);

    player.onTick = onPlayerTick;
    player.onMeasureChange = onPlayerMeasureChange;
    player.onEnd = onPlayerEnd;

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('performance-mode')) {
            togglePerformanceMode();
        }
    });
}

async function loadPlayerSongs() {
    const response = await fetch(`${API_BASE}?endpoint=songs`);
    const songs = await response.json();

    if (songs.length === 0) {
        playerSongListEl.innerHTML = '<div class="song-list-empty">No hay canciones. Crea una en Song Builder.</div>';
        return;
    }

    playerSongListEl.innerHTML = songs.map(song => `
        <div class="player-song-item" onclick="selectPlayerSong(${song.id}, this)">
            <div class="song-info">
                <span class="song-name">${song.name}</span>
                <span class="song-meta">${song.bpm} BPM</span>
            </div>
        </div>
    `).join('');
}

async function selectPlayerSong(songId, el) {
    document.querySelectorAll('.player-song-item').forEach(item => item.classList.remove('selected'));
    if (el) el.classList.add('selected');

    const response = await fetch(`${API_BASE}?endpoint=songs&id=${songId}&player=1`);
    playerSongData = await response.json();

    if (!playerSongData.player_data || playerSongData.player_data.total_measures === 0) {
        alert('Esta cancion no tiene compases.');
        playerSongData = null;
        return;
    }

    populateStartMeasureSelect(playerSongData.player_data);
    playerStartConfig.classList.remove('hidden');
}

function populateStartMeasureSelect(playerData) {
    let html = '';
    playerData.measures.forEach(m => {
        const ev = m.events && m.events.length > 0 ? m.events[0] : null;
        const label = ev ? `${ev.root_note_name}${ev.element_name.charAt(0)}` : '(vacio)';
        html += `<option value="${m.global_index}">#${m.global_index + 1} ${m.section_name} - ${label}</option>`;
    });
    playerStartMeasure.innerHTML = html;
}

function startPlayer() {
    if (!playerSongData) { alert('Selecciona una cancion primero'); return; }
    if (playerSongData.player_data.total_measures === 0) { alert('La cancion no tiene compases'); return; }

    player.load(playerSongData.player_data);

    songMap = new SongMap(songMapContainer);
    songMap.load(playerSongData.player_data.measures);
    songMap.onMeasureClick = (index) => {
        player.seekToMeasure(index);
    };

    playerSetup.classList.add('hidden');
    playerActive.classList.remove('hidden');

    updatePlayerHud(player.state.currentMeasureIndex, player.state.currentBeat);

    const startIndex = parseInt(playerStartMeasure.value) || 0;
    player.play(startIndex);
}

function stopPlayer() {
    player.stop();
}

function togglePause() {
    if (player.state.isPlaying) {
        player.stop();
        playerPauseBtn.textContent = '▶';
    } else {
        player.play(player.state.currentMeasureIndex);
        playerPauseBtn.textContent = '⏸';
    }
}

function togglePlayerLoop() {
    if (!songMap) return;

    if (player.loop.active) {
        player.clearLoop();
        songMap.clearLoop();
        playerLoopBtn.classList.remove('active');
    } else if (songMap.loopStartIndex !== null) {
        alert('Selecciona el compas final del loop en el mapa');
    } else {
        alert('Primero selecciona el compas de inicio del loop en el mapa');
    }
}

function setPlayerLoopFromMap() {
    if (!songMap || songMap.loopRange === null) return;
    player.toggleLoop(songMap.loopRange.start, songMap.loopRange.end);
    playerLoopBtn.classList.add('active');
}

function togglePerformanceMode() {
    document.body.classList.toggle('performance-mode');
    playerPerformanceBtn.classList.toggle('active');
}

function exitPlayer() {
    player.stop();
    playerSetup.classList.remove('hidden');
    playerActive.classList.add('hidden');
    document.body.classList.remove('performance-mode');
    playerLoopBtn.classList.remove('active');
    playerPerformanceBtn.classList.remove('active');
    playerPauseBtn.textContent = '⏸';
    playerSongData = null;
    loadPlayerSongs();
}

async function playFromEditor() {
    if (!currentSong) return;
    const response = await fetch(`${API_BASE}?endpoint=songs&id=${currentSong.id}&player=1`);
    playerSongData = await response.json();

    if (!playerSongData.player_data || playerSongData.player_data.total_measures === 0) {
        alert('Agrega compases antes de reproducir');
        return;
    }

    // Switch to player tab
    tabBtns.forEach(b => b.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));
    document.querySelector('[data-tab="player"]').classList.add('active');
    document.getElementById('tab-player').classList.add('active');

    populateStartMeasureSelect(playerSongData.player_data);
    playerStartConfig.classList.remove('hidden');

    // Auto-select this song in the list
    playerSongListEl.innerHTML = `
        <div class="player-song-item selected">
            <div class="song-info">
                <span class="song-name">${playerSongData.name}</span>
                <span class="song-meta">${playerSongData.bpm} BPM</span>
            </div>
        </div>
    `;
}

function onPlayerTick(data) {
    updateBeatDots(data.currentBeat, data.beatsTotal);
    hudBeatsRemaining.textContent = `${data.beatsRemaining} beat${data.beatsRemaining !== 1 ? 's' : ''}`;
}

function onPlayerMeasureChange(index) {
    const measure = player.getCurrentMeasure();
    if (!measure) return;

    const event = player.getCurrentEvent();
    const nextEvent = player.getNextEvent();

    // Render fretboard with current heatmap
    if (event && event.heatmap && event.heatmap.length > 0) {
        const containerWidth = playerFretboard.parentElement.clientWidth - 16;
        renderFretboardInContainer(playerFretboard, event.heatmap, false, containerWidth);
    }

    // Update next chord indicator
    if (nextEvent && nextEvent.root_note_name && nextEvent.element_name) {
        nextChordName.textContent = `${nextEvent.root_note_name} ${nextEvent.element_name}`;
        playerNextChord.classList.remove('hidden');
    } else {
        playerNextChord.classList.add('hidden');
    }

    // Update HUD
    updatePlayerHud(index, 1);

    // Update song map cursor
    if (songMap) {
        songMap.updateCursor(index);
    }

    // Update loop state from map
    if (songMap && songMap.loopRange && !player.loop.active) {
        setPlayerLoopFromMap();
    }
}

function onPlayerEnd() {
    playerPauseBtn.textContent = '▶';
}

function updatePlayerHud(measureIndex, beat) {
    const measure = player.getCurrentMeasure();
    if (!measure) return;

    hudSectionName.textContent = measure.section_name || '-';
    hudSectionColor.style.background = measure.section_color || '#e94560';
    hudMeasureNum.textContent = measureIndex + 1;
    hudMeasureTotal.textContent = player.song ? player.song.total_measures : 0;
    hudBpm.textContent = player.bpm;
    hudTimeSig.textContent = `${player.timeSig.num}/${player.timeSig.den}`;
    updateBeatDots(beat, player.timeSig.num);
}

function updateBeatDots(currentBeat, totalBeats) {
    let html = '';
    for (let i = 1; i <= totalBeats; i++) {
        const isAccent = i === 1;
        const isActive = i === currentBeat;
        let cls = 'hud-beat-dot';
        if (isActive && isAccent) cls += ' active accent';
        else if (isActive) cls += ' active';
        html += `<div class="${cls}"></div>`;
    }
    hudBeatDots.innerHTML = html;
}

// Init player listeners
setupPlayerListeners();
