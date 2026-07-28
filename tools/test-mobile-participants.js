#!/usr/bin/env node
'use strict';

const assert = require('node:assert/strict');

class FakeClassList {
    constructor() { this.values = new Set(); }
    add(...names) { names.forEach((name) => this.values.add(name)); }
    remove(...names) { names.forEach((name) => this.values.delete(name)); }
    toggle(name, force) {
        const enabled = typeof force === 'boolean' ? force : !this.values.has(name);
        enabled ? this.values.add(name) : this.values.delete(name);
        return enabled;
    }
    contains(name) { return this.values.has(name); }
}

class FakeElement extends EventTarget {
    constructor(tagName) {
        super();
        this.tagName = tagName;
        this.children = [];
        this.classList = new FakeClassList();
        this.attributes = new Map();
        this.hidden = false;
        this.parentNode = null;
        this.scrollCalls = [];
    }
    set className(value) { value.split(/\s+/).filter(Boolean).forEach((name) => this.classList.add(name)); }
    setAttribute(name, value) { this.attributes.set(name, String(value)); }
    getAttribute(name) { return this.attributes.get(name); }
    appendChild(child) { child.parentNode = this; this.children.push(child); return child; }
    remove() {
        if (this.parentNode) {
            this.parentNode.children = this.parentNode.children.filter((child) => child !== this);
            this.parentNode = null;
        }
    }
    matches(selector) {
        if (selector.startsWith('[')) return this.attributes.has(selector.slice(1, -1));
        if (selector.startsWith('.')) return this.classList.contains(selector.slice(1));
        return false;
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    querySelectorAll(selector) {
        return this.children.reduce((matches, child) => matches.concat(child.matches(selector) ? [child] : [], child.querySelectorAll(selector)), []);
    }
    scrollIntoView(options) { this.scrollCalls.push(options); }
    focus() {}
}

class FakeRoot extends FakeElement {
    constructor(elements) { super('section'); this.elements = elements; }
    querySelector(selector) { return this.elements[selector] || super.querySelector(selector); }
    querySelectorAll(selector) {
        const element = this.elements[selector];
        return element ? (Array.isArray(element) ? element : [element]) : super.querySelectorAll(selector);
    }
}

global.CustomEvent = class CustomEvent extends Event {
    constructor(type, options) { super(type); this.detail = (options && options.detail) || {}; }
};
global.document = {
    activeElement: null,
    createElement: (tagName) => new FakeElement(tagName),
    createElementNS: (namespace, tagName) => new FakeElement(tagName)
};
global.window = global;
global.location = { href: 'https://example.test/studio' };
global.matchMedia = () => ({ matches: false });

const remoteStage = new FakeElement('div');
const drawer = new FakeElement('aside');
const drawerList = new FakeElement('div');
const count = new FakeElement('span');
const countValue = new FakeElement('span');
const countIcon = new FakeElement('span');
countValue.setAttribute('data-mobile-participant-count-value', '');
countIcon.className = 'vh360-mobile-live__participant-count-icon';
count.appendChild(countValue);
count.appendChild(countIcon);
const root = new FakeRoot({
    '[data-mobile-remote-stage]': remoteStage,
    '[data-mobile-participant-drawer]': drawer,
    '[data-mobile-participant-list]': drawerList,
    '[data-mobile-participant-count]': count,
    '[data-mobile-participant-count-value]': countValue
});

require('../bundled-plugins/videohub360-studio/assets/js/studio-mobile-participants.js');

const calls = { play: [], stop: [], quality: [] };
const session = {
    getRemoteParticipants: () => [],
    playRemoteVideo: (uid) => { calls.play.push(uid); return true; },
    stopRemoteVideo: (uid) => { calls.stop.push(uid); return true; },
    playRemoteAudio: () => true,
    stopRemoteAudio: () => true,
    setRemoteVideoQuality: (uid, quality) => { calls.quality.push([uid, quality]); return Promise.resolve(true); }
};

async function flush() {
    await new Promise((resolve) => setTimeout(resolve, 0));
}

(async function run() {
    const controller = window.VH360StudioMobileParticipants.create({ root, session, enabled: true });
    await controller.activateRendering();
    assert.equal(countValue.textContent, '0', 'count starts at zero');
    assert.equal(count.getAttribute('aria-label'), '0 participants', 'zero count is accessible');
    const participantIcon = countIcon;
    for (let uid = 1; uid <= 8; uid += 1) {
        root.dispatchEvent(new CustomEvent('vh360:agora-broadcaster:remote-participant-published', {
            detail: { uid: String(uid), videoAvailable: true, videoTrack: { id: uid } }
        }));
        if ([5, 6, 8].includes(uid)) {
            assert.equal(remoteStage.children.length, uid, `${uid} remote tiles are present`);
            assert.equal(new Set(calls.play).size, uid, `${uid} remote videos receive playback calls`);
            assert.equal(root.getAttribute('data-mobile-remote-count'), String(uid), `count reaches ${uid}`);
            assert.equal(countValue.textContent, String(uid), `visible count reaches ${uid}`);
            assert.equal(count.children.includes(participantIcon), true, 'participant icon is preserved');
        }
    }
    await flush();

    assert.equal(remoteStage.children.length, 8, 'all eight remote tiles are created');
    assert.equal(drawerList.children.length, 8, 'all eight drawer rows are created');
    assert.deepEqual(new Set(calls.play), new Set(['1', '2', '3', '4', '5', '6', '7', '8']), 'all eight videos play');
    assert.equal(remoteStage.children.some((tile) => tile.hidden), false, 'no participant tile is hidden');
    assert.equal(root.getAttribute('data-mobile-remote-count'), '8');
    assert.equal(root.classList.contains('has-large-participant-grid'), true);
    assert.equal(count.getAttribute('aria-label'), '8 participants');
    assert.equal(calls.quality.filter((entry) => entry[1] === 'high').length, 1, 'one participant starts high');
    assert.equal(calls.quality.filter((entry) => entry[1] === 'low').length, 7, 'the other participants start low');

    const secondTile = remoteStage.children[1];
    const secondMicrophone = secondTile.querySelector('[data-mobile-participant-microphone]');
    const secondDrawerMicrophone = drawerList.children[1].querySelector('[data-mobile-participant-microphone]');
    assert.equal(secondMicrophone.classList.contains('is-muted'), true, 'participant starts with muted microphone');
    assert.equal(secondDrawerMicrophone.classList.contains('is-muted'), true, 'drawer starts with muted microphone');
    root.dispatchEvent(new CustomEvent('vh360:agora-broadcaster:remote-participant-published', {
        detail: { uid: '2', audioAvailable: true, audioTrack: { id: 'audio-2' } }
    }));
    await flush();
    assert.equal(remoteStage.children[1], secondTile, 'audio publish reuses the participant tile');
    assert.equal(secondMicrophone.classList.contains('is-active'), true, 'audio publish activates tile microphone');
    assert.equal(secondMicrophone.getAttribute('aria-label'), 'Microphone on');
    assert.equal(secondDrawerMicrophone.classList.contains('is-active'), true, 'audio publish activates drawer microphone');
    root.dispatchEvent(new CustomEvent('vh360:agora-broadcaster:remote-track-unpublished', {
        detail: { uid: '2', mediaType: 'audio' }
    }));
    await flush();
    assert.equal(remoteStage.children[1], secondTile, 'audio unpublish reuses the participant tile');
    assert.equal(secondMicrophone.classList.contains('is-muted'), true, 'audio unpublish mutes tile microphone');
    assert.equal(secondMicrophone.getAttribute('aria-label'), 'Microphone muted');
    assert.equal(secondTile.querySelector('.vh360-mobile-live__participant-status'), null, 'long tile status element is absent');
    assert.equal(drawerList.children[1].querySelector('.vh360-mobile-live__participant-row-state'), null, 'long drawer status element is absent');

    drawerList.children[7].dispatchEvent(new Event('click'));
    await flush();
    assert.equal(calls.stop.length, 0, 'selection does not stop another video');
    assert.deepEqual(calls.quality.slice(-2), [['1', 'low'], ['8', 'high']], 'selection changes only old and new quality');
    assert.equal(remoteStage.children[7].scrollCalls.length, 1, 'selected tile scrolls into view');

    root.dispatchEvent(new CustomEvent('vh360:agora-broadcaster:remote-participants-reset'));
    await flush();
    assert.equal(remoteStage.children.length, 0, 'reset removes all eight tiles');
    assert.equal(drawerList.children.length, 0, 'reset removes all eight drawer rows');
    assert.equal(controller.getParticipantCount(), 0, 'reset clears all participant records');
    assert.equal(root.classList.contains('has-large-participant-grid'), false, 'reset clears large-grid state');
    assert.equal(countValue.textContent, '0', 'reset returns visible count to zero');
    assert.equal(count.getAttribute('aria-label'), '0 participants', 'reset restores accessible zero count');
    assert.equal(count.children.includes(participantIcon), true, 'reset preserves participant icon');
    console.log('Mobile participant 8-remote harness passed.');
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
