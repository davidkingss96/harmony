class AudioEngine {
    constructor() {
        this.ctx = null;
    }

    _ensureContext() {
        if (!this.ctx) {
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
        return this.ctx;
    }

    /**
     * Metronome tick.
     * beat 1 (accent): triangle 1000Hz, 60ms, gain 0.5
     * beats 2-3: sine 800Hz, 40ms, gain 0.3
     * beat 4 (close): square 1200Hz, 30ms, gain 0.4
     */
    metronomeTick(beatInMeasure, timeSignatureNum) {
        const ctx = this._ensureContext();
        const now = ctx.currentTime;

        let freq, waveform, duration, gainVal;

        if (beatInMeasure === 1) {
            freq = 1000;
            waveform = 'triangle';
            duration = 0.06;
            gainVal = 0.5;
        } else if (beatInMeasure === timeSignatureNum) {
            freq = 1200;
            waveform = 'square';
            duration = 0.03;
            gainVal = 0.4;
        } else {
            freq = 800;
            waveform = 'sine';
            duration = 0.04;
            gainVal = 0.3;
        }

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = waveform;
        osc.frequency.value = freq;

        gain.gain.setValueAtTime(gainVal, now);
        gain.gain.linearRampToValueAtTime(0, now + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start(now);
        osc.stop(now + duration + 0.01);
    }

    /**
     * Play a note from the fretboard.
     * notePosition: 0-11 chromatic
     * duration: seconds (default 0.5)
     * waveform: oscillator type (default 'triangle')
     */
    playNote(notePosition, duration = 0.5, waveform = 'triangle') {
        const ctx = this._ensureContext();
        const now = ctx.currentTime;

        const freq = 440 * Math.pow(2, (notePosition - 9) / 12);

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = waveform;
        osc.frequency.value = freq;

        gain.gain.setValueAtTime(0.3, now);
        gain.gain.setValueAtTime(0.3, now + duration * 0.6);
        gain.gain.linearRampToValueAtTime(0, now + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start(now);
        osc.stop(now + duration + 0.01);
    }

    resume() {
        if (this.ctx && this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
    }

    suspend() {
        if (this.ctx && this.ctx.state === 'running') {
            this.ctx.suspend();
        }
    }
}
