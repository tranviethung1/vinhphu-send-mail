@extends('layouts.admin')

@section('title', 'Trợ lý AI')

@push('styles')
<style>
    .chat-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 140px);
        background: #f8fafc;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    }

    /* ── Header ─────────────────────────────────── */
    .chat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: #fff;
        flex-shrink: 0;
    }
    .chat-header-info { display: flex; align-items: center; gap: 12px; }
    .chat-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }
    .chat-header-title { font-size: 16px; font-weight: 700; }
    .chat-header-sub   { font-size: 12px; opacity: .8; margin-top: 2px; }
    .chat-status {
        display: flex; align-items: center; gap: 6px;
        font-size: 12px; opacity: .9;
    }
    .status-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #4ade80; animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: .4; }
    }

    .btn-refresh {
        display: flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: 8px; border: none;
        background: rgba(255,255,255,0.15); color: #fff;
        font-size: 13px; cursor: pointer; transition: background .2s;
        backdrop-filter: blur(4px);
    }
    .btn-refresh:hover { background: rgba(255,255,255,0.25); }
    .btn-refresh.loading svg { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Knowledge badge ─────────────────────────── */
    .knowledge-bar {
        padding: 8px 24px;
        background: #eff6ff;
        border-bottom: 1px solid #bfdbfe;
        font-size: 12px; color: #1d4ed8;
        display: flex; align-items: center; gap: 8px;
        flex-shrink: 0;
    }
    .knowledge-bar svg { flex-shrink: 0; }

    /* ── Messages area ────────────────────────────── */
    .chat-messages {
        flex: 1; overflow-y: auto; padding: 24px;
        display: flex; flex-direction: column; gap: 16px;
        scroll-behavior: smooth;
    }
    .chat-messages::-webkit-scrollbar { width: 6px; }
    .chat-messages::-webkit-scrollbar-track { background: transparent; }
    .chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

    /* ── Message bubble ──────────────────────────── */
    .message { display: flex; align-items: flex-end; gap: 10px; max-width: 80%; }
    .message.user  { align-self: flex-end; flex-direction: row-reverse; }
    .message.bot   { align-self: flex-start; }

    .msg-avatar {
        width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; flex-shrink: 0;
    }
    .message.user .msg-avatar { background: #3b82f6; color: #fff; }
    .message.bot  .msg-avatar { background: #e2e8f0; color: #475569; }

    .msg-content {
        padding: 12px 16px; border-radius: 16px;
        font-size: 14px; line-height: 1.6; word-break: break-word;
    }
    .message.user .msg-content {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff; border-bottom-right-radius: 4px;
    }
    .message.bot .msg-content {
        background: #fff; color: #1e293b;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
    .msg-time {
        font-size: 11px; color: #94a3b8; margin-top: 4px; text-align: right;
    }
    .message.bot .msg-time { text-align: left; }

    /* ── Typing indicator ────────────────────────── */
    .typing-indicator { display: flex; align-items: center; gap: 5px; padding: 4px 2px; }
    .typing-indicator span {
        width: 7px; height: 7px; background: #94a3b8; border-radius: 50%;
        animation: bounce 1.2s infinite;
    }
    .typing-indicator span:nth-child(2) { animation-delay: .2s; }
    .typing-indicator span:nth-child(3) { animation-delay: .4s; }
    @keyframes bounce {
        0%, 80%, 100% { transform: translateY(0); }
        40%           { transform: translateY(-8px); }
    }

    /* ── Welcome state ───────────────────────────── */
    .welcome-state {
        flex: 1; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 16px;
        color: #94a3b8; padding: 40px;
    }
    .welcome-icon {
        width: 72px; height: 72px; border-radius: 50%;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        display: flex; align-items: center; justify-content: center;
        font-size: 32px;
    }
    .welcome-title { font-size: 18px; font-weight: 600; color: #475569; }
    .welcome-sub   { font-size: 14px; text-align: center; max-width: 360px; line-height: 1.6; }
    .quick-questions {
        display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 8px;
    }
    .quick-btn {
        padding: 8px 14px; border-radius: 20px;
        border: 1.5px solid #bfdbfe; background: #fff;
        color: #1d4ed8; font-size: 13px; cursor: pointer;
        transition: all .2s;
    }
    .quick-btn:hover { background: #dbeafe; border-color: #3b82f6; }

    /* ── Input area ──────────────────────────────── */
    .chat-input-area {
        padding: 16px 24px; background: #fff;
        border-top: 1px solid #e2e8f0; flex-shrink: 0;
    }
    .chat-input-wrap {
        display: flex; align-items: flex-end; gap: 12px;
        background: #f1f5f9; border-radius: 14px;
        padding: 10px 14px;
        border: 2px solid transparent; transition: border-color .2s;
    }
    .chat-input-wrap:focus-within { border-color: #3b82f6; background: #fff; }
    #chatInput {
        flex: 1; border: none; outline: none; background: transparent;
        font-size: 14px; color: #1e293b; resize: none;
        max-height: 120px; min-height: 22px; line-height: 1.5;
        font-family: inherit;
    }
    #chatInput::placeholder { color: #94a3b8; }

    .btn-send {
        width: 38px; height: 38px; border-radius: 10px; border: none;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff; cursor: pointer; display: flex;
        align-items: center; justify-content: center;
        flex-shrink: 0; transition: opacity .2s, transform .1s;
    }
    .btn-send:hover   { opacity: .9; transform: scale(1.05); }
    .btn-send:active  { transform: scale(.95); }
    .btn-send:disabled { opacity: .5; cursor: not-allowed; transform: none; }

    .input-hint {
        font-size: 11px; color: #94a3b8; margin-top: 8px; text-align: center;
    }

    /* ── Toast ───────────────────────────────────── */
    .toast {
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        padding: 12px 20px; border-radius: 10px; color: #fff;
        font-size: 14px; display: none; align-items: center; gap: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15); animation: slideUp .3s ease;
    }
    .toast.success { background: #059669; display: flex; }
    .toast.error   { background: #dc2626; display: flex; }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Markdown-like formatting in bot messages ── */
    .msg-content strong { font-weight: 700; }
    .msg-content em     { font-style: italic; }
    .msg-content code   {
        background: #f1f5f9; padding: 2px 6px; border-radius: 4px;
        font-family: monospace; font-size: 13px;
    }
    .msg-content ul, .msg-content ol {
        padding-left: 18px; margin: 6px 0;
    }
    .msg-content li { margin: 3px 0; }
</style>
@endpush

@section('content')
<div class="chat-container">

    {{-- Header --}}
    <div class="chat-header">
        <div class="chat-header-info">
            <div class="chat-avatar">🤖</div>
            <div>
                <div class="chat-header-title">Trợ lý AI</div>
                <div class="chat-header-sub">Powered by Google Gemini</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
            <div class="chat-status">
                <div class="status-dot"></div>
                <span>Đang hoạt động</span>
            </div>
            <button class="btn-refresh" id="btnRefresh" onclick="refreshKnowledge()" title="Cập nhật dữ liệu từ Google Sheets">
                <svg id="refreshIcon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Cập nhật dữ liệu
            </button>
        </div>
    </div>

    {{-- Knowledge bar --}}
    <div class="knowledge-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span>Nguồn dữ liệu: <strong>Google Sheets</strong> — Thông tin công ty Vinh Phú</span>
        <a href="https://docs.google.com/spreadsheets/d/1KEhSLvbSfunKLGdl7wXunXi-ROWjqoLylyzGcvI58as/edit"
            target="_blank"
            style="margin-left:auto;color:#1d4ed8;text-decoration:none;font-size:12px;display:flex;align-items:center;gap:4px;">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Mở Sheet
        </a>
    </div>

    {{-- Messages --}}
    <div class="chat-messages" id="chatMessages">
        <div class="welcome-state" id="welcomeState">
            <div class="welcome-icon">🤖</div>
            <div class="welcome-title">Xin chào! Tôi là trợ lý AI của Vinh Phú</div>
            <div class="welcome-sub">
                Tôi có thể trả lời các câu hỏi về công ty dựa trên dữ liệu từ Google Sheets.
                Hãy đặt câu hỏi bên dưới!
            </div>
            <div class="quick-questions">
                <button class="quick-btn" onclick="sendQuick('Công ty Vinh Phú thành lập năm nào?')">📅 Năm thành lập</button>
                <button class="quick-btn" onclick="sendQuick('Công ty có bao nhiêu nhân viên?')">👥 Số nhân viên</button>
                <button class="quick-btn" onclick="sendQuick('Các phòng ban trong công ty là gì?')">🏢 Phòng ban</button>
                <button class="quick-btn" onclick="sendQuick('Giám đốc công ty là ai?')">👔 Giám đốc</button>
                <button class="quick-btn" onclick="sendQuick('Doanh thu hằng năm của công ty?')">💰 Doanh thu</button>
                <button class="quick-btn" onclick="sendQuick('Địa chỉ công ty ở đâu?')">📍 Địa chỉ</button>
            </div>
        </div>
    </div>

    {{-- Input --}}
    <div class="chat-input-area">
        <div class="chat-input-wrap">
            <textarea
                id="chatInput"
                placeholder="Nhập câu hỏi của bạn..."
                rows="1"
                onkeydown="handleKeyDown(event)"
                oninput="autoResize(this)"
            ></textarea>
            <button class="btn-send" id="btnSend" onclick="sendMessage()" title="Gửi (Enter)">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
        <div class="input-hint">Nhấn <kbd style="background:#f1f5f9;padding:1px 5px;border-radius:4px;font-size:11px;">Enter</kbd> để gửi · <kbd style="background:#f1f5f9;padding:1px 5px;border-radius:4px;font-size:11px;">Shift+Enter</kbd> để xuống dòng</div>
    </div>
</div>

{{-- Toast --}}
<div class="toast" id="toast"></div>
@endsection

@push('scripts')
<script>
    const CHAT_URL    = '{{ route("ai-chat.chat") }}';
    const REFRESH_URL = '{{ route("ai-chat.refresh") }}';
    const CSRF_TOKEN  = '{{ csrf_token() }}';

    let conversationHistory = [];
    let isLoading = false;

    // ── Send message ──────────────────────────────────────────────────────────

    function sendMessage() {
        const input = document.getElementById('chatInput');
        const text  = input.value.trim();
        if (!text || isLoading) return;

        hideWelcome();
        appendMessage('user', text);
        input.value = '';
        autoResize(input);

        conversationHistory.push({ role: 'user', text });

        showTyping();
        setLoading(true);

        fetch(CHAT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                message: text,
                history: conversationHistory.slice(-10, -1), // gửi 9 tin nhắn trước
            }),
        })
        .then(r => r.json())
        .then(data => {
            removeTyping();
            const answer = data.answer || 'Không có phản hồi.';
            appendMessage('bot', answer);
            conversationHistory.push({ role: 'model', text: answer });
        })
        .catch(err => {
            removeTyping();
            appendMessage('bot', '❌ Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.');
            console.error(err);
        })
        .finally(() => setLoading(false));
    }

    function sendQuick(text) {
        document.getElementById('chatInput').value = text;
        sendMessage();
    }

    // ── DOM helpers ───────────────────────────────────────────────────────────

    function appendMessage(role, text) {
        const container = document.getElementById('chatMessages');
        const isUser    = role === 'user';
        const time      = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

        const div = document.createElement('div');
        div.className = `message ${role}`;
        div.innerHTML = `
            <div class="msg-avatar">${isUser ? '👤' : '🤖'}</div>
            <div>
                <div class="msg-content">${isUser ? escHtml(text) : formatMarkdown(text)}</div>
                <div class="msg-time">${time}</div>
            </div>
        `;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function showTyping() {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'message bot';
        div.id = 'typingIndicator';
        div.innerHTML = `
            <div class="msg-avatar">🤖</div>
            <div>
                <div class="msg-content">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function removeTyping() {
        const el = document.getElementById('typingIndicator');
        if (el) el.remove();
    }

    function hideWelcome() {
        const el = document.getElementById('welcomeState');
        if (el) el.remove();
    }

    function setLoading(state) {
        isLoading = state;
        document.getElementById('btnSend').disabled  = state;
        document.getElementById('chatInput').disabled = state;
    }

    // ── Refresh knowledge ─────────────────────────────────────────────────────

    function refreshKnowledge() {
        if (isLoading) return;
        const btn  = document.getElementById('btnRefresh');
        const icon = document.getElementById('refreshIcon');
        btn.classList.add('loading');
        btn.disabled = true;

        fetch(REFRESH_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json' },
        })
        .then(r => r.json())
        .then(data => showToast(data.message || 'Đã cập nhật!', 'success'))
        .catch(() => showToast('Lỗi khi cập nhật dữ liệu!', 'error'))
        .finally(() => {
            btn.classList.remove('loading');
            btn.disabled = false;
        });
    }

    // ── Utils ─────────────────────────────────────────────────────────────────

    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function escHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');
    }

    function formatMarkdown(text) {
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/^### (.*$)/gm, '<strong>$1</strong>')
            .replace(/^## (.*$)/gm, '<strong>$1</strong>')
            .replace(/^# (.*$)/gm, '<strong>$1</strong>')
            .replace(/^\* (.*$)/gm, '• $1')
            .replace(/^- (.*$)/gm, '• $1')
            .replace(/\n/g, '<br>');
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.className = `toast ${type}`;
        toast.innerHTML = (type === 'success' ? '✅ ' : '❌ ') + message;
        setTimeout(() => { toast.className = 'toast'; }, 4000);
    }

    // Focus input on load
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('chatInput').focus();
    });
</script>
@endpush
