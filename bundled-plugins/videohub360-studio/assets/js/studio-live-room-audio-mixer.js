(function (window) {
    'use strict';
    function Mixer() { var AudioContext = window.AudioContext || window.webkitAudioContext; this.context = AudioContext ? new AudioContext() : null; this.destination = this.context ? this.context.createMediaStreamDestination() : null; this.sources = []; }
    Mixer.prototype.addStream = function (stream) { if (!this.context || !stream || !stream.getAudioTracks().length) { return; } try { var source = this.context.createMediaStreamSource(stream); source.connect(this.destination); this.sources.push(source); } catch (e) {} };
    Mixer.prototype.participants = function () { return window.vh360AgoraParticipants instanceof Map ? Array.from(window.vh360AgoraParticipants.values()) : Object.values(window.vh360AgoraParticipants || {}); };
    Mixer.prototype.refresh = function () { var self = this; this.participants().forEach(function (p) { if (p.audioTrack && p.audioTrack.getMediaStreamTrack) { self.addStream(new MediaStream([p.audioTrack.getMediaStreamTrack()])); } if (p.audioStream) { self.addStream(p.audioStream); } }); };
    Mixer.prototype.stream = function () { this.refresh(); return this.destination ? this.destination.stream : new MediaStream(); };
    Mixer.prototype.stop = function () { this.sources.forEach(function (s) { try { s.disconnect(); } catch (e) {} }); if (this.context) { this.context.close(); } };
    window.VH360LiveRoomAudioMixer = Mixer;
})(window);
