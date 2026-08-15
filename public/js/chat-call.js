/**
 * HRM Chat WebRTC calling — 1:1 + group mesh
 * Signaling via AJAX poll; media is peer-to-peer.
 */
(function (window, $) {
    'use strict';

    const CallApp = {
        meId: 0,
        routes: {},
        csrf: '',
        defaultAv: '',
        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
        ringTimeout: 45,
        getCurrent: null,

        state: 'idle',
        call: null,
        isVideo: false,
        isCaller: false,

        localStream: null,
        screenStream: null,
        cameraTrack: null,
        peers: {}, // userId -> peer state

        muted: false,
        cameraOff: false,
        sharing: false,
        viewMode: 'full', // full | minimized | pip
        localDragBound: false,

        pollTimer: null,
        timerInterval: null,
        connectedAt: null,
        ringTimer: null,

        init(opts) {
            this.meId = Number(opts.meId || 0);
            this.routes = opts.routes || {};
            this.csrf = opts.csrf || '';
            this.defaultAv = opts.defaultAv || '';
            this.getCurrent = typeof opts.getCurrent === 'function' ? opts.getCurrent : () => window.current || null;
            this.bindUi();
            this.loadConfig();
            this.startPolling(2000);
            this.updateHeaderButtons();
        },

        bindUi() {
            const self = this;
            $('#callAudioBtn').on('click', () => self.onCallButton('audio'));
            $('#callVideoBtn').on('click', () => self.onCallButton('video'));
            $('#callAcceptBtn').on('click', () => self.acceptIncoming());
            $('#callRejectBtn').on('click', () => self.rejectIncoming());
            $('#callEndBtn').on('click', () => self.endCall(true));
            $('#callMuteBtn').on('click', () => self.toggleMute());
            $('#callCamBtn').on('click', () => self.toggleCamera());
            $('#callShareBtn').on('click', () => self.toggleScreenShare());
            $('#callAddBtn').on('click', () => self.openInviteModal());
            $('#callInviteConfirmBtn').on('click', () => self.confirmInvite());
            $('#callStartConfirmBtn').on('click', () => self.confirmGroupStart());
            $('#callMinBtn').on('click', () => self.setCallView(self.viewMode === 'minimized' ? 'full' : 'minimized'));
            $('#callMaxBtn').on('click', () => self.maximizeCall());
            $('#callPipBtn').on('click', () => self.setCallView(self.viewMode === 'pip' ? 'full' : 'pip'));
            this.bindInviteModalZ();
            this.bindLocalVideoDrag();
        },

        bindInviteModalZ() {
            const el = document.getElementById('callInviteModal');
            if (!el || el.dataset.zBound) return;
            el.dataset.zBound = '1';
            el.addEventListener('show.bs.modal', () => {
                document.body.classList.add('hrm-call-invite-open');
            });
            el.addEventListener('shown.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach((b) => {
                    b.style.zIndex = '2590';
                });
            });
            el.addEventListener('hidden.bs.modal', () => {
                document.body.classList.remove('hrm-call-invite-open');
            });
        },

        setCallView(mode) {
            this.viewMode = mode || 'full';
            const ov = $('#callActiveOverlay');
            ov.removeClass('is-minimized is-pip');
            if (this.viewMode === 'minimized') ov.addClass('is-minimized');
            if (this.viewMode === 'pip') ov.addClass('is-pip');
            if (this.viewMode === 'full' && document.fullscreenElement) {
                document.exitFullscreen?.().catch(() => {});
            }
        },

        maximizeCall() {
            const ov = document.getElementById('callActiveOverlay');
            if (!ov) return;
            if (this.viewMode !== 'full') {
                this.setCallView('full');
            }
            if (!document.fullscreenElement) {
                (ov.requestFullscreen || ov.webkitRequestFullscreen || ov.msRequestFullscreen)?.call(ov)?.catch?.(() => {});
            } else {
                document.exitFullscreen?.().catch(() => {});
            }
        },

        resetLocalVideoPos() {
            const el = document.getElementById('callLocalVideo');
            if (!el) return;
            el.style.left = '';
            el.style.top = '';
            el.style.right = '';
            el.style.bottom = '';
        },

        bindLocalVideoDrag() {
            if (this.localDragBound) return;
            const el = document.getElementById('callLocalVideo');
            const stage = document.getElementById('callStage');
            if (!el || !stage) return;
            this.localDragBound = true;

            let dragging = false;
            let startX = 0;
            let startY = 0;
            let origLeft = 0;
            let origTop = 0;

            const onDown = (e) => {
                if (el.classList.contains('d-none')) return;
                const point = e.touches ? e.touches[0] : e;
                dragging = true;
                el.classList.add('is-dragging');
                const rect = el.getBoundingClientRect();
                const stageRect = stage.getBoundingClientRect();
                origLeft = rect.left - stageRect.left;
                origTop = rect.top - stageRect.top;
                startX = point.clientX;
                startY = point.clientY;
                el.style.right = 'auto';
                el.style.bottom = 'auto';
                el.style.left = origLeft + 'px';
                el.style.top = origTop + 'px';
                e.preventDefault();
            };

            const onMove = (e) => {
                if (!dragging) return;
                const point = e.touches ? e.touches[0] : e;
                const stageRect = stage.getBoundingClientRect();
                let nextLeft = origLeft + (point.clientX - startX);
                let nextTop = origTop + (point.clientY - startY);
                const maxLeft = Math.max(0, stageRect.width - el.offsetWidth);
                const maxTop = Math.max(0, stageRect.height - el.offsetHeight);
                nextLeft = Math.min(Math.max(0, nextLeft), maxLeft);
                nextTop = Math.min(Math.max(0, nextTop), maxTop);
                el.style.left = nextLeft + 'px';
                el.style.top = nextTop + 'px';
            };

            const onUp = () => {
                if (!dragging) return;
                dragging = false;
                el.classList.remove('is-dragging');
            };

            el.addEventListener('pointerdown', onDown);
            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp);
            window.addEventListener('pointercancel', onUp);
        },

        post(url, data) {
            return $.ajax({
                url,
                method: 'POST',
                data: Object.assign({ _token: this.csrf }, data || {}),
                dataType: 'json',
            });
        },

        get(url, data) {
            return $.ajax({ url, method: 'GET', data: data || {}, dataType: 'json' });
        },

        loadConfig() {
            if (!this.routes.config) return;
            this.get(this.routes.config).done((res) => {
                if (res.iceServers && res.iceServers.length) this.iceServers = res.iceServers;
                if (res.ringTimeout) this.ringTimeout = Number(res.ringTimeout) || 45;
            }).fail(() => {});
        },

        startPolling(ms) {
            clearInterval(this.pollTimer);
            this.pollTimer = setInterval(() => this.poll(), ms);
            this.poll();
        },

        setPollFast(active) {
            this.startPolling(active ? 700 : 2000);
        },

        activeChat() {
            try {
                return this.getCurrent ? this.getCurrent() : (window.current || null);
            } catch (e) {
                return null;
            }
        },

        updateHeaderButtons() {
            const cur = this.activeChat();
            const show = !!(cur && (cur.type === 'user' || cur.type === 'group'));
            $('#callAudioBtn, #callVideoBtn')
                .toggleClass('d-none', !show)
                .prop('disabled', !show || this.state !== 'idle');
        },

        onCallButton(type) {
            if (this.state !== 'idle') return;
            const cur = this.activeChat();
            if (!cur) {
                this.toast('Open a chat to start a call.');
                return;
            }
            if (cur.type === 'group') {
                this.pendingStartType = type;
                this.openStartPicker(cur);
                return;
            }
            this.startCall(type, { receiver_id: Number(cur.id) });
        },

        openStartPicker(cur) {
            const box = $('#callInviteList').empty();
            $('#callInviteModalTitle').text('Start group call');
            $('#callInviteConfirmBtn').addClass('d-none');
            $('#callStartConfirmBtn').removeClass('d-none');
            const self = this;
            this.get(this.routes.groupMembers || '', { group_id: cur.id }).done((members) => {
                (members || []).forEach((m) => {
                    if (Number(m.id) === self.meId) return;
                    const name = m.name || ((m.fname || '') + ' ' + (m.lname || '')).trim();
                    box.append(
                        `<label class="form-check d-block mb-2">
                            <input class="form-check-input call-invite-check" type="checkbox" value="${m.id}" checked>
                            <span class="form-check-label">${self.esc(name)}</span>
                        </label>`
                    );
                });
                bootstrap.Modal.getOrCreateInstance(document.getElementById('callInviteModal')).show();
            }).fail(() => this.toast('Could not load group members'));
        },

        confirmGroupStart() {
            const cur = this.activeChat();
            if (!cur || cur.type !== 'group') return;
            const ids = $('.call-invite-check:checked').map(function () { return Number(this.value); }).get();
            if (!ids.length) {
                this.toast('Select at least one member.');
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('callInviteModal'))?.hide();
            this.startCall(this.pendingStartType || 'audio', {
                group_id: Number(cur.id),
                member_ids: ids,
            });
        },

        openInviteModal() {
            if (!this.call || this.state === 'idle') return;
            const box = $('#callInviteList').empty();
            $('#callInviteModalTitle').text('Add people to call');
            $('#callStartConfirmBtn').addClass('d-none');
            $('#callInviteConfirmBtn').removeClass('d-none');

            const inCall = new Set(
                (this.call.participants || [])
                    .filter((p) => ['joined', 'invited', 'ringing'].includes(p.status))
                    .map((p) => Number(p.user_id))
            );
            inCall.add(this.meId);
            Object.keys(this.peers).forEach((id) => inCall.add(Number(id)));
            if (this.call.caller_id) inCall.add(Number(this.call.caller_id));
            if (this.call.receiver_id) inCall.add(Number(this.call.receiver_id));

            const self = this;
            const finish = (people) => {
                let count = 0;
                (people || []).forEach((m) => {
                    const id = Number(m.id);
                    if (!id || inCall.has(id)) return;
                    const name = m.name || ((m.fname || '') + ' ' + (m.lname || '')).trim();
                    box.append(
                        `<label class="form-check d-block mb-2">
                            <input class="form-check-input call-invite-check" type="checkbox" value="${id}">
                            <span class="form-check-label">${self.esc(name)}</span>
                        </label>`
                    );
                    count++;
                });
                if (!count) {
                    box.html('<div class="text-muted">No more people to add.</div>');
                }
                bootstrap.Modal.getOrCreateInstance(document.getElementById('callInviteModal')).show();
            };

            // Prefer contacts cache / directory while call overlay stays open (modal z-index > call).
            const loadFromCacheOrFail = () => {
                const list = (window.contactsCache && window.contactsCache.employees) || [];
                if (list.length) {
                    finish(list);
                    return;
                }
                self.toast('Could not load members');
            };

            // Load all employees so people outside the group can also be invited.
            const cached = (window.contactsCache && window.contactsCache.employees) || [];
            if (cached.length) {
                finish(cached);
                return;
            }

            if (this.routes.contacts) {
                this.get(this.routes.contacts).done((data) => {
                    const people = (data && data.employees) ? data.employees : [];
                    if (people.length) {
                        finish(people);
                        return;
                    }
                    loadFromCacheOrFail();
                }).fail(loadFromCacheOrFail);
                return;
            }

            const cur = this.activeChat();
            const gid = this.call.group_id || (cur && cur.type === 'group' ? cur.id : null);
            if (gid && this.routes.groupMembers) {
                this.get(this.routes.groupMembers, { group_id: gid }).done((members) => {
                    finish(Array.isArray(members) ? members : []);
                }).fail(loadFromCacheOrFail);
                return;
            }

            loadFromCacheOrFail();
        },

        confirmInvite() {
            if (!this.call) return;
            const ids = $('.call-invite-check:checked').map(function () { return Number(this.value); }).get();
            if (!ids.length) {
                this.toast('Select at least one person.');
                return;
            }
            this.post(this.routes.invite, { call_id: this.call.id, user_ids: ids })
                .done((res) => {
                    bootstrap.Modal.getInstance(document.getElementById('callInviteModal'))?.hide();
                    if (res.call) this.call = res.call;
                    this.toast('Invite sent.');
                    this.renderParticipantChips();
                })
                .fail((xhr) => this.toast(xhr.responseJSON?.message || 'Invite failed'));
        },

        esc(s) {
            return String(s || '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[c]));
        },

        async startCall(type, params) {
            if (this.state !== 'idle') return;
            this.isVideo = type === 'video';
            this.isCaller = true;

            try {
                await this.ensureMedia(this.isVideo);
            } catch (err) {
                this.toast(this.permMessage(err, this.isVideo));
                this.cleanupMediaOnly();
                return;
            }

            const body = Object.assign({ call_type: type }, params || {});
            this.post(this.routes.start, body)
                .done(async (res) => {
                    if (res.iceServers) this.iceServers = res.iceServers;
                    this.call = res.call;
                    this.setState('calling');
                    this.showActiveUi();
                    this.setPollFast(true);
                    this.armRingTimeout();
                    this.renderParticipantChips();
                })
                .fail((xhr) => {
                    this.cleanupMediaOnly();
                    this.toast(xhr.responseJSON?.message || 'Unable to start call');
                    this.setState('idle');
                });
        },

        async acceptIncoming() {
            if (!this.call) return;
            this.isCaller = false;
            this.isVideo = this.call.call_type === 'video';

            try {
                await this.ensureMedia(this.isVideo);
            } catch (err) {
                this.toast(this.permMessage(err, this.isVideo));
                this.rejectIncoming();
                return;
            }

            this.post(this.routes.accept, { call_id: this.call.id })
                .done(async (res) => {
                    if (res.iceServers) this.iceServers = res.iceServers;
                    this.call = res.call;
                    this.hideIncoming();
                    this.setState('connecting');
                    this.showActiveUi();
                    this.setPollFast(true);
                    this.renderParticipantChips();
                    // Existing peers will send offers (peer-joined on their side).
                    // Also prepare polite PCs for known peers.
                    (res.peers || []).forEach((p) => {
                        if (p && p.id) this.ensurePeer(p.id, p, true);
                    });
                })
                .fail((xhr) => {
                    this.cleanupMediaOnly();
                    this.hideIncoming();
                    this.toast(xhr.responseJSON?.message || 'Could not accept call');
                    this.resetToIdle();
                });
        },

        rejectIncoming() {
            if (!this.call) {
                this.hideIncoming();
                this.resetToIdle();
                return;
            }
            const id = this.call.id;
            this.post(this.routes.reject, { call_id: id }).always(() => {
                this.hideIncoming();
                this.cleanupAll();
                this.resetToIdle();
            });
        },

        endCall(notifyServer) {
            clearTimeout(this.ringTimer);
            const id = this.call?.id;
            const endForAll = !!(this.call && this.call.is_host && (this.call.scope === 'dm' || Object.keys(this.peers).length <= 1));
            if (notifyServer && id) {
                this.post(this.routes.end, { call_id: id, end_for_all: endForAll ? 1 : 0 }).always(() => {
                    if (typeof window.loadMessages === 'function') window.loadMessages(true);
                });
            }
            this.setState('ended');
            this.setStatusText('Call ended');
            setTimeout(() => {
                this.cleanupAll();
                this.hideActive();
                this.hideIncoming();
                this.resetToIdle();
            }, 800);
        },

        armRingTimeout() {
            clearTimeout(this.ringTimer);
            this.ringTimer = setTimeout(() => {
                if (this.state === 'calling' || this.state === 'ringing') {
                    // Keep host in call if at least one peer connected; else end
                    if (Object.keys(this.peers).length === 0) {
                        this.setStatusText('No answer');
                        this.endCall(true);
                    }
                }
            }, (this.ringTimeout + 2) * 1000);
        },

        poll() {
            if (!this.routes.poll) return;
            this.get(this.routes.poll).done((res) => {
                (res.signals || []).forEach((sig) => this.handleSignal(sig));

                if (this.state === 'idle' && res.incoming && res.incoming.length) {
                    const inc = res.incoming[0];
                    this.call = inc;
                    this.isVideo = inc.call_type === 'video';
                    this.isCaller = false;
                    this.setState('ringing');
                    this.showIncoming(inc);
                    this.startIncomingRingtone();
                    this.setPollFast(true);
                }

                // Keep everyone aware of live calls / on-call badges
                if (window.contactsCache) {
                    const ids = (res.on_call_ids || []).map(Number);
                    const live = res.live_calls || [];
                    const sig = ids.slice().sort().join(',') + '|' + live.map((c) => c.id + ':' + (c.participant_ids || []).join('-')).join(';');
                    if (sig !== window.__hrmLiveCallSig) {
                        window.__hrmLiveCallSig = sig;
                        const set = new Set(ids);
                        (contactsCache.employees || []).forEach((e) => {
                            e.on_call = set.has(Number(e.id));
                        });
                        contactsCache.live_calls = live;
                        contactsCache.on_call_ids = ids;
                        if (typeof renderContactList === 'function') renderContactList();
                    }
                }
            }).fail(() => {});
        },

        unwrapPayload(payload) {
            if (!payload) return { fromId: null, data: null };
            if (payload.data !== undefined && (payload.from_id || payload.target_id)) {
                return { fromId: Number(payload.from_id || 0), data: payload.data };
            }
            return { fromId: null, data: payload };
        },

        handleSignal(sig) {
            if (!sig) return;
            const type = sig.type;
            const { fromId, data } = this.unwrapPayload(sig.payload);
            const sender = fromId || Number(sig.sender_id);

            if (type === 'invite' && this.state === 'idle') return;

            if (this.call && Number(sig.call_id) !== Number(this.call.id) && type !== 'invite') {
                return;
            }

            if (type === 'peer-joined') {
                const user = (sig.payload && sig.payload.user) || data?.user || { id: sender };
                const uid = Number(user.id || sender);
                if (uid && uid !== this.meId) {
                    // Existing member creates offer to new joiner
                    this.ensurePeer(uid, user, false).then((peer) => {
                        if (peer) this.createAndSendOffer(uid);
                    });
                    this.renderParticipantChips();
                }
                return;
            }

            if (type === 'peer-left') {
                const uid = Number((sig.payload && sig.payload.user_id) || sender);
                this.removePeer(uid);
                return;
            }

            if (type === 'accepted') {
                clearTimeout(this.ringTimer);
                this.setState('connecting');
                this.setStatusText('Connecting...');
                // 1:1 host creates offer to accepter
                if (sender && sender !== this.meId) {
                    this.ensurePeer(sender, null, false).then(() => this.createAndSendOffer(sender));
                }
                return;
            }

            if (type === 'rejected' || type === 'missed') {
                // For group: one reject shouldn't kill whole call for host
                if (this.call && (this.call.scope === 'group' || (this.call.participants || []).length > 2)) {
                    this.setStatusText(type === 'rejected' ? 'Someone declined' : 'Missed by someone');
                    return;
                }
                clearTimeout(this.ringTimer);
                this.setStatusText(type === 'rejected' ? 'Call declined' : 'No answer');
                this.cleanupAll();
                this.hideIncoming();
                setTimeout(() => {
                    this.hideActive();
                    this.resetToIdle();
                }, 900);
                return;
            }

            if (type === 'hangup') {
                clearTimeout(this.ringTimer);
                this.setStatusText('Call ended');
                this.cleanupAll();
                this.hideIncoming();
                setTimeout(() => {
                    this.hideActive();
                    this.resetToIdle();
                    if (typeof window.loadMessages === 'function') window.loadMessages(true);
                }, 800);
                return;
            }

            if (type === 'offer') {
                this.onRemoteOffer(sender, data);
                return;
            }
            if (type === 'answer') {
                this.onRemoteAnswer(sender, data);
                return;
            }
            if (type === 'ice') {
                this.onRemoteIce(sender, data);
                return;
            }
            if (type === 'connected') {
                this.onConnected();
            }
        },

        sendSignal(type, targetId, payload) {
            if (!this.call || !targetId) return;
            this.post(this.routes.signal, {
                call_id: this.call.id,
                type,
                target_id: targetId,
                payload: typeof payload === 'string' ? payload : JSON.stringify(payload || {}),
            }).fail(() => {});
        },

        async ensurePeer(userId, userBrief, polite) {
            userId = Number(userId);
            if (!userId || userId === this.meId) return null;
            if (this.peers[userId]) return this.peers[userId];

            const peer = {
                userId,
                name: userBrief?.name || ('User ' + userId),
                image: userBrief?.image || this.defaultAv,
                pc: null,
                stream: new MediaStream(),
                polite: !!polite,
                makingOffer: false,
                ignoreOffer: false,
            };

            peer.pc = new RTCPeerConnection({
                iceServers: this.iceServers,
                iceCandidatePoolSize: 4,
            });
            peer.pendingIce = [];
            peer.pc.onicecandidate = (ev) => {
                if (ev.candidate) {
                    const c = ev.candidate.toJSON ? ev.candidate.toJSON() : {
                        candidate: ev.candidate.candidate,
                        sdpMid: ev.candidate.sdpMid,
                        sdpMLineIndex: ev.candidate.sdpMLineIndex,
                    };
                    this.sendSignal('ice', userId, c);
                }
            };
            peer.pc.ontrack = (ev) => {
                ev.streams[0]?.getTracks().forEach((t) => {
                    if (!peer.stream.getTracks().find((x) => x.id === t.id)) peer.stream.addTrack(t);
                });
                this.attachPeerVideo(userId, peer);
                this.onConnected();
            };
            peer.pc.onconnectionstatechange = () => {
                const st = peer.pc.connectionState;
                if (st === 'connected') {
                    this.onConnected();
                } else if (st === 'failed') {
                    this.setStatusText('Reconnecting...');
                    this.restartIce(userId).catch(() => {
                        this.setStatusText('Connection failed — check network / TURN');
                    });
                } else if (st === 'disconnected') {
                    this.setStatusText('Unstable connection...');
                }
            };
            peer.pc.oniceconnectionstatechange = () => {
                const st = peer.pc.iceConnectionState;
                if (st === 'connected' || st === 'completed') this.onConnected();
            };

            if (this.localStream) {
                this.localStream.getTracks().forEach((track) => {
                    peer.pc.addTrack(track, this.localStream);
                });
            }

            this.peers[userId] = peer;
            this.attachPeerVideo(userId, peer);
            this.renderParticipantChips();
            return peer;
        },

        attachPeerVideo(userId, peer) {
            const grid = document.getElementById('callRemoteGrid');
            if (!grid) return;
            let tile = document.getElementById('callPeer-' + userId);
            if (!tile) {
                tile = document.createElement('div');
                tile.id = 'callPeer-' + userId;
                tile.className = 'hrm-call-tile';
                tile.innerHTML = `<video autoplay playsinline></video><div class="hrm-call-tile-name"></div>`;
                grid.appendChild(tile);
            }
            const video = tile.querySelector('video');
            const nameEl = tile.querySelector('.hrm-call-tile-name');
            if (nameEl) nameEl.textContent = peer.name || '';
            if (video && video.srcObject !== peer.stream) {
                video.srcObject = peer.stream;
                video.play().catch(() => {});
            }
            // Hide legacy single remote when using grid
            $('#callRemoteVideo').addClass('d-none');
            $('#callAudioHero').addClass('d-none');
            $('#callRemoteGrid').removeClass('d-none');
        },

        removePeer(userId) {
            userId = Number(userId);
            const peer = this.peers[userId];
            if (!peer) return;
            try { peer.pc && peer.pc.close(); } catch (e) {}
            delete this.peers[userId];
            const tile = document.getElementById('callPeer-' + userId);
            if (tile) tile.remove();
            this.renderParticipantChips();
            if (Object.keys(this.peers).length === 0 && this.state === 'connected') {
                this.setStatusText('Waiting for others...');
            }
        },

        async flushPendingIce(peer) {
            if (!peer || !peer.pc || !peer.pc.remoteDescription) return;
            const list = peer.pendingIce || [];
            peer.pendingIce = [];
            for (let i = 0; i < list.length; i++) {
                try {
                    await peer.pc.addIceCandidate(list[i]);
                } catch (e) {}
            }
        },

        async restartIce(userId) {
            const peer = this.peers[userId];
            if (!peer || !peer.pc) return;
            try {
                if (typeof peer.pc.restartIce === 'function') peer.pc.restartIce();
                await this.createAndSendOffer(userId);
            } catch (e) {}
        },

        async createAndSendOffer(userId) {
            const peer = this.peers[userId];
            if (!peer || !peer.pc || peer.makingOffer) return;
            try {
                peer.makingOffer = true;
                const offer = await peer.pc.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true });
                await peer.pc.setLocalDescription(offer);
                this.sendSignal('offer', userId, {
                    type: peer.pc.localDescription.type,
                    sdp: peer.pc.localDescription.sdp,
                });
            } catch (e) {
                // ignore
            } finally {
                peer.makingOffer = false;
            }
        },

        parseDesc(desc) {
            if (!desc) return null;
            if (typeof desc === 'string') {
                try { return JSON.parse(desc); } catch (e) { return null; }
            }
            return desc;
        },

        async onRemoteOffer(fromId, desc) {
            desc = this.parseDesc(desc);
            if (!desc || !fromId) return;
            const peer = await this.ensurePeer(fromId, null, true);
            if (!peer) return;
            const offerCollision = peer.makingOffer || peer.pc.signalingState !== 'stable';
            peer.ignoreOffer = !peer.polite && offerCollision;
            if (peer.ignoreOffer) return;
            try {
                await peer.pc.setRemoteDescription(desc);
                await this.flushPendingIce(peer);
                const answer = await peer.pc.createAnswer();
                await peer.pc.setLocalDescription(answer);
                this.sendSignal('answer', fromId, {
                    type: peer.pc.localDescription.type,
                    sdp: peer.pc.localDescription.sdp,
                });
                this.setState('connecting');
                this.setStatusText('Connecting...');
            } catch (e) {
                this.toast('Could not process call connection.');
            }
        },

        async onRemoteAnswer(fromId, desc) {
            desc = this.parseDesc(desc);
            const peer = this.peers[fromId];
            if (!peer || !desc) return;
            try {
                if (peer.pc.signalingState === 'have-local-offer') {
                    await peer.pc.setRemoteDescription(desc);
                    await this.flushPendingIce(peer);
                    this.setState('connecting');
                    this.setStatusText('Connecting...');
                }
            } catch (e) {}
        },

        async onRemoteIce(fromId, candidate) {
            const peer = this.peers[fromId];
            if (!peer || !candidate) return;
            try {
                const c = this.parseDesc(candidate) || candidate;
                if (!peer.pc.remoteDescription) {
                    peer.pendingIce = peer.pendingIce || [];
                    peer.pendingIce.push(c);
                    return;
                }
                await peer.pc.addIceCandidate(c);
            } catch (e) {}
        },

        onConnected() {
            clearTimeout(this.ringTimer);
            if (this.state !== 'connected') {
                this.setState('connected');
                this.setStatusText('Connected');
                this.connectedAt = this.connectedAt || Date.now();
                this.startTimer();
            }
        },

        startTimer() {
            clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                if (!this.connectedAt) return;
                const sec = Math.floor((Date.now() - this.connectedAt) / 1000);
                $('#callTimer').text(this.fmtTime(sec));
            }, 500);
        },

        fmtTime(sec) {
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },

        toggleMute() {
            const track = this.localStream?.getAudioTracks()?.[0];
            if (!track) return;
            this.muted = !this.muted;
            track.enabled = !this.muted;
            $('#callMuteBtn').toggleClass('is-off', this.muted)
                .attr('title', this.muted ? 'Unmute' : 'Mute')
                .find('i').attr('class', this.muted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone');
        },

        toggleCamera() {
            if (!this.isVideo || this.sharing) return;
            const track = this.localStream?.getVideoTracks()?.[0];
            if (!track) return;
            this.cameraOff = !this.cameraOff;
            track.enabled = !this.cameraOff;
            $('#callCamBtn').toggleClass('is-off', this.cameraOff)
                .attr('title', this.cameraOff ? 'Camera on' : 'Camera off')
                .find('i').attr('class', this.cameraOff ? 'fa-solid fa-video-slash' : 'fa-solid fa-video');
        },

        async toggleScreenShare() {
            if (this.state === 'idle' || !this.call) return;
            if (this.sharing) {
                await this.stopScreenShare();
                return;
            }
            if (!window.isSecureContext) {
                this.toast('Screen share needs HTTPS (or localhost).');
                return;
            }
            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getDisplayMedia !== 'function') {
                this.toast('Screen share is not supported in this browser.');
                return;
            }
            try {
                this.screenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: {
                        cursor: 'always',
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        frameRate: { ideal: 15, max: 30 },
                    },
                    audio: false,
                });
                const screenTrack = this.screenStream.getVideoTracks()[0];
                if (!screenTrack) {
                    this.toast('Could not get screen track.');
                    return;
                }

                // Keep camera track so we can restore later
                const currentCam = this.localStream?.getVideoTracks()?.[0];
                if (currentCam && currentCam.id !== screenTrack.id) {
                    this.cameraTrack = currentCam;
                }

                await this.replaceOrAddVideoTrack(screenTrack);
                this.sharing = true;
                this.showLocalScreenPreview();
                $('#callShareBtn').removeClass('d-none').addClass('is-on').attr('title', 'Stop sharing');
                screenTrack.onended = () => {
                    this.stopScreenShare().catch(() => {});
                };
            } catch (e) {
                const name = String(e?.name || '');
                if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                    this.toast('Screen share permission denied or cancelled.');
                } else if (name === 'NotFoundError') {
                    this.toast('No screen/window available to share.');
                } else {
                    this.toast('Screen sharing failed. Try Chrome/Edge on HTTPS.');
                }
                if (this.screenStream) {
                    try { this.screenStream.getTracks().forEach((t) => t.stop()); } catch (err) {}
                    this.screenStream = null;
                }
            }
        },

        showLocalScreenPreview() {
            const el = document.getElementById('callLocalVideo');
            if (!el || !this.screenStream) return;
            el.srcObject = this.screenStream;
            el.muted = true;
            el.classList.remove('d-none');
            el.play().catch(() => {});
        },

        async replaceOrAddVideoTrack(track) {
            if (!track) return;

            // Keep localStream video in sync
            if (this.localStream) {
                this.localStream.getVideoTracks().forEach((t) => {
                    if (t.id !== track.id) {
                        try { this.localStream.removeTrack(t); } catch (e) {}
                    }
                });
                if (!this.localStream.getVideoTracks().find((t) => t.id === track.id)) {
                    this.localStream.addTrack(track);
                }
            }

            const peerIds = Object.keys(this.peers).map(Number);
            for (let i = 0; i < peerIds.length; i++) {
                const userId = peerIds[i];
                const peer = this.peers[userId];
                if (!peer?.pc) continue;

                let sender = peer.pc.getSenders().find((s) => s.track && s.track.kind === 'video');
                if (!sender) {
                    // Audio-only call: look for a video sender without live track
                    sender = peer.pc.getSenders().find((s) => {
                        const tr = peer.pc.getTransceivers().find((x) => x.sender === s);
                        return tr && (tr.receiver?.track?.kind === 'video' || tr.mid != null) && (!s.track || s.track.kind === 'video');
                    });
                }

                try {
                    if (sender) {
                        await sender.replaceTrack(track);
                    } else {
                        peer.pc.addTrack(track, this.localStream || new MediaStream([track]));
                        await this.createAndSendOffer(userId);
                    }
                } catch (e) {
                    try {
                        peer.pc.addTrack(track, this.localStream || new MediaStream([track]));
                        await this.createAndSendOffer(userId);
                    } catch (err) {}
                }
            }
        },

        async stopScreenShare() {
            if (!this.sharing && !this.screenStream) return;

            const screenTracks = this.screenStream ? this.screenStream.getTracks().slice() : [];
            this.sharing = false;

            if (this.cameraTrack && this.cameraTrack.readyState === 'live') {
                await this.replaceOrAddVideoTrack(this.cameraTrack);
                if (this.localStream && !this.localStream.getVideoTracks().find((t) => t.id === this.cameraTrack.id)) {
                    this.localStream.addTrack(this.cameraTrack);
                }
                this.attachLocalPreview();
                $('#callLocalVideo').toggleClass('d-none', !this.isVideo);
            } else {
                // Audio call / no camera — remove outgoing video
                Object.values(this.peers).forEach((peer) => {
                    const sender = peer.pc?.getSenders().find((s) => s.track && s.track.kind === 'video');
                    if (sender) {
                        try { sender.replaceTrack(null); } catch (e) {}
                    }
                });
                if (this.localStream) {
                    this.localStream.getVideoTracks().forEach((t) => {
                        try { this.localStream.removeTrack(t); } catch (e) {}
                    });
                }
                $('#callLocalVideo').toggleClass('d-none', !this.isVideo);
                this.attachLocalPreview();
            }

            screenTracks.forEach((t) => {
                try { t.stop(); } catch (e) {}
            });
            this.screenStream = null;
            $('#callShareBtn').removeClass('is-on').attr('title', 'Share screen');
        },

        showIncoming(call) {
            const name = call.caller?.name || call.peer?.name || 'Incoming call';
            const img = call.caller?.image || call.peer?.image || this.defaultAv;
            const scope = call.scope === 'group' ? 'Group ' : '';
            $('#callIncomingName').text(name);
            $('#callIncomingType').text(scope + (call.call_type === 'video' ? 'Incoming video call' : 'Incoming audio call'));
            $('#callIncomingAvatar').attr('src', img);
            $('#callIncomingOverlay').addClass('is-open');
        },

        startIncomingRingtone() {
            if (window.HrmNotifyPrefs && window.HrmNotifyPrefs.callRingtone === false) return;
            if (window.HrmNotifySounds) window.HrmNotifySounds.startCallRingtone();
        },

        stopIncomingRingtone() {
            if (window.HrmNotifySounds) window.HrmNotifySounds.stopCallRingtone();
        },

        hideIncoming() {
            this.stopIncomingRingtone();
            $('#callIncomingOverlay').removeClass('is-open');
        },

        showActiveUi() {
            const peer = this.call?.peer || this.call?.caller || {};
            const name = this.call?.scope === 'group'
                ? (this.activeChat()?.name || 'Group call')
                : (peer.name || 'Call');
            const img = peer.image || this.defaultAv;
            $('#callActiveName').text(name);
            $('#callActiveAvatar').attr('src', img);
            $('#callTimer').text('00:00');
            $('#callStage').toggleClass('is-video', this.isVideo).toggleClass('is-audio', !this.isVideo);
            $('#callCamBtn').toggleClass('d-none', !this.isVideo);
            $('#callShareBtn').removeClass('d-none');
            $('#callAddBtn').removeClass('d-none');
            $('#callRemoteGrid').toggleClass('d-none', false).empty();
            $('#callRemoteVideo').addClass('d-none');
            $('#callLocalVideo').toggleClass('d-none', !this.isVideo);
            $('#callAudioHero').toggleClass('d-none', this.isVideo);
            this.setStatusText(this.state === 'calling' ? 'Calling...' : 'Connecting...');
            this.setCallView('full');
            this.resetLocalVideoPos();
            $('#callActiveOverlay').addClass('is-open');
            this.attachLocalPreview();
            this.bindLocalVideoDrag();
            this.muted = false;
            this.cameraOff = false;
            $('#callMuteBtn').removeClass('is-off').find('i').attr('class', 'fa-solid fa-microphone');
            $('#callCamBtn').removeClass('is-off').find('i').attr('class', 'fa-solid fa-video');
            $('#callShareBtn').removeClass('is-on');
            this.renderParticipantChips();
        },

        renderParticipantChips() {
            const el = $('#callPeopleChips');
            if (!el.length || !this.call) return;
            const parts = this.call.participants || [];
            let html = '';
            parts.forEach((p) => {
                if (!['joined', 'invited', 'ringing'].includes(p.status)) return;
                const n = p.user?.name || ('#' + p.user_id);
                html += `<span class="hrm-call-chip ${p.status}">${this.esc(n)}</span>`;
            });
            el.html(html);
        },

        hideActive() {
            this.setCallView('full');
            this.resetLocalVideoPos();
            if (document.fullscreenElement) {
                document.exitFullscreen?.().catch(() => {});
            }
            $('#callActiveOverlay').removeClass('is-open');
            $('#callRemoteGrid').empty();
        },

        setState(s) {
            this.state = s;
            this.updateHeaderButtons();
        },

        setStatusText(t) {
            $('#callStatusText').text(t || '');
        },

        resetToIdle() {
            this.stopIncomingRingtone();
            this.call = null;
            this.peers = {};
            this.setState('idle');
            this.setPollFast(false);
            this.updateHeaderButtons();
        },

        async ensureMedia(withVideo) {
            this.releaseAllMediaElements();
            this.cleanupMediaOnly();

            if (!window.isSecureContext) {
                const err = new Error('insecure'); err.name = 'SecurityError'; throw err;
            }
            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                const err = new Error('unsupported'); err.name = 'NotSupportedError'; throw err;
            }

            const attempts = withVideo
                ? [{ audio: true, video: true }, { audio: true, video: { facingMode: 'user' } }, { audio: true, video: false }]
                : [{ audio: true, video: false }];

            let lastErr = null;
            for (let pass = 0; pass < 2; pass++) {
                if (pass === 1) {
                    this.releaseAllMediaElements();
                    this.cleanupMediaOnly();
                    await this.wait(400);
                }
                for (let i = 0; i < attempts.length; i++) {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia(attempts[i]);
                        if (withVideo && !this.localStream.getVideoTracks().length) {
                            this.isVideo = false;
                            this.toast('Camera unavailable — continuing as audio call.');
                        }
                        this.cameraTrack = this.localStream.getVideoTracks()[0] || null;
                        this.attachLocalPreview();
                        return;
                    } catch (err) {
                        lastErr = err;
                        if (err && ['NotAllowedError', 'PermissionDeniedError', 'SecurityError'].includes(err.name)) throw err;
                        this.releaseAllMediaElements();
                        this.cleanupMediaOnly();
                    }
                }
            }
            throw lastErr || new Error('media_failed');
        },

        wait(ms) {
            return new Promise((r) => setTimeout(r, ms));
        },

        releaseAllMediaElements() {
            ['callLocalVideo', 'callRemoteVideo'].forEach((id) => {
                const el = document.getElementById(id);
                if (!el) return;
                try {
                    const stream = el.srcObject;
                    if (stream && stream.getTracks) stream.getTracks().forEach((t) => { try { t.stop(); } catch (e) {} });
                } catch (e) {}
                el.srcObject = null;
            });
            document.querySelectorAll('#callRemoteGrid video').forEach((el) => {
                try {
                    const stream = el.srcObject;
                    if (stream && stream.getTracks) stream.getTracks().forEach((t) => { try { t.stop(); } catch (e) {} });
                } catch (e) {}
                el.srcObject = null;
            });
            if (this.screenStream) {
                try { this.screenStream.getTracks().forEach((t) => t.stop()); } catch (e) {}
                this.screenStream = null;
            }
        },

        cleanupMediaOnly() {
            if (this.localStream) {
                this.localStream.getTracks().forEach((t) => { try { t.stop(); } catch (e) {} });
                this.localStream = null;
            }
            this.cameraTrack = null;
            this.sharing = false;
        },

        attachLocalPreview() {
            const el = document.getElementById('callLocalVideo');
            if (el && this.localStream) {
                el.srcObject = this.localStream;
                el.muted = true;
                el.playsInline = true;
                el.play().catch(() => {});
            }
        },

        cleanupAll() {
            clearInterval(this.timerInterval);
            clearTimeout(this.ringTimer);
            this.timerInterval = null;
            this.connectedAt = null;
            this.sharing = false;
            Object.keys(this.peers).forEach((id) => this.removePeer(Number(id)));
            this.releaseAllMediaElements();
            this.cleanupMediaOnly();
        },

        permMessage(err, video) {
            const name = String(err?.name || '');
            const msg = String(err?.message || '');
            if (name === 'SecurityError' || msg === 'insecure') {
                return 'Calling needs a secure page. Use http://localhost/... or HTTPS.';
            }
            if (name === 'NotSupportedError' || msg === 'unsupported') {
                return 'This browser cannot access microphone/camera.';
            }
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                return video
                    ? 'Allow camera + microphone for this site, then try again.'
                    : 'Allow microphone for this site, then try again.';
            }
            if (name === 'NotFoundError') {
                return video ? 'No camera or microphone found.' : 'No microphone found.';
            }
            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return 'Microphone/camera could not start (device busy).\n\n1) Close other apps/tabs using camera\n2) Ctrl+F5\n3) Try again';
            }
            return 'Unable to access media devices' + (name ? ' (' + name + ')' : '') + '.';
        },

        toast(msg) {
            if (window.alert) alert(msg);
        },
    };

    window.HrmChatCall = CallApp;
})(window, jQuery);
