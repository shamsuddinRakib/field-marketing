@extends('backend.layouts.master')

@section('meta')
  <title>AI Assistant</title>
@endsection

@php
    /* ============================================================
       AI CHAT â€” ROUTE CONFIG (fill these when backend is ready)
       ============================================================
       Expected API contracts (fields are auto-detected where possible):

       send   POST  { message, conversation_id? }
                     â†’ { reply }  (or { message|content|text|answer } )
                       optional: { conversation_id, messages: [...] }

       history   GET   ?conversation_id=...   (optional page load)
                     â†’ { messages: [ { role:"user"|"assistant", message:"..", created_at:".." }, ... ] }

       conversations  GET   (list previous chats for left panel)
                     â†’ { conversations: [ { id, title, updated_at }, ... ] }

       clear     POST  { conversation_id? }   â†’ { success:true }
       ============================================================ */
    $aiChatRoutes = [
        'send'          => route('ai.chat.send'),  // TODO: e.g. route('ai.chat.send')
        'history'       => '',  // TODO: e.g. route('ai.chat.history')
        'conversations' => '',  // TODO: e.g. route('ai.chat.conversations')
        'clear'         => '',  // TODO: e.g. route('ai.chat.clear')
    ];
@endphp

@section('content')
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div>
      <h6 class="fw-semibold mb-0">AI Assistant</h6>
      <p class="m-0">Chat with your AI agent â€” ask anything</p>
    </div>
    <ul class="d-flex align-items-center gap-2">
      <li class="fw-medium">
        <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
          <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
        </a>
      </li>
      <li>-</li>
      <li class="fw-medium">AI Assistant</li>
    </ul>
  </div>

  <div class="ai-chat-page" id="aiChatPage">

    {{-- ===== Left: previous conversations (auto-hidden until `conversations` route is set) ===== --}}
    <aside class="ai-chat-sidebar" id="aiSidebar" hidden>
      <div class="ai-sidebar-head">
        <span class="ai-sidebar-title">Conversations</span>
        <button type="button" class="ai-btn-new-chat" id="aiNewChat" title="New chat">
          <iconify-icon icon="ion:add-outline"></iconify-icon>&nbsp;New chat
        </button>
      </div>
      <div class="ai-sidebar-body" id="aiConvList">
        <div class="ai-conv-empty"><iconify-icon icon="solar:chat-square-like-linear"></iconify-icon><span>No conversations yet</span></div>
      </div>
    </aside>

    {{-- ===== Right: main chat panel ===== --}}
    <section class="ai-chat-main">
      <header class="ai-chat-head">
        <button type="button" class="ai-head-icon-btn" id="aiConvsToggle" title="Conversations" aria-label="Conversations">
          <iconify-icon icon="solar:hamburger-menu-linear"></iconify-icon>
        </button>

        <div class="ai-head-id">
          <div class="ai-avatar"><iconify-icon icon="solar:magic-wand-3-bold"></iconify-icon></div>
          <div>
            <h6 class="ai-head-title">AI Assistant</h6>
            <span class="ai-head-status" id="aiStatus"><i class="ai-dot"></i>No backend connected yet</span>
          </div>
        </div>

        <div class="ai-head-actions">
          <button type="button" class="ai-head-icon-btn" id="aiRefreshHistory" title="Refresh messages" aria-label="Refresh messages">
            <iconify-icon icon="solar:refresh-linear"></iconify-icon>
          </button>
          <button type="button" class="ai-head-icon-btn" id="aiClearChat" title="Clear chat" aria-label="Clear chat">
            <iconify-icon icon="solar:trash-bin-trash-broken"></iconify-icon>
          </button>
        </div>
      </header>

      {{-- slim dev notice, hides itself once `send` route is filled --}}
      <div class="ai-offline-note" id="aiOfflineNote" style="display:none">
        <iconify-icon icon="solar:info-circle-bold"></iconify-icon>
        Backend routes are not configured yet â€” fill the <code>$aiChatRoutes</code> array at the top of this view.
      </div>

      <div class="ai-messages" id="aiMessages" role="log" aria-live="polite" aria-label="Conversation messages">

        {{-- welcome / empty state --}}
        <div class="ai-welcome" id="aiWelcome">
        <div class="ai-welcome-icon"><iconify-icon icon="solar:chat-square-like-bold"></iconify-icon></div>
          <h4 class="ai-welcome-title">Hello! How can I help you today?</h4>
          <p class="ai-welcome-sub">Ask me about sales, customers, expenses or anything else in the system.</p>
          <div class="ai-chips" id="aiChips">
            <button type="button" class="ai-chip">Summarize today's sales</button>
            <button type="button" class="ai-chip">Which branch is underperforming?</button>
            <button type="button" class="ai-chip">Draft a customer follow-up message</button>
            <button type="button" class="ai-chip">What can this AI do?</button>
          </div>
        </div>

        {{-- typing indicator --}}
        <div class="ai-msg ai-msg-ai ai-typing" id="aiTyping" style="display:none">
          <div class="ai-avatar ai-avatar-sm"><iconify-icon icon="solar:magic-wand-3-bold"></iconify-icon></div>
          <div class="ai-msg-bubble">
            <span class="ai-dot-pulse"></span><span class="ai-dot-pulse"></span><span class="ai-dot-pulse"></span>
          </div>
        </div>
      </div>

      {{-- jump to latest FAB --}}
      <button type="button" class="ai-scroll-fab" id="aiScrollFab" title="Jump to latest" aria-label="Jump to latest">
        <iconify-icon icon="solar:alt-arrow-down-linear"></iconify-icon>
      </button>

      {{-- composer --}}
      <form class="ai-composer" id="aiComposer" autocomplete="off">
        <textarea id="aiInput" class="ai-input" rows="1" placeholder="Type your message..." aria-label="Message" maxlength="8000"></textarea>
        <button type="submit" class="ai-send" id="aiSendBtn" title="Send message" aria-label="Send message">
          <iconify-icon icon="ri:send-plane-2-fill"></iconify-icon>
        </button>
      </form>
      <div class="ai-composer-note">
        <span><kbd>Enter</kbd> to send &nbsp;â€¢&nbsp; <kbd>Shift</kbd>+<kbd>Enter</kbd> for a new line</span>
        <span class="ai-disclaimer">AI can make mistakes â€” verify important details.</span>
      </div>
    </section>

    {{-- mobile sidebar backdrop --}}
    <div class="ai-sidebar-backdrop" id="aiSidebarBackdrop"></div>
  </div>
