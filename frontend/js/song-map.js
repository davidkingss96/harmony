class SongMap {
    constructor(container) {
        this.container = container;
        this.measures = [];
        this.currentMeasureIndex = -1;
        this.loopRange = null;
        this.loopStartIndex = null;
        this.onMeasureClick = null;
    }

    load(measures) {
        this.measures = measures;
        this.currentMeasureIndex = -1;
        this.loopRange = null;
        this.loopStartIndex = null;
        this.render();
    }

    render() {
        if (!this.measures || this.measures.length === 0) {
            this.container.innerHTML = '<div class="song-map-empty">Sin compases</div>';
            return;
        }

        let html = '';
        let currentSection = null;

        this.measures.forEach((m, index) => {
            if (m.section_name !== currentSection) {
                if (currentSection !== null) html += '</div>';
                currentSection = m.section_name;
                html += `<div class="map-section">
                    <div class="map-section-label" style="color: ${m.section_color}">${m.section_name}</div>
                    <div class="map-measures-row">`;
            }

            const classes = ['map-measure'];
            if (index === this.currentMeasureIndex) classes.push('current');
            if (this.loopRange && index >= this.loopRange.start && index <= this.loopRange.end) {
                classes.push('loop-range');
                if (index === this.loopRange.start) classes.push('loop-start');
                if (index === this.loopRange.end) classes.push('loop-end');
            }

            const eventName = this._getEventLabel(m);

            html += `<div class="${classes.join(' ')}" data-index="${index}" onclick="songMap._onClick(${index})">
                <div class="map-measure-events">${eventName}</div>
            </div>`;
        });

        if (currentSection !== null) html += '</div></div>';

        this.container.innerHTML = html;
    }

    _getEventLabel(measure) {
        if (!measure.events || measure.events.length === 0) return '';
        const ev = measure.events[0];
        if (!ev.root_note_name || !ev.element_name) return '';
        return ev.root_note_name + ev.element_name.charAt(0);
    }

    updateCursor(index) {
        this.currentMeasureIndex = index;

        const prev = this.container.querySelector('.map-measure.current');
        if (prev) prev.classList.remove('current');

        const next = this.container.querySelector(`.map-measure[data-index="${index}"]`);
        if (next) {
            next.classList.add('current');
            next.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    }

    setLoopRange(start, end) {
        this.loopRange = { start: Math.min(start, end), end: Math.max(start, end) };
        this.render();
        this.updateCursor(this.currentMeasureIndex);
    }

    clearLoop() {
        this.loopRange = null;
        this.loopStartIndex = null;
        this.render();
        this.updateCursor(this.currentMeasureIndex);
    }

    _onClick(index) {
        if (this.loopStartIndex !== null) {
            this.setLoopRange(this.loopStartIndex, index);
            this.loopStartIndex = null;
        } else {
            this.loopStartIndex = index;
            this._highlightLoopStart(index);
        }

        if (this.onMeasureClick) {
            this.onMeasureClick(index);
        }
    }

    _highlightLoopStart(index) {
        this.container.querySelectorAll('.map-measure').forEach(el => {
            el.classList.remove('loop-start');
        });
        const el = this.container.querySelector(`.map-measure[data-index="${index}"]`);
        if (el) el.classList.add('loop-start');
    }
}
