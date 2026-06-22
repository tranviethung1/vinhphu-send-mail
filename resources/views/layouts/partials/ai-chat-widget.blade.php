<style>
    /* ── Floating Widget Button ──────────────────────────── */
    #aiChatToggle {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 9998;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        border: none;
        /* background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); */
        color: #fff;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(59,130,246,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform .25s, box-shadow .25s;
    }
    #aiChatToggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 28px rgba(59,130,246,0.55);
    }
    #aiChatToggle .badge {
        position: absolute;
        top: -4px; right: -4px;
        width: 18px; height: 18px;
        border-radius: 50%;
        background: #ef4444;
        font-size: 10px;
        font-weight: 700;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
    }

    /* ── Chat Panel ──────────────────────────────────────── */
    #aiChatPanel {
        position: fixed;
        bottom: 96px;
        right: 28px;
        z-index: 9997;
        width: 380px;
        max-width: calc(100vw - 40px);
        height: 560px;
        max-height: calc(100vh - 120px);
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 8px 40px rgba(0,0,0,0.18);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform-origin: bottom right;
        transform: scale(0.85) translateY(20px);
        opacity: 0;
        pointer-events: none;
        transition: transform .25s cubic-bezier(.34,1.56,.64,1), opacity .2s ease;
    }
    #aiChatPanel.open {
        transform: scale(1) translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    /* ── Header ─────────────────────────────────────────── */
    .wgt-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: #fff;
        flex-shrink: 0;
    }
    .wgt-header-left { display: flex; align-items: center; gap: 10px; }
    .wgt-avatar {
        width: 38px; height: 38px; border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }
    .wgt-title   { font-size: 14px; font-weight: 700; }
    .wgt-sub     { font-size: 11px; opacity: 0.8; margin-top: 1px; }
    .wgt-status  { display: flex; align-items: center; gap: 5px; font-size: 11px; opacity: .9; }
    .wgt-dot     {
        width: 7px; height: 7px; border-radius: 50%;
        background: #4ade80;
        animation: wgtPulse 2s infinite;
    }
    @keyframes wgtPulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .wgt-actions { display: flex; align-items: center; gap: 6px; }
    .wgt-btn {
        width: 30px; height: 30px; border-radius: 8px; border: none;
        background: rgba(255,255,255,0.15); color: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 14px;
        transition: background .2s;
    }
    .wgt-btn:hover { background: rgba(255,255,255,0.28); }
    .wgt-btn.spin svg { animation: wgtSpin 1s linear infinite; }
    @keyframes wgtSpin { to { transform: rotate(360deg); } }

    /* ── Messages ────────────────────────────────────────── */
    .wgt-messages {
        flex: 1; overflow-y: auto; padding: 16px 14px;
        display: flex; flex-direction: column; gap: 12px;
        scroll-behavior: smooth;
        background: #f8fafc;
    }
    .wgt-messages::-webkit-scrollbar { width: 4px; }
    .wgt-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

    .wgt-msg { display: flex; align-items: flex-end; gap: 8px; max-width: 88%; }
    .wgt-msg.user { align-self: flex-end; flex-direction: row-reverse; }
    .wgt-msg.bot  { align-self: flex-start; }

    .wgt-msg-av {
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; flex-shrink: 0;
    }
    .wgt-msg.user .wgt-msg-av { background: #3b82f6; color: #fff; }
    .wgt-msg.bot  .wgt-msg-av { background: #e2e8f0; color: #475569; }

    .wgt-bubble {
        padding: 10px 13px; border-radius: 14px;
        font-size: 13px; line-height: 1.55; word-break: break-word;
    }
    .wgt-msg.user .wgt-bubble {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff; border-bottom-right-radius: 4px;
    }
    .wgt-msg.bot .wgt-bubble {
        background: #fff; color: #1e293b;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
    .wgt-time {
        font-size: 10px; color: #94a3b8; margin-top: 3px;
    }
    .wgt-msg.user .wgt-time { text-align: right; }
    .wgt-msg.bot  .wgt-time { text-align: left; }

    /* ── Typing ──────────────────────────────────────────── */
    .wgt-typing { display: flex; align-items: center; gap: 4px; padding: 2px; }
    .wgt-typing span {
        width: 6px; height: 6px; background: #94a3b8; border-radius: 50%;
        animation: wgtBounce 1.2s infinite;
    }
    .wgt-typing span:nth-child(2) { animation-delay: .2s; }
    .wgt-typing span:nth-child(3) { animation-delay: .4s; }
    @keyframes wgtBounce {
        0%,80%,100% { transform: translateY(0); }
        40%          { transform: translateY(-7px); }
    }

    /* ── Welcome ─────────────────────────────────────────── */
    .wgt-welcome {
        display: flex; flex-direction: column;
        align-items: center; text-align: center;
        gap: 10px; padding: 24px 16px;
        color: #94a3b8;
    }
    .wgt-welcome-icon {
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
    }
    .wgt-welcome-title { font-size: 14px; font-weight: 600; color: #475569; }
    .wgt-welcome-sub   { font-size: 12px; line-height: 1.5; max-width: 260px; }
    .wgt-quick { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 6px; }
    .wgt-quick-btn {
        padding: 5px 11px; border-radius: 16px;
        border: 1.5px solid #bfdbfe; background: #fff;
        color: #1d4ed8; font-size: 11px; cursor: pointer;
        transition: all .2s;
    }
    .wgt-quick-btn:hover { background: #dbeafe; border-color: #3b82f6; }

    /* ── Input ───────────────────────────────────────────── */
    .wgt-input-area {
        padding: 10px 12px 12px;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        flex-shrink: 0;
    }
    .wgt-input-wrap {
        display: flex; align-items: flex-end; gap: 8px;
        background: #f1f5f9; border-radius: 12px;
        padding: 8px 10px;
        border: 2px solid transparent; transition: border-color .2s;
    }
    .wgt-input-wrap:focus-within { border-color: #3b82f6; background: #fff; }
    #wgtInput {
        flex: 1; border: none; outline: none; background: transparent;
        font-size: 13px; color: #1e293b; resize: none;
        max-height: 90px; min-height: 20px; line-height: 1.5;
        font-family: inherit;
    }
    #wgtInput::placeholder { color: #94a3b8; }
    .wgt-send {
        width: 32px; height: 32px; border-radius: 8px; border: none;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: opacity .2s, transform .1s;
    }
    .wgt-send:hover   { opacity: .9; transform: scale(1.05); }
    .wgt-send:active  { transform: scale(.95); }
    .wgt-send:disabled { opacity: .5; cursor: not-allowed; transform: none; }
    .wgt-hint {
        font-size: 10px; color: #94a3b8; text-align: center; margin-top: 6px;
    }
    .wgt-bubble strong { font-weight: 700; }
    .wgt-bubble em     { font-style: italic; }
    .wgt-bubble code   { background: #f1f5f9; padding: 1px 5px; border-radius: 3px; font-family: monospace; font-size: 12px; }
    .wgt-bubble a      { color: #3b82f6; text-decoration: underline; }

    /* ── Modern AI Icon ─────────────────────────────────── */
    #aiChatToggle .ai-icon-wrap {
        position: relative;
        width: 50px; height: 50px;
        display: flex; align-items: center; justify-content: center;
    }
    #aiChatToggle .ai-icon-wrap::before {
        content: '';
        position: absolute; inset: -6px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, #a855f7, #06b6d4, #3b82f6, #a855f7);
        animation: aiSpin 3s linear infinite;
        opacity: 0.6;
    }
    #aiChatToggle .ai-icon-wrap::after {
        content: '';
        position: absolute; inset: -4px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e40af, #7c3aed);
    }
    #aiChatToggle .ai-icon-wrap svg {
        position: relative; z-index: 1;
    }
    @keyframes aiSpin { to { transform: rotate(360deg); } }

    /* Sparkle dots on toggle */
    #aiChatToggle .sparkle {
        position: absolute;
        width: 4px; height: 4px;
        border-radius: 50%;
        background: #fff;
        animation: sparklePop 2s ease-in-out infinite;
    }
    #aiChatToggle .sparkle:nth-child(1) { top: 4px;  right: 6px;  animation-delay: 0s; }
    #aiChatToggle .sparkle:nth-child(2) { bottom: 5px; left: 7px; animation-delay: .7s; }
    #aiChatToggle .sparkle:nth-child(3) { top: 50%;  right: 3px;  animation-delay: 1.4s; }
    @keyframes sparklePop {
        0%,100% { transform: scale(0); opacity: 0; }
        50%      { transform: scale(1); opacity: 1; }
    }

    /* Header avatar glow */
    .wgt-avatar-modern {
        width: 36px; height: 36px; border-radius: 12px;
        background: linear-gradient(135deg, #7c3aed 0%, #2563eb 50%, #06b6d4 100%);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 0 12px rgba(124,58,237,0.5);
        position: relative; overflow: hidden;
    }
    .wgt-avatar-modern::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.15) 50%, transparent 70%);
        animation: avatarShimmer 2.5s linear infinite;
    }
    @keyframes avatarShimmer {
        0%   { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%)  translateY(100%)  rotate(45deg); }
    }

    /* Welcome orb */
    .wgt-welcome-orb {
        width: 64px; height: 64px;
        border-radius: 20px;
        background: linear-gradient(135deg, #7c3aed, #2563eb, #06b6d4);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 8px 24px rgba(124,58,237,0.35);
        animation: orbFloat 3s ease-in-out infinite;
    }
    @keyframes orbFloat {
        0%,100% { transform: translateY(0); }
        50%      { transform: translateY(-6px); }
    }

    /* Msg avatar modern */
    .wgt-av-ai {
        width: 28px; height: 28px; border-radius: 8px;
        background: linear-gradient(135deg, #7c3aed, #2563eb);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .wgt-av-user {
        width: 28px; height: 28px; border-radius: 8px;
        background: linear-gradient(135deg, #3b82f6, #0ea5e9);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
</style>

{{-- Toggle Button --}}
<button id="aiChatToggle" onclick="wgtToggle()" title="Trợ lý AI">
    <span class="sparkle"></span>
    <span class="sparkle"></span>
    <span class="sparkle"></span>
    <span id="wgtIconOpen" class="ai-icon-wrap">
        <img src="{{ asset('ai_icon.webp') }}" alt="AI" style="width:52px;height:52px;border-radius:50%;object-fit:cover;position:relative;z-index:1;">
    </span>
    <span id="wgtIconClose" style="display:none;font-size:28px;font-weight:400;line-height:1;color:#1e293b;">✕</span>
    <span class="badge" id="wgtBadge">1</span>
</button>

{{-- Chat Panel --}}
<div id="aiChatPanel">

    {{-- Header --}}
    <div class="wgt-header">
        <div class="wgt-header-left">
            <div class="wgt-avatar-modern">
                <img src="{{ asset('ai_icon.webp') }}" alt="AI" style="width:100%;height:100%;object-fit:cover;border-radius:12px;position:relative;z-index:1;">
            </div>
            <div>
                <div class="wgt-title">Trợ lý AI</div>
                <div class="wgt-sub" id="wgtProviderLabel">Powered by AI</div>
            </div>
        </div>
        <div class="wgt-actions">
            <div class="wgt-status">
                <div class="wgt-dot"></div>
                <span style="font-size:11px;">Online</span>
            </div>
            <button class="wgt-btn" id="wgtRefreshBtn" onclick="wgtRefresh()" title="Cập nhật dữ liệu">
                <svg id="wgtRefreshIcon" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <button class="wgt-btn" onclick="wgtClear()" title="Xoá lịch sử">🗑</button>
            <button class="wgt-btn" onclick="wgtToggle()" title="Đóng">✕</button>
        </div>
    </div>

    {{-- Messages --}}
    <div class="wgt-messages" id="wgtMessages">
        <div class="wgt-welcome" id="wgtWelcome">
            <div class="wgt-welcome-orb">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L9 9H2L7.5 13.5L5.5 21L12 16.5L18.5 21L16.5 13.5L22 9H15L12 2Z" fill="white" opacity="0.95"/>
                    <circle cx="12" cy="12" r="3" fill="white"/>
                </svg>
            </div>
            <div class="wgt-welcome-title">Xin chào! Tôi là trợ lý AI</div>
            <div class="wgt-welcome-sub">Hãy hỏi tôi bất cứ điều gì về công ty Vinh Phú!</div>
            <div class="wgt-quick">
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Công ty thành lập năm nào?')">📅 Năm thành lập</button>
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Số nhân viên hiện tại?')">👥 Nhân viên</button>
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Các phòng ban trong công ty?')">🏢 Phòng ban</button>
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Địa chỉ công ty ở đâu?')">📍 Địa chỉ</button>
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Giám đốc công ty là ai?')">👔 Giám đốc</button>
            </div>
        </div>
    </div>

    {{-- Input --}}
    <div class="wgt-input-area">
        <div class="wgt-input-wrap">
            <textarea
                id="wgtInput"
                placeholder="Nhập câu hỏi..."
                rows="1"
                onkeydown="wgtKeyDown(event)"
                oninput="wgtAutoResize(this)"
            ></textarea>
            <button class="wgt-send" id="wgtSendBtn" onclick="wgtSend()" title="Gửi">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
        <div class="wgt-hint">
            <kbd style="background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:10px;">Enter</kbd> gửi ·
            <kbd style="background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:10px;">Shift+Enter</kbd> xuống dòng
        </div>
    </div>
</div>

<script>
(function () {
    const CHAT_URL    = '{{ route("ai-chat.chat") }}';
    const REFRESH_URL = '{{ route("ai-chat.refresh") }}';
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const COOKIE_KEY  = 'ai_chat_history';
    const COOKIE_DAYS = 7;
    const MAX_HISTORY = 20; // giới hạn để không vượt quá 4KB cookie

    let history  = [];
    let busy     = false;
    let isOpen   = false;
    let unread   = 0;

    // ── Cookie helpers ───────────────────────────────────────────────
    function cookieSave(data) {
        try {
            const str     = JSON.stringify(data);
            const expires = new Date(Date.now() + COOKIE_DAYS * 864e5).toUTCString();
            document.cookie = `${COOKIE_KEY}=${encodeURIComponent(str)};expires=${expires};path=/;SameSite=Lax`;
        } catch (e) { /* nếu quá lớn thì bỏ qua */ }
    }

    function cookieLoad() {
        try {
            const match = document.cookie.match(new RegExp('(?:^|; )' + COOKIE_KEY + '=([^;]*)'));
            return match ? JSON.parse(decodeURIComponent(match[1])) : null;
        } catch (e) { return null; }
    }

    function cookieClear() {
        document.cookie = `${COOKIE_KEY}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Lax`;
    }

    function saveHistory() {
        // Chỉ lưu tối đa MAX_HISTORY tin nhắn gần nhất
        cookieSave(history.slice(-MAX_HISTORY));
    }

    // ── Restore chat từ cookie khi load trang ────────────────────────
    function restoreFromCookie() {
        const saved = cookieLoad();
        if (!saved || !Array.isArray(saved) || saved.length === 0) return;

        history = saved;
        hideWelcome();

        saved.forEach(msg => {
            appendMsgUI(msg.role === 'model' ? 'bot' : 'user', msg.text, msg.time || '');
        });

        // Scroll xuống dưới
        const msgs = document.getElementById('wgtMessages');
        if (msgs) msgs.scrollTop = msgs.scrollHeight;
    }

    // ── Toggle ──────────────────────────────────────────────────────
    window.wgtToggle = function () {
        isOpen = !isOpen;
        document.getElementById('aiChatPanel').classList.toggle('open', isOpen);
        document.getElementById('wgtIconOpen').style.display  = isOpen ? 'none'  : '';
        document.getElementById('wgtIconClose').style.display = isOpen ? ''      : 'none';
        document.getElementById('aiChatToggle').style.background = isOpen ? '#f1f5f9' : '';
        if (isOpen) {
            unread = 0;
            document.getElementById('wgtBadge').style.display = 'none';
            setTimeout(() => document.getElementById('wgtInput').focus(), 250);
        }
    };

    // ── Send ─────────────────────────────────────────────────────────
    window.wgtSend = function () {
        const input = document.getElementById('wgtInput');
        const text  = input.value.trim();
        if (!text || busy) return;

        hideWelcome();
        const time = nowTime();
        appendMsgUI('user', text, time);
        input.value = '';
        wgtAutoResize(input);
        history.push({ role: 'user', text, time });
        saveHistory();

        showTyping();
        setBusy(true);

        fetch(CHAT_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ message: text, history: history.slice(-10, -1) }),
        })
        .then(r => r.json())
        .then(data => {
            removeTyping();
            const answer   = data.answer || 'Không có phản hồi.';
            const provider = data.source  || 'ai';
            const ansTime  = nowTime();
            appendMsgUI('bot', answer, ansTime);
            history.push({ role: 'model', text: answer, time: ansTime });
            saveHistory();

            // Cập nhật label provider
            const lbl = document.getElementById('wgtProviderLabel');
            if (lbl) lbl.textContent = provider === 'deepseek' ? 'Powered by DeepSeek' : 'Powered by Gemini';

            // Badge nếu panel đóng
            if (!isOpen) { unread++; showBadge(); }
        })
        .catch(() => {
            removeTyping();
            appendMsgUI('bot', '❌ Lỗi kết nối. Vui lòng thử lại.', nowTime());
        })
        .finally(() => setBusy(false));
    };

    window.wgtSendQuick = function (text) {
        document.getElementById('wgtInput').value = text;
        wgtSend();
    };

    window.wgtClear = function () {
        history = [];
        cookieClear();
        const msgs = document.getElementById('wgtMessages');
        msgs.innerHTML = '';
        msgs.appendChild(makeWelcome());
        showToast('Đã xoá lịch sử trò chuyện', true);
    };

    // ── Refresh ──────────────────────────────────────────────────────
    window.wgtRefresh = function () {
        const btn  = document.getElementById('wgtRefreshBtn');
        const icon = document.getElementById('wgtRefreshIcon');
        btn.disabled = true;
        icon.style.animation = 'wgtSpin 1s linear infinite';

        fetch(REFRESH_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        })
        .then(r => r.json())
        .then(d => showToast(d.message || 'Đã cập nhật!', true))
        .catch(()  => showToast('Lỗi khi cập nhật!', false))
        .finally(() => { btn.disabled = false; icon.style.animation = ''; });
    };

    // ── DOM helpers ──────────────────────────────────────────────────
    /**
     * Chỉ render lên DOM, không lưu vào history (dùng khi restore từ cookie).
     */
    const AI_AV   = `<div class="wgt-av-ai"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2L9.5 8.5H3L8 12.5L6 19.5L12 15.5L18 19.5L16 12.5L21 8.5H14.5L12 2Z" fill="white"/></svg></div>`;
    const USER_AV  = `<div class="wgt-av-user"><svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></div>`;

    function appendMsgUI(role, text, time) {
        const container = document.getElementById('wgtMessages');
        const isUser = role === 'user';
        const displayTime = time || nowTime();

        const wrap = document.createElement('div');
        wrap.className = `wgt-msg ${role}`;
        wrap.innerHTML = `
            ${isUser ? USER_AV : AI_AV}
            <div>
                <div class="wgt-bubble">${isUser ? escHtml(text) : fmtMd(text)}</div>
                <div class="wgt-time">${displayTime}</div>
            </div>`;
        container.appendChild(wrap);
        container.scrollTop = container.scrollHeight;
    }

    // appendMsg giờ chỉ là alias của appendMsgUI (giữ tương thích)
    function appendMsg(role, text) { appendMsgUI(role, text, nowTime()); }

    function showTyping() {
        const container = document.getElementById('wgtMessages');
        const div = document.createElement('div');
        div.className = 'wgt-msg bot';
        div.id = 'wgtTyping';
        div.innerHTML = `
            ${AI_AV}
            <div><div class="wgt-bubble">
                <div class="wgt-typing"><span></span><span></span><span></span></div>
            </div></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function removeTyping() { document.getElementById('wgtTyping')?.remove(); }

    function hideWelcome()  { document.getElementById('wgtWelcome')?.remove(); }

    function makeWelcome() {
        const div = document.createElement('div');
        div.className = 'wgt-welcome';
        div.id = 'wgtWelcome';
        div.innerHTML = `
            <div class="wgt-welcome-orb">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L9.5 8.5H3L8 12.5L6 19.5L12 15.5L18 19.5L16 12.5L21 8.5H14.5L12 2Z" fill="white" opacity="0.95"/>
                    <circle cx="12" cy="12" r="2.5" fill="white"/>
                </svg>
            </div>
            <div class="wgt-welcome-title">Xin chào! Tôi là trợ lý AI</div>
            <div class="wgt-welcome-sub">Hãy hỏi tôi bất cứ điều gì về công ty Vinh Phú!</div>
            <div class="wgt-quick">
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Công ty thành lập năm nào?')">📅 Năm thành lập</button>
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Số nhân viên hiện tại?')">👥 Nhân viên</button>
                <button class="wgt-quick-btn" onclick="wgtSendQuick('Địa chỉ công ty ở đâu?')">📍 Địa chỉ</button>
            </div>`;
        return div;
    }

    function setBusy(v) {
        busy = v;
        document.getElementById('wgtSendBtn').disabled  = v;
        document.getElementById('wgtInput').disabled    = v;
    }

    function showBadge() {
        const b = document.getElementById('wgtBadge');
        b.textContent   = unread > 9 ? '9+' : unread;
        b.style.display = 'flex';
    }

    function showToast(msg, ok) {
        let t = document.getElementById('wgtToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'wgtToast';
            t.style.cssText = 'position:fixed;bottom:96px;left:50%;transform:translateX(-50%);z-index:99999;padding:10px 18px;border-radius:10px;color:#fff;font-size:13px;transition:opacity .3s;pointer-events:none;';
            document.body.appendChild(t);
        }
        t.textContent      = (ok ? '✅ ' : '❌ ') + msg;
        t.style.background = ok ? '#059669' : '#dc2626';
        t.style.opacity    = '1';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.style.opacity = '0', 3500);
    }

    function nowTime() {
        return new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }

    window.wgtKeyDown = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); wgtSend(); }
    };

    window.wgtAutoResize = function (el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 90) + 'px';
    };

    function escHtml(t) {
        return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    }

    function fmtMd(t) {
        return t
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
            .replace(/\*(.*?)\*/g,'<em>$1</em>')
            .replace(/`(.*?)`/g,'<code>$1</code>')
            .replace(/^#{1,3} (.*)$/gm,'<strong>$1</strong>')
            .replace(/^[*-] (.*)$/gm,'• $1')
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g,'<a href="$2" target="_blank">$1</a>')
            .replace(/\n/g,'<br>');
    }

    // ── Khởi động: restore từ cookie ────────────────────────────────
    document.addEventListener('DOMContentLoaded', restoreFromCookie);

})();
</script>