@endsection

@section('script')
<style>
    .ai-chat-page {
        --ai-accent: #487fff;
        --ai-accent-2: #7a5cff;
        --ai-bg: #f4f6fb;
        --ai-panel: #ffffff;
        --ai-panel-2: #f7f9fd;
        --ai-border: #e6eaf2;
        --ai-text: #1f2937;
        --ai-muted: #8a94a6;
        --ai-bubble-ai: #f2f5fb;
        --ai-msg-ai: #ffffff;
        --ai-header: linear-gradient(180deg, #ffffff, #f6f8fc);
        --ai-input-bg: #f4f6fb;
        --ai-shadow: 0 10px 30px -12px rgba(16, 24, 40, .14);
        position: relative;
        display: flex;
        gap: 16px;
        height: calc(100vh - 235px);
        min-height: 520px;
        max-height: calc(100vh - 235px);
    }

    [data-theme="dark"] .ai-chat-page {
        --ai-bg: #0f1420;
        --ai-panel: #161c2b;
        --ai-panel-2: #1a2133;
        --ai-border: #263043;
        --ai-text: #e7ecf5;
        --ai-muted: #8a94a6;
        --ai-bubble-ai: #1e2740;
        --ai-msg-ai: #141a29;
        --ai-header: linear-gradient(180deg, #161c2b, #141a29);
        --ai-input-bg: #1e2740;
        --ai-shadow: 0 10px 30px -12px rgba(0, 0, 0, .5);
    }

    .ai-chat-page, .ai-chat-page * { box-sizing: border-box; }
    .ai-chat-page kbd { background:#eef2f9; color:#43506b; border:1px solid #d7dee9; border-radius:4px; padding:1px 5px; font-size:11px; }
    [data-theme="dark"] .ai-chat-page kbd { background:#253048; color:#b9c4da; border-color:#35415c; }

    /* ---------- left conversations sidebar ---------- */
    .ai-chat-page .ai-chat-sidebar {
        flex: 0 0 280px; width: 280px; display: flex; flex-direction: column;
        background: var(--ai-panel); border: 1px solid var(--ai-border); border-radius: 16px;
        box-shadow: var(--ai-shadow); overflow: hidden;
    }
    .ai-chat-page .ai-chat-sidebar[hidden] { display: none; }
    .ai-chat-page .ai-sidebar-head { padding: 14px; border-bottom: 1px solid var(--ai-border); display:flex; flex-direction:column; gap:12px; }
    .ai-chat-page .ai-sidebar-title { font-size: 12px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--ai-muted); }
    .ai-chat-page .ai-btn-new-chat {
        display: inline-flex; align-items: center; justify-content: center; gap: 2px;
        padding: 9px 12px; border-radius: 10px; border: 1px dashed rgba(72,127,255,.55);
        background: rgba(72,127,255,.08); color: var(--ai-accent); font-weight: 600; font-size: 13px;
        transition: all .18s ease; width: 100%;
    }
    .ai-chat-page .ai-btn-new-chat:hover { background: rgba(72,127,255,.16); }
    .ai-chat-page .ai-sidebar-body { flex: 1; overflow-y: auto; padding: 10px; }
    .ai-chat-page .ai-conv-item {
        display: flex; align-items: center; gap: 10px; width: 100%;
        padding: 10px 12px; border-radius: 12px; border: 1px solid transparent; background: transparent;
        text-align: start; cursor: pointer; transition: background .15s ease; margin-bottom: 6px;
    }
    .ai-chat-page .ai-conv-item:hover { background: var(--ai-panel-2); }
    .ai-chat-page .ai-conv-item.active { background: rgba(72,127,255,.12); border-color: rgba(72,127,255,.35); }
    .ai-chat-page .ai-conv-item i { color: var(--ai-accent); font-style: normal; flex: 0 0 auto; }
    .ai-chat-page .ai-conv-meta { min-width: 0; }
    .ai-chat-page .ai-conv-title { display:block; font-size: 13.5px; font-weight: 600; color: var(--ai-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ai-chat-page .ai-conv-time { display:block; font-size: 11.5px; color: var(--ai-muted); }
    .ai-chat-page .ai-conv-empty {
        display: flex; flex-direction: column; align-items: center; gap: 8px;
        color: var(--ai-muted); font-size: 12.5px; padding: 28px 10px; text-align: center;
    }
    .ai-chat-page .ai-conv-empty iconify-icon { font-size: 26px; opacity: .6; }

    /* ---------- main chat panel ---------- */
    .ai-chat-page .ai-chat-main {
        flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column;
        background: var(--ai-panel); border: 1px solid var(--ai-border); border-radius: 16px;
        box-shadow: var(--ai-shadow); overflow: hidden; position: relative;
    }
    .ai-chat-page .ai-chat-head {
        display: flex; align-items: center; gap: 12px; padding: 12px 16px;
        background: var(--ai-header); border-bottom: 1px solid var(--ai-border);
    }
    .ai-chat-page .ai-head-id { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
    .ai-chat-page .ai-head-title { margin: 0; font-size: 15px; font-weight: 700; color: var(--ai-text); line-height: 1.1; }
    .ai-chat-page .ai-head-status { font-size: 12px; color: var(--ai-muted); display: inline-flex; align-items: center; gap: 5px; }
    .ai-chat-page .ai-head-status .ai-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--ai-muted); display: inline-block; }
    .ai-chat-page .ai-head-status.connected { color: #1e9e5a; }
    .ai-chat-page .ai-head-status.connected .ai-dot { background: #1e9e5a; box-shadow: 0 0 0 3px rgba(30,158,90,.18); animation: aiPulseDot 1.8s infinite; }
    [data-theme="dark"] .ai-chat-page .ai-head-status.connected { color: #4ade80; }
    @keyframes aiPulseDot { 0%,100% { box-shadow: 0 0 0 0 rgba(30,158,90,.35);} 50% { box-shadow: 0 0 0 4px rgba(30,158,90,.08);} }

    .ai-chat-page .ai-avatar {
        flex: 0 0 auto; width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center;
        background: linear-gradient(135deg, var(--ai-accent), var(--ai-accent-2)); color: #fff; font-size: 20px;
        box-shadow: 0 6px 14px -6px rgba(72, 127, 255, .6);
    }
    .ai-chat-page .ai-avatar iconify-icon { font-size: 20px; }
    .ai-chat-page .ai-avatar-sm { width: 28px; height: 28px; border-radius: 9px; }
    .ai-chat-page .ai-avatar-sm iconify-icon { font-size: 15px; }
    .ai-chat-page .ai-head-actions { display: flex; align-items: center; gap: 6px; }
    .ai-chat-page .ai-head-icon-btn {
        width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--ai-border);
        background: var(--ai-panel); color: var(--ai-text); display: grid; place-items: center;
        cursor: pointer; transition: all .15s ease; flex: 0 0 auto;
    }
    .ai-chat-page .ai-head-icon-btn:hover { border-color: var(--ai-accent); color: var(--ai-accent); background: rgba(72,127,255,.08); }
    .ai-chat-page .ai-head-icon-btn iconify-icon { font-size: 17px; }

    .ai-chat-page .ai-offline-note {
        display: flex; align-items: center; gap: 8px; padding: 8px 16px;
        background: rgba(239, 74, 0, .08); border-bottom: 1px solid rgba(239, 74, 0, .2);
        color: #b34a10; font-size: 12.5px;
    }
    .ai-chat-page .ai-offline-note code { background: rgba(239,74,0,.12); padding: 0 5px; border-radius: 4px; font-size: 11.5px; }
    [data-theme="dark"] .ai-chat-page .ai-offline-note { color: #ffb27a; }

    /* ---------- messages ---------- */
    .ai-chat-page .ai-messages { flex: 1 1 auto; overflow-y: auto; padding: 20px; scroll-behavior: smooth; background: var(--ai-bg); }
    .ai-chat-page .ai-messages::-webkit-scrollbar, .ai-chat-page .ai-sidebar-body::-webkit-scrollbar { width: 6px; }
    .ai-chat-page .ai-messages::-webkit-scrollbar-thumb, .ai-chat-page .ai-sidebar-body::-webkit-scrollbar-thumb { background: #c9d2e3; border-radius: 8px; }
    .ai-chat-page .ai-messages::-webkit-scrollbar-track, .ai-chat-page .ai-sidebar-body::-webkit-scrollbar-track { background: transparent; }

    .ai-chat-page .ai-day {
        display: flex; align-items: center; gap: 12px; margin: 14px 0 10px; color: var(--ai-muted);
        font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
    }
    .ai-chat-page .ai-day::before, .ai-chat-page .ai-day::after { content: ""; flex: 1; height: 1px; background: var(--ai-border); }

    .ai-chat-page .ai-msg { display: flex; align-items: flex-end; gap: 8px; margin-bottom: 14px; animation: aiMsgIn .28s ease both; }
    .ai-chat-page .ai-msg-ai { justify-content: flex-start; }
    .ai-chat-page .ai-msg-user { justify-content: flex-end; }
    @keyframes aiMsgIn { from { opacity: 0; transform: translateY(8px);} to { opacity: 1; transform: none; } }

    .ai-chat-page .ai-msg-bubble {
        max-width: min(680px, 78%); padding: 10px 14px; border-radius: 16px;
        font-size: 14px; line-height: 1.55; color: var(--ai-text); position: relative;
        word-wrap: break-word; white-space: pre-wrap; overflow-wrap: anywhere;
    }
    .ai-chat-page .ai-msg-ai .ai-msg-bubble { background: var(--ai-bubble-ai); color: var(--ai-text); border: 1px solid var(--ai-border); }
    .ai-chat-page .ai-msg-user .ai-msg-bubble {
        background: linear-gradient(135deg, var(--ai-accent), var(--ai-accent-2));
        color: #fff; box-shadow: 0 8px 18px -10px rgba(72,127,255,.65);
    }
    .ai-chat-page .ai-msg-time { font-size: 10.5px; color: var(--ai-muted); flex: 0 0 auto; margin-bottom: 3px; white-space: nowrap; }
    .ai-chat-page .ai-msg-user .ai-msg-time { margin-inline-end: 2px; }

    .ai-chat-page .ai-copy-btn {
        position: absolute; top: -12px; inset-inline-end: 10px; width: 24px; height: 24px; border-radius: 8px;
        border: 1px solid var(--ai-border); background: var(--ai-panel); color: var(--ai-muted);
        display: grid; place-items: center; cursor: pointer; opacity: 0; transition: all .15s ease; font-size: 13px;
    }
    .ai-chat-page .ai-msg-ai:hover .ai-copy-btn { opacity: 1; }
    .ai-chat-page .ai-copy-btn:hover { color: var(--ai-accent); border-color: var(--ai-accent); }
    .ai-chat-page .ai-copy-btn.done { color: #1e9e5a; border-color: #1e9e5a; }

    .ai-chat-page .ai-msg-error .ai-msg-bubble { background: rgba(220, 53, 69, .08) !important; border: 1px solid rgba(220, 53, 69, .3) !important; color: #b02a37 !important; }
    [data-theme="dark"] .ai-chat-page .ai-msg-error .ai-msg-bubble { color: #ffb3b3 !important; }

    .ai-chat-page .ai-typing .ai-msg-bubble { display: inline-flex; align-items: center; gap: 5px; padding: 14px 16px; }
    .ai-chat-page .ai-dot-pulse { width: 7px; height: 7px; border-radius: 50%; background: var(--ai-accent); display: inline-block; animation: aiDotPulse 1.2s infinite ease-in-out; }
    .ai-chat-page .ai-dot-pulse:nth-child(2) { animation-delay: .18s; }
    .ai-chat-page .ai-dot-pulse:nth-child(3) { animation-delay: .36s; }
    @keyframes aiDotPulse { 0%,60%,100% { transform: translateY(0); opacity:.45;} 30% { transform: translateY(-5px); opacity:1; } }

    /* ---------- welcome ---------- */
    .ai-chat-page .ai-welcome {
        height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center; padding: 20px;
    }
    .ai-chat-page .ai-welcome-icon {
        width: 72px; height: 72px; border-radius: 24px; display: grid; place-items: center; margin-bottom: 16px;
        background: linear-gradient(135deg, var(--ai-accent), var(--ai-accent-2)); color: #fff; font-size: 34px;
        box-shadow: 0 14px 30px -10px rgba(72,127,255,.55); animation: aiFloat 3.2s ease-in-out infinite;
    }
    @keyframes aiFloat { 0%,100% { transform: translateY(0);} 50% { transform: translateY(-7px);} }
    .ai-chat-page .ai-welcome-title { margin: 0 0 6px; font-weight: 700; color: var(--ai-text); font-size: 20px; }
    .ai-chat-page .ai-welcome-sub { margin: 0 0 22px; color: var(--ai-muted); font-size: 13.5px; }
    .ai-chat-page .ai-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; max-width: 560px; }
    .ai-chat-page .ai-chip {
        border: 1px solid var(--ai-border); background: var(--ai-panel); color: var(--ai-text);
        padding: 9px 16px; border-radius: 999px; font-size: 13px; font-weight: 500; cursor: pointer;
        transition: all .18s ease; display: inline-flex; align-items: center; gap: 6px;
    }
    .ai-chat-page .ai-chip::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: linear-gradient(135deg, var(--ai-accent), var(--ai-accent-2)); }
    .ai-chat-page .ai-chip:hover { border-color: var(--ai-accent); color: var(--ai-accent); background: rgba(72,127,255,.07); transform: translateY(-1px); }

    /* ---------- scroll fab ---------- */
    .ai-chat-page .ai-scroll-fab {
        position: absolute; bottom: 118px; inset-inline-end: 22px; width: 40px; height: 40px; border-radius: 50%;
        border: 1px solid var(--ai-border); background: var(--ai-panel); color: var(--ai-accent);
        display: grid; place-items: center; cursor: pointer; box-shadow: var(--ai-shadow);
        opacity: 0; visibility: hidden; transform: translateY(8px); transition: all .2s ease; z-index: 5;
    }
    .ai-chat-page .ai-scroll-fab.show { opacity: 1; visibility: visible; transform: none; }

    /* ---------- composer ---------- */
    .ai-chat-page .ai-composer { display: flex; align-items: flex-end; gap: 10px; padding: 12px 16px 6px; background: var(--ai-panel); }
    .ai-chat-page .ai-input {
        flex: 1; resize: none; border: 1.5px solid var(--ai-border); background: var(--ai-input-bg);
        border-radius: 14px; padding: 12px 14px; font-size: 14px; color: var(--ai-text); max-height: 160px;
        line-height: 1.5; outline: none; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ai-chat-page .ai-input:focus { border-color: var(--ai-accent); box-shadow: 0 0 0 3px rgba(72,127,255,.15); }
    .ai-chat-page .ai-input:disabled { opacity: .6; }
    .ai-chat-page .ai-input::placeholder { color: var(--ai-muted); }
    .ai-chat-page .ai-send {
        flex: 0 0 auto; width: 46px; height: 46px; border: none; border-radius: 14px; cursor: pointer;
        background: linear-gradient(135deg, var(--ai-accent), var(--ai-accent-2)); color: #fff; font-size: 21px;
        box-shadow: 0 8px 18px -8px rgba(72,127,255,.7); transition: transform .12s ease, opacity .15s ease, filter .15s ease;
    }
    .ai-chat-page .ai-send:hover { filter: brightness(1.07); }
    .ai-chat-page .ai-send:active { transform: scale(.93); }
    .ai-chat-page .ai-send:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; }
    .ai-chat-page .ai-composer-note {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 4px 18px 12px; font-size: 11px; color: var(--ai-muted);
    }
    .ai-chat-page .ai-disclaimer { opacity: .75; }

    /* ---------- mobile ---------- */
    .ai-chat-page .ai-sidebar-backdrop {
        position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 1040;
        opacity: 0; visibility: hidden; transition: opacity .2s ease;
    }
    @media (max-width: 1199.98px) {
        .ai-chat-page .ai-chat-sidebar {
            position: fixed; top: 0; bottom: 0; inset-inline-start: -300px; z-index: 1041;
            width: 280px; flex-basis: 280px; border-radius: 0 16px 16px 0; transition: inset-inline-start .25s ease;
        }
        .ai-chat-page .ai-chat-sidebar.mobile-open { inset-inline-start: 0; }
        .ai-chat-page .ai-sidebar-backdrop.show { opacity: 1; visibility: visible; }
        .ai-chat-page { margin-inline-end: -0.9375rem; margin-inline-start: -0.9375rem; }
    }
    .ai-chat-page .ai-head-icon-btn#aiConvsToggle { display: none; }
    @media (max-width: 1199.98px) {
        .ai-chat-page .ai-head-icon-btn#aiConvsToggle { display: grid; }
    }
    @media (min-width: 1400px) {
        .ai-chat-page { margin-inline-end: -1.5rem; margin-inline-start: -1.5rem; }
    }
</style>

<script>
    (function () {
        "use strict";

        /* ============================================================
           ROUTE CONFIG â€” filled from the Blade PHP block at the top.
           Replace placeholders there, nothing to change here.
           ============================================================ */
        const R = @json($aiChatRoutes);

        const $page      = $("#aiChatPage");
        const $messages  = $("#aiMessages");
        const $input     = $("#aiInput");
        const $form      = $("#aiComposer");
        const $sendBtn   = $("#aiSendBtn");
        const $typing    = $("#aiTyping");
        const $welcome   = $("#aiWelcome");
        const $convList  = $("#aiConvList");
        const $sidebar   = $("#aiSidebar");
        const $backdrop  = $("#aiSidebarBackdrop");
        const $status    = $("#aiStatus");
        const $fab       = $("#aiScrollFab");

        /* fallbacks in case the sidebar include is absent */
        if (!$sidebar.length) return;

        const state = { busy: false, conversationId: null, lastDay: null };

        /* ---------------- helpers ---------------- */

        function csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || "";
        }

        function fmtTime(d) {
            return d.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
        }

        function dayLabel(d) {
            const today = new Date();
            const yest = new Date(); yest.setDate(yest.getDate() - 1);
            const same = (a, b) => a.toDateString() === b.toDateString();
            if (same(d, today)) return "Today";
            if (same(d, yest)) return "Yesterday";
            return d.toLocaleDateString([], { day: "numeric", month: "short", year: "numeric" });
        }

        function isUser(role) {
            return /^(user|human|me|client)$/i.test(String(role || "").trim());
        }

        /* normalize any backend field names into { role, text, time } */
        function normalizeMessage(m) {
            const text = m?.message ?? m?.content ?? m?.text ?? m?.reply ?? m?.answer ?? m?.body ?? "";
            return {
                text: String(text),
                user: isUser(m?.role ?? m?.sender ?? m?.author ?? (String(text).trim() === "" ? "ai" : "")) || false,
                time: m?.created_at ?? m?.time ?? m?.date ?? null
            };
        }

        function ensureDayDivider(date) {
            const label = dayLabel(date);
            if (state.lastDay === label) return;
            state.lastDay = label;
            $typing.before($("<div/>", { class: "ai-day", text: label }));
        }

        function scrollBottom(smooth) {
            $messages[0].scrollTo({
                top: $messages[0].scrollHeight,
                behavior: smooth === false ? "auto" : "smooth"
            });
        }

        function setBusy(b) {
            state.busy = b;
            $sendBtn.prop("disabled", b);
            $input.prop("disabled", b);
            if (!b) $input.trigger("focus");
        }

        function renderMessage({ text, user, time, error, skipCopy }) {
            const d = time && !isNaN(new Date(time)) ? new Date(time) : new Date();
            ensureDayDivider(d);

            const $row = $("<div/>", {
                class: "ai-msg " + (user ? "ai-msg-user" : "ai-msg-ai") + (error ? " ai-msg-error" : "")
            });

            if (!user) {
                $row.append($("<div/>", { class: "ai-avatar ai-avatar-sm" })
                    .append($("<iconify-icon/>", { icon: "solar:magic-wand-3-bold" })));
            }

            const $bubble = $("<div/>", { class: "ai-msg-bubble", text: text });

            if (!user && !error && !skipCopy && text) {
                const $copy = $("<button/>", { type: "button", class: "ai-copy-btn", title: "Copy", "aria-label": "Copy message" })
                    .append($("<iconify-icon/>", { icon: "solar:copy-linear" }))
                    .on("click", function () {
                        navigator.clipboard?.writeText(text).then(() => {
                            const $b = $(this).addClass("done");
                            $b.find("iconify-icon").attr("icon", "solar:check-circle-bold");
                            setTimeout(() => $b.removeClass("done")
                                .find("iconify-icon").attr("icon", "solar:copy-linear"), 1600);
                        });
                    });
                $bubble.append($copy);
            }

            $row.append($bubble);
            $row.append($("<span/>", { class: "ai-msg-time", text: fmtTime(d) }));

            $typing.before($row);
            scrollBottom();
        }

        function showWelcome() {
            $messages.children(".ai-day").remove();
            $messages.children(".ai-msg:not(.ai-typing)").remove();
            state.lastDay = null;
            $welcome.css("display", "flex");
        }

        function hideWelcome() {
            $welcome.hide();
        }

        function status(connected) {
            $status
                .toggleClass("connected", !!connected)
                .html('<i class="ai-dot"></i>' + (connected ? "Connected" : "No backend connected yet"));
        }

        function toast(msg, icon) {
            Swal.fire({ toast: true, position: "top-end", icon: icon || "info", title: msg, timer: 2400, showConfirmButton: false });
        }

        /* ---------------- conversations (left panel) ---------------- */

        function renderConversations(list, activeId) {
            $convList.empty();
            if (!Array.isArray(list) || !list.length) {
                $convList.html('<div class="ai-conv-empty"><iconify-icon icon="solar:chat-square-like-linear"></iconify-icon><span>No conversations yet</span></div>');
                return;
            }
            list.forEach(c => {
                const time = c.updated_at ?? c.updated ?? c.time;
                const d = time && !isNaN(new Date(time)) ? new Date(time) : null;
                const $item = $("<button/>", {
                    type: "button",
                    class: "ai-conv-item" + (String(c.id) === String(activeId ?? "") ? " active" : ""),
                    "data-id": c.id
                })
                    .append($("<i/>").append($("<iconify-icon/>", { icon: "solar:chat-square-like-linear" })))
                    .append($("<span/>", { class: "ai-conv-meta" })
                        .append($("<span/>", { class: "ai-conv-title", text: c.title ?? c.name ?? c.subject ?? ("Chat #" + c.id) }))
                        .append($("<span/>", { class: "ai-conv-time", text: d ? d.toLocaleString([], { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }) : "" })))
                    .on("click", function () {
                        $convList.find(".ai-conv-item").removeClass("active");
                        $(this).addClass("active");
                        loadHistory(this.dataset.id);
                        closeMobileSidebar();
                    });
                $convList.append($item);
            });
        }

        function loadConversations(activeId) {
            if (!R.conversations) return;
            $.get(R.conversations)
                .done(res => renderConversations(res?.conversations ?? res ?? [], res?.active_id ?? activeId))
                .fail(() => $convList.html('<div class="ai-conv-empty"><span>Could not load conversations</span></div>'));
        }

        function refreshConversations() {
            if (!R.conversations) return;
            loadConversations(state.conversationId);
        }

        /* ---------------- message history ---------------- */

        function loadHistory(conversationId) {
            if (!R.history) return;
            state.conversationId = conversationId ?? null;
            state.lastDay = null;
            $messages.children(".ai-msg:not(.ai-typing), .ai-day").remove();
            hideWelcome();
            setBusy(true);
            $typing.css("display", "flex");
            scrollBottom(false);

            let url = R.history;
            if (conversationId) url += (url.includes("?") ? "&" : "?") + "conversation_id=" + encodeURIComponent(conversationId);

            $.get(url)
                .done(res => {
                    $messages.children(".ai-msg:not(.ai-typing), .ai-day").remove();
                    state.lastDay = null;
                    const msgs = res?.messages ?? res ?? [];
                    if (Array.isArray(msgs) && msgs.length) {
                        msgs.forEach(m => renderMessage(normalizeMessage(m)));
                    } else {
                        showWelcome();
                    }
                })
                .fail(() => {
                    showWelcome();
                    toast("Failed to load message history", "error");
                })
                .always(() => {
                    $typing.css("display", "none");
                    setBusy(false);
                    scrollBottom(false);
                });
        }

        /* ---------------- sending ---------------- */

        function appendAssistantReply(res) {
            /* store the conversation id returned with the reply; an empty/missing id
               never overwrites the current one, so continuity is preserved */
            const conversationId = res?.conversation_id ?? res?.conversationId;
            if (conversationId !== undefined && conversationId !== null && String(conversationId).trim() !== "") {
                state.conversationId = String(conversationId);
            }

            const fromMessages = Array.isArray(res?.messages) ? res.messages.map(normalizeMessage) : null;
            const replyText = res?.reply ?? res?.message ?? res?.content ?? res?.text ?? res?.answer ?? "";

            if (fromMessages && fromMessages.length) {
                fromMessages.forEach(m => renderMessage(m));
            } else if (String(replyText).trim() !== "") {
                renderMessage({ text: String(replyText), user: false, time: res?.created_at ?? res?.time });
            } else {
                renderMessage({ text: "\u26A0\uFE0F The AI returned an empty response.", user: false, error: true });
            }
        }

        function send(message) {
            const text = message ?? String($input.val() ?? "").trim();
            if (!text || state.busy) return;

            if (!R.send) {
                Swal.fire({
                    icon: "info",
                    title: "AI backend not connected yet",
                    html: "This chat UI is ready â€” just fill the <code>$aiChatRoutes</code> array<br>at the top of the view <code>backend/modules/ai_chat/index.blade.php</code>.",
                    confirmButtonColor: "#487fff"
                });
                return;
            }

            hideWelcome();
            renderMessage({ text, user: true, time: Date.now() });
            $input.val("").trigger("input");
            setBusy(true);
            $typing.css("display", "flex");
            scrollBottom();

            let payload = { message: text };
            /* send the stored conversation id with the next query; omit the field entirely when empty */
            if (state.conversationId) payload.conversation_id = state.conversationId;

            $.ajax({
                url: R.send,
                method: "POST",
                data: JSON.stringify(payload),
                contentType: "application/json",
                headers: { "X-CSRF-TOKEN": csrf(), "Accept": "application/json" },
                success: res => { appendAssistantReply(res); },
                error: xhr => {
                    let msg;
                    if (xhr.status === 419) msg = "Session expired â€” please reload the page.";
                    else if (xhr.status === 422) msg = xhr.responseJSON?.message ?? Object.values(xhr.responseJSON?.errors ?? {})[0]?.[0] ?? "The message was rejected.";
                    else if (xhr.status === 401 || xhr.status === 403) msg = xhr.responseJSON?.message ?? "You are not authorized.";
                    else msg = xhr.responseJSON?.message ?? "Something went wrong while contacting the AI.";
                    renderMessage({ text: "\u26A0\uFE0F " + msg, user: false, error: true });
                },
                complete: () => {
                    $typing.css("display", "none");
                    setBusy(false);
                    scrollBottom(false);
                    refreshConversations();
                }
            });
        }

        /* ---------------- composer events ---------------- */

        $form.on("submit", function (e) { e.preventDefault(); send(); });

        $input.on("keydown", function (e) {
            if (e.isComposing || e.keyCode === 229) return; /* IME composition (Bangla keyboard) */
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                $form.trigger("submit");
            }
        });

        $input.on("input", function () {
            this.style.height = "auto";
            this.style.height = Math.min(this.scrollHeight, 160) + "px";
        });

        /* suggestion chips */
        $(document).on("click", "#aiChips .ai-chip", function () {
            $input.val($(this).text().trim()).trigger("input");
            $form.trigger("submit");
        });

        /* ---------------- header actions ---------------- */

        $("#aiRefreshHistory").on("click", () => R.history ? loadHistory(state.conversationId) : toast("History route is not configured", "info"));

        $("#aiClearChat").on("click", function () {
            Swal.fire({
                icon: "question",
                title: "Clear this chat?",
                text: "The conversation will be reset.",
                showCancelButton: true,
                confirmButtonText: "Clear",
                confirmButtonColor: "#e60023",
                cancelButtonText: "Cancel",
                focusCancel: true
            }).then(r => {
                if (!r.isConfirmed) return;
                const clear = () => { state.conversationId = null; showWelcome(); state.lastDay = null; refreshConversations(); };
                if (!R.clear) { clear(); toast("Chat cleared (local view only)", "success"); return; }
                $.post({
                    url: R.clear,
                    headers: { "X-CSRF-TOKEN": csrf() },
                    data: { conversation_id: state.conversationId }
                })
                    .done(() => { clear(); toast("Chat cleared", "success"); })
                    .fail(() => toast("Could not clear chat on the server", "error"));
            });
        });

        function newChat() {
            state.conversationId = null;
            state.lastDay = null;
            $messages.children(".ai-msg:not(.ai-typing), .ai-day").remove();
            showWelcome();
            $convList.find(".ai-conv-item").removeClass("active");
            $input.trigger("focus");
        }

        $("#aiNewChat").on("click", newChat);

        /* ---------------- sidebar (mobile) ---------------- */

        function openMobileSidebar() {
            $sidebar.addClass("mobile-open");
            $backdrop.addClass("show");
        }
        function closeMobileSidebar() {
            $sidebar.removeClass("mobile-open");
            $backdrop.removeClass("show");
        }
        $("#aiConvsToggle").on("click", () => $sidebar.hasClass("mobile-open") ? closeMobileSidebar() : openMobileSidebar());
        $backdrop.on("click", closeMobileSidebar);

        /* ---------------- scroll fab ---------------- */

        $messages.on("scroll", function () {
            const el = this;
            const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 160;
            $fab.toggleClass("show", !nearBottom);
        });
        $fab.on("click", () => scrollBottom());

        /* ---------------- init ---------------- */

        status(!!R.send);
        $("#aiOfflineNote").toggle(!R.send);
        if (!R.send && !R.conversations) $sidebar[0].hidden = true;
        if (R.conversations) { $sidebar[0].hidden = false; loadConversations(); }
        if (R.history) { loadHistory(null); }   /* active session messages (active conversation) */
        $input.trigger("focus");

        /* sentinel marker: AI_CHAT_INTERFACE_JS_END */
    })();
</script>
@endsection
