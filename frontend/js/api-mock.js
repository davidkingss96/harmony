// Mock API para desarrollo local - intercepta fetch calls
const originalFetch = window.fetch;
const mockDataStore = {};

const mockData = {
  '/api/notes': [
    { chromatic_position: 0, name: 'C' },
    { chromatic_position: 1, name: 'C#' },
    { chromatic_position: 2, name: 'D' },
    { chromatic_position: 3, name: 'D#' },
    { chromatic_position: 4, name: 'E' },
    { chromatic_position: 5, name: 'F' },
    { chromatic_position: 6, name: 'F#' },
    { chromatic_position: 7, name: 'G' },
    { chromatic_position: 8, name: 'G#' },
    { chromatic_position: 9, name: 'A' },
    { chromatic_position: 10, name: 'A#' },
    { chromatic_position: 11, name: 'B' }
  ],
  '/api/chords': [
    { id: 1, name: 'Major' },
    { id: 2, name: 'Minor' },
    { id: 3, name: 'Dominant 7' },
    { id: 4, name: 'Minor 7' },
    { id: 5, name: 'Major 7' },
    { id: 6, name: 'Diminished' },
    { id: 7, name: 'Augmented' },
    { id: 8, name: 'Sus2' },
    { id: 9, name: 'Sus4' }
  ],
  '/api/scales': [
    { id: 1, name: 'Major' },
    { id: 2, name: 'Minor Natural' },
    { id: 3, name: 'Minor Harmonic' },
    { id: 4, name: 'Pentatonic Minor' },
    { id: 5, name: 'Pentatonic Major' },
    { id: 6, name: 'Blues' },
    { id: 7, name: 'Dorian' },
    { id: 8, name: 'Phrygian' },
    { id: 9, name: 'Lydian' },
    { id: 10, name: 'Mixolydian' }
  ],
  '/api/tunings': [
    { id: 1, name: 'Standard', notes: [40, 45, 50, 55, 59, 64] }
  ]
};

// Mock de almacenamiento de datos
const dbStore = {
  songs: {},
  sessions: {},
  nextIds: {
    songs: 1,
    sessions: 1,
    sections: 1,
    measures: 1
  }
};

window.fetch = function(url, options = {}) {
  const urlPath = url.split('?')[0];

  // GET requests - return mock data
  if (!options.method || options.method === 'GET') {
    if (mockData[urlPath]) {
      return Promise.resolve(new Response(JSON.stringify(mockData[urlPath]), {
        status: 200,
        headers: { 'Content-Type': 'application/json' }
      }));
    }

    // Specific resource GET
    if (urlPath.includes('/api/songs')) {
      const parts = urlPath.split('/');
      const id = parts[parts.length - 1];
      if (id && dbStore.songs[id]) {
        return Promise.resolve(new Response(JSON.stringify(dbStore.songs[id]), {
          status: 200,
          headers: { 'Content-Type': 'application/json' }
        }));
      }
      if (urlPath.endsWith('/songs') || urlPath.endsWith('/api/songs')) {
        return Promise.resolve(new Response(JSON.stringify(Object.values(dbStore.songs)), {
          status: 200,
          headers: { 'Content-Type': 'application/json' }
        }));
      }
    }
  }

  // POST requests
  if (options.method === 'POST') {
    const body = JSON.parse(options.body);

    if (urlPath === '/api/songs') {
      const song = {
        id: dbStore.nextIds.songs++,
        name: body.name,
        bpm: body.bpm,
        timeSig: body.timeSig || '4/4',
        sections: []
      };
      dbStore.songs[song.id] = song;
      return Promise.resolve(new Response(JSON.stringify(song), {
        status: 201,
        headers: { 'Content-Type': 'application/json' }
      }));
    }

    if (urlPath.includes('/sections')) {
      const songId = urlPath.split('/')[3];
      const section = {
        id: dbStore.nextIds.sections++,
        name: body.name,
        color: body.color,
        measures: []
      };
      if (dbStore.songs[songId]) {
        dbStore.songs[songId].sections.push(section);
      }
      return Promise.resolve(new Response(JSON.stringify(section), {
        status: 201,
        headers: { 'Content-Type': 'application/json' }
      }));
    }

    if (urlPath.includes('/measures') || urlPath.includes('/song-measures')) {
      const measure = {
        id: dbStore.nextIds.measures++,
        events: [],
        ...body
      };
      return Promise.resolve(new Response(JSON.stringify(measure), {
        status: 201,
        headers: { 'Content-Type': 'application/json' }
      }));
    }

    if (urlPath === '/api/harmony') {
      // Mock harmony calculation
      const { items } = body;
      const result = {
        heatmap: generateMockHeatmap(items),
        influence: generateMockInfluence(items)
      };
      return Promise.resolve(new Response(JSON.stringify(result), {
        status: 200,
        headers: { 'Content-Type': 'application/json' }
      }));
    }
  }

  // DELETE requests
  if (options.method === 'DELETE') {
    if (urlPath.includes('/api/songs')) {
      const id = urlPath.split('/').pop();
      delete dbStore.songs[id];
      return Promise.resolve(new Response(JSON.stringify({ success: true }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' }
      }));
    }
  }

  // Fallback para rutas no manejadas
  return Promise.reject(new Error(`Mock API: ruta no manejada: ${url}`));
};

function generateMockHeatmap(items) {
  // Simular datos de influencia de notas
  const heatmap = {};
  for (let i = 0; i < 12; i++) {
    heatmap[i] = Math.random() * 100;
  }
  return heatmap;
}

function generateMockInfluence(items) {
  return mockData['/api/notes'].map(note => ({
    name: note.name,
    influence: Math.random() * 100,
    count: Math.floor(Math.random() * 5) + 1
  }));
}

console.log('✓ API Mock cargado - operando en modo offline');
