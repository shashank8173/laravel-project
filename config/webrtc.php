<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebRTC ICE servers
    |--------------------------------------------------------------------------
    | On localhost STUN is often enough. On Hostinger / real networks you need
    | a TURN server or peers behind NAT will have no audio/video.
    | Set WEBRTC_TURN_* in .env for a dedicated TURN (recommended).
    | If TURN is empty, a public Metered OpenRelay fallback is used.
    */
    'stun_servers' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'WEBRTC_STUN_SERVERS',
            'stun:stun.l.google.com:19302,stun:stun1.l.google.com:19302,stun:stun.cloudflare.com:3478'
        ))
    ))),

    /** Comma-separated TURN/TURNS URLs (turn:host:3478,turns:host:443?transport=tcp) */
    'turn_servers' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('WEBRTC_TURN_SERVERS', env('WEBRTC_TURN_SERVER', '')))
    ))),

    'turn_username' => env('WEBRTC_TURN_USERNAME'),
    'turn_password' => env('WEBRTC_TURN_PASSWORD'),

    /** Free Metered OpenRelay when no custom TURN is set (OK for demo; use own coturn later) */
    'use_public_turn_fallback' => filter_var(
        env('WEBRTC_PUBLIC_TURN_FALLBACK', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /** Ring timeout in seconds before a call is marked missed */
    'ring_timeout' => (int) env('WEBRTC_RING_TIMEOUT', 45),
];
