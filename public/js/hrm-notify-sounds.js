/**
 * HRM notification sounds (Web Audio) — message "tn tn" + looping call ringtone.
 */
(function (window) {
    'use strict';

    let ctx = null;
    let unlocked = false;
    let callTimer = null;
    let callOsc = [];

    function audioCtx() {
        if (!ctx) {
            const AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return null;
            ctx = new AC();
        }
        return ctx;
    }

    function unlock() {
        const ac = audioCtx();
        if (!ac) return;
        if (ac.state === 'suspended') {
            ac.resume().catch(function () {});
        }
        unlocked = true;
    }

    function beep(freq, start, dur, type, gainVal) {
        const ac = audioCtx();
        if (!ac) return;
        const osc = ac.createOscillator();
        const gain = ac.createGain();
        osc.type = type || 'sine';
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.0001, ac.currentTime + start);
        gain.gain.exponentialRampToValueAtTime(gainVal || 0.18, ac.currentTime + start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, ac.currentTime + start + dur);
        osc.connect(gain);
        gain.connect(ac.destination);
        osc.start(ac.currentTime + start);
        osc.stop(ac.currentTime + start + dur + 0.02);
    }

    /** Short double tone — tn tn */
    function playMessageTone() {
        unlock();
        beep(880, 0, 0.09, 'sine', 0.16);
        beep(1175, 0.14, 0.1, 'sine', 0.16);
    }

    function stopCallRingtone() {
        if (callTimer) {
            clearInterval(callTimer);
            callTimer = null;
        }
        callOsc.forEach(function (o) {
            try { o.stop(); } catch (e) {}
        });
        callOsc = [];
    }

    function playCallBurst() {
        const ac = audioCtx();
        if (!ac) return;
        // Classic ring-ish two-tone pattern
        [0, 0.22, 0.44].forEach(function (offset, i) {
            const osc = ac.createOscillator();
            const gain = ac.createGain();
            osc.type = 'sine';
            osc.frequency.value = i % 2 === 0 ? 440 : 480;
            gain.gain.setValueAtTime(0.0001, ac.currentTime + offset);
            gain.gain.exponentialRampToValueAtTime(0.2, ac.currentTime + offset + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.0001, ac.currentTime + offset + 0.18);
            osc.connect(gain);
            gain.connect(ac.destination);
            osc.start(ac.currentTime + offset);
            osc.stop(ac.currentTime + offset + 0.22);
            callOsc.push(osc);
        });
    }

    function startCallRingtone() {
        unlock();
        stopCallRingtone();
        playCallBurst();
        callTimer = setInterval(playCallBurst, 2200);
    }

    window.HrmNotifySounds = {
        unlock: unlock,
        playMessageTone: playMessageTone,
        startCallRingtone: startCallRingtone,
        stopCallRingtone: stopCallRingtone,
        isUnlocked: function () { return unlocked; },
    };

    document.addEventListener('click', unlock, { once: true });
    document.addEventListener('keydown', unlock, { once: true });
    document.addEventListener('touchstart', unlock, { once: true });
})(window);
