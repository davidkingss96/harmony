class Player {
    constructor(audioEngine) {
        this.audio = audioEngine;
        this.song = null;

        this.state = {
            isPlaying: false,
            currentMeasureIndex: 0,
            currentBeat: 1
        };

        this.bpm = 120;
        this.timeSig = { num: 4, den: 4 };
        this.beatDuration = 60000 / this.bpm;

        this.loop = { active: false, startMeasure: 0, endMeasure: 0 };
        this.timerId = null;
        this.expectedTime = 0;

        this.onTick = null;
        this.onMeasureChange = null;
        this.onPlay = null;
        this.onStop = null;
        this.onEnd = null;
    }

    load(playerData) {
        this.song = playerData;
        this.bpm = parseFloat(playerData.bpm) || 120;
        this.timeSig = {
            num: playerData.time_signature_num || 4,
            den: playerData.time_signature_den || 4
        };
        this.beatDuration = 60000 / this.bpm;
    }

    play(startMeasureIndex) {
        if (!this.song) return;

        this.audio.resume();

        if (typeof startMeasureIndex === 'number') {
            this.state.currentMeasureIndex = startMeasureIndex;
        }
        this.state.currentBeat = 1;
        this.state.isPlaying = true;
        this.expectedTime = performance.now() + this.beatDuration;

        if (this.onPlay) this.onPlay(this.state);
        if (this.onMeasureChange) this.onMeasureChange(this.state.currentMeasureIndex);

        this._tick();
    }

    stop() {
        this.state.isPlaying = false;
        if (this.timerId) {
            clearTimeout(this.timerId);
            this.timerId = null;
        }

        if (this.state.currentBeat === 1 && this.state.currentMeasureIndex > 0) {
            this.state.currentMeasureIndex--;
        }
        this.state.currentBeat = 1;

        if (this.onStop) this.onStop(this.state);
        if (this.onMeasureChange) this.onMeasureChange(this.state.currentMeasureIndex);
    }

    seekToMeasure(index) {
        if (!this.song) return;
        if (index < 0) index = 0;
        if (index >= this.song.total_measures) index = this.song.total_measures - 1;

        this.state.currentMeasureIndex = index;
        this.state.currentBeat = 1;

        if (this.onMeasureChange) this.onMeasureChange(index);
        if (this.onTick) this.onTick(this._buildTickData());
    }

    toggleLoop(startMeasure, endMeasure) {
        if (this.loop.active && this.loop.startMeasure === startMeasure && this.loop.endMeasure === endMeasure) {
            this.loop.active = false;
        } else {
            this.loop.active = true;
            this.loop.startMeasure = Math.min(startMeasure, endMeasure);
            this.loop.endMeasure = Math.max(startMeasure, endMeasure);
        }
        return this.loop;
    }

    clearLoop() {
        this.loop.active = false;
    }

    getCurrentMeasure() {
        if (!this.song || !this.song.measures) return null;
        return this.song.measures[this.state.currentMeasureIndex] || null;
    }

    getCurrentEvent() {
        const measure = this.getCurrentMeasure();
        if (!measure || !measure.events || measure.events.length === 0) return null;

        for (let i = measure.events.length - 1; i >= 0; i--) {
            if (measure.events[i].beat <= this.state.currentBeat) {
                return measure.events[i];
            }
        }
        return measure.events[0];
    }

    getNextMeasure() {
        if (!this.song || !this.song.measures) return null;
        const nextIndex = this.state.currentMeasureIndex + 1;
        if (nextIndex >= this.song.total_measures) return null;
        return this.song.measures[nextIndex];
    }

    getNextEvent() {
        const nextMeasure = this.getNextMeasure();
        if (!nextMeasure || !nextMeasure.events || nextMeasure.events.length === 0) return null;
        return nextMeasure.events[0];
    }

    getBeatsRemaining() {
        return this.timeSig.num - this.state.currentBeat + 1;
    }

    _tick() {
        if (!this.state.isPlaying) return;

        const now = performance.now();
        const delay = this.expectedTime - now;

        this._processBeat();

        this.expectedTime += this.beatDuration;
        this.timerId = setTimeout(() => this._tick(), Math.max(0, delay));
    }

    _processBeat() {
        this.audio.metronomeTick(this.state.currentBeat, this.timeSig.num);

        if (this.onTick) {
            this.onTick(this._buildTickData());
        }

        this.state.currentBeat++;

        if (this.state.currentBeat > this.timeSig.num) {
            this.state.currentBeat = 1;
            this.state.currentMeasureIndex++;

            if (this.loop.active &&
                this.state.currentMeasureIndex > this.loop.endMeasure) {
                this.state.currentMeasureIndex = this.loop.startMeasure;
            }

            if (this.state.currentMeasureIndex >= this.song.total_measures) {
                this.state.isPlaying = false;
                if (this.onEnd) this.onEnd();
                return;
            }

            if (this.onMeasureChange) {
                this.onMeasureChange(this.state.currentMeasureIndex);
            }
        }
    }

    _buildTickData() {
        return {
            isPlaying: this.state.isPlaying,
            currentMeasureIndex: this.state.currentMeasureIndex,
            currentBeat: this.state.currentBeat,
            beatsTotal: this.timeSig.num,
            beatsRemaining: this.getBeatsRemaining(),
            totalMeasures: this.song ? this.song.total_measures : 0,
            loop: this.loop
        };
    }
}
