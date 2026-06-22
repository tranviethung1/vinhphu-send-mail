<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Tổng quan hệ thống'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* ===== SPACE BACKGROUND ===== */
    #space-canvas {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
    }

    /* Override main content background to be transparent */
    .main-content {
        background: transparent !important;
        position: relative;
        z-index: 1;
    }

    body {
        background: #020818 !important;
    }

    /* Header gets a dark glass look */
    .header {
        background: rgba(5, 10, 35, 0.75) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-bottom: 1px solid rgba(99, 179, 237, 0.15) !important;
        box-shadow: 0 1px 20px rgba(0, 0, 0, 0.4) !important;
    }

    .page-title {
        color: #e2e8ff !important;
    }

    .user-name {
        color: #c7d2fe !important;
    }

    /* Lift content-wrapper above space canvas only on this page */
    .content-wrapper {
        position: relative;
        z-index: 2;
    }

    /* ===== DASHBOARD GRID ===== */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* ===== GLASSMORPHISM STAT CARDS ===== */
    .stat-card {
        background: rgba(15, 23, 70, 0.55);
        border: 1px solid rgba(120, 160, 255, 0.2);
        border-radius: 1rem;
        padding: 1.75rem;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.07);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg,
            rgba(99, 102, 241, 0.12) 0%,
            rgba(139, 92, 246, 0.06) 50%,
            transparent 100%);
        pointer-events: none;
    }

    /* Nebula glow accent per card */
    .stat-card::after {
        content: '';
        position: absolute;
        bottom: -40px;
        right: -40px;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, transparent 70%);
        pointer-events: none;
    }

    .stat-card.green::after {
        background: radial-gradient(circle, rgba(52, 211, 153, 0.35) 0%, transparent 70%);
    }

    .stat-card.amber::after {
        background: radial-gradient(circle, rgba(251, 191, 36, 0.35) 0%, transparent 70%);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: rgba(165, 180, 252, 0.45);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4),
                    0 0 30px rgba(99, 102, 241, 0.15),
                    inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .stat-card.green:hover {
        border-color: rgba(52, 211, 153, 0.45);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4),
                    0 0 30px rgba(52, 211, 153, 0.15),
                    inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .stat-card.amber:hover {
        border-color: rgba(251, 191, 36, 0.45);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4),
                    0 0 30px rgba(251, 191, 36, 0.15),
                    inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .stat-label {
        font-size: 0.8rem;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        position: relative;
        z-index: 1;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #e2e8ff;
        line-height: 1;
        position: relative;
        z-index: 1;
        text-shadow: 0 0 20px rgba(165, 180, 252, 0.5);
    }

    .stat-card.green .stat-value {
        text-shadow: 0 0 20px rgba(52, 211, 153, 0.5);
    }

    .stat-card.amber .stat-value {
        text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
    }

    .stat-subtext {
        font-size: 0.85rem;
        color: #64748b;
        position: relative;
        z-index: 1;
        line-height: 1.4;
    }

    .stat-icon {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #ffffff;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.45),
                    0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        z-index: 1;
    }

    .stat-card.green .stat-icon {
        background: linear-gradient(135deg, #059669, #0d9488);
        box-shadow: 0 8px 20px rgba(5, 150, 105, 0.45),
                    0 0 0 1px rgba(255, 255, 255, 0.1) inset;
    }

    .stat-card.amber .stat-icon {
        background: linear-gradient(135deg, #d97706, #dc2626);
        box-shadow: 0 8px 20px rgba(217, 119, 6, 0.45),
                    0 0 0 1px rgba(255, 255, 255, 0.1) inset;
    }

    .stat-icon svg {
        width: 22px;
        height: 22px;
    }

    @media (max-width: 1024px) {
        .dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 768px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    
    <canvas id="space-canvas"></canvas>

    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-5.586a1 1 0 0 1-.707-.293L9.293 3.293A1 1 0 0 0 8.586 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/>
                </svg>
            </div>
            <div class="stat-label">File lương</div>
            <div class="stat-value"><?php echo e(number_format($stats['salary_files_count'])); ?></div>
            <div class="stat-subtext">Tổng số file lương đã được upload lên hệ thống</div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10l9-7 9 7v8a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V12H5v6a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-8z"/>
                </svg>
            </div>
            <div class="stat-label">Cơ sở</div>
            <div class="stat-value"><?php echo e(number_format($stats['facilities_count'])); ?></div>
            <div class="stat-subtext">Số lượng cơ sở đang được quản lý trong hệ thống</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                </svg>
            </div>
            <div class="stat-label">Danh sách mail</div>
            <div class="stat-value"><?php echo e(number_format($stats['mail_lists_count'])); ?></div>
            <div class="stat-subtext">Tổng số danh sách mail phục vụ gửi bảng lương</div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const canvas = document.getElementById('space-canvas');
    const ctx    = canvas.getContext('2d');

    let W, H, stars = [], shootingStars = [], nebulas = [], planets = [];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    /* ---- Stars ---- */
    function initStars(count = 320) {
        stars = [];
        for (let i = 0; i < count; i++) {
            stars.push({
                x:      Math.random() * W,
                y:      Math.random() * H,
                r:      Math.random() * 1.6 + 0.2,
                alpha:  Math.random(),
                dAlpha: (Math.random() * 0.006 + 0.002) * (Math.random() < 0.5 ? 1 : -1),
                color:  ['#ffffff','#c7d2fe','#bfdbfe','#ddd6fe'][Math.floor(Math.random()*4)],
            });
        }
    }

    /* ---- Nebula blobs ---- */
    function initNebulas() {
        nebulas = [
            { x: W * 0.15, y: H * 0.25, r: 260, color: 'rgba(109,40,217,0.07)' },
            { x: W * 0.75, y: H * 0.55, r: 320, color: 'rgba(37,99,235,0.06)'  },
            { x: W * 0.50, y: H * 0.80, r: 200, color: 'rgba(192,38,211,0.05)' },
            { x: W * 0.85, y: H * 0.15, r: 180, color: 'rgba(14,165,233,0.05)' },
        ];
    }

    /* ---- Shooting stars ---- */
    function spawnShootingStar() {
        const angle = (Math.random() * 30 + 15) * Math.PI / 180;
        shootingStars.push({
            x:     Math.random() * W * 0.8,
            y:     Math.random() * H * 0.4,
            len:   Math.random() * 120 + 80,
            speed: Math.random() * 10 + 8,
            angle, alpha: 1,
        });
    }

    /* ============================================================
       REALISTIC PLANETS
       ============================================================ */

    /**
     * Build an off-screen texture canvas with horizontal cloud bands.
     * Width = radius*4 so we always have at least one full wrap.
     */
    function buildTexture(radius, bands, baseColor) {
        const tw = radius * 4, th = radius * 2;
        const tc = document.createElement('canvas');
        tc.width = tw; tc.height = th;
        const tx = tc.getContext('2d');

        tx.fillStyle = baseColor;
        tx.fillRect(0, 0, tw, th);

        for (const b of bands) {
            tx.save();
            tx.beginPath();
            const by = th * b.y, bh = th * b.h;
            const wv = b.waves || 3, wA = (b.wAmp || 0.02) * th, ph = b.phase || 0;
            tx.moveTo(0, by + Math.sin(ph) * wA);
            for (let x = 0; x <= tw; x += 2)
                tx.lineTo(x, by + Math.sin(x / tw * Math.PI * wv + ph) * wA);
            for (let x = tw; x >= 0; x -= 2)
                tx.lineTo(x, by + bh + Math.sin(x / tw * Math.PI * wv + ph + 1.1) * wA);
            tx.closePath();
            tx.globalAlpha = b.alpha ?? 1;
            tx.fillStyle   = b.color;
            tx.fill();
            tx.globalAlpha = 1;
            tx.restore();
        }

        /* fine-grain noise */
        const n = Math.floor(tw * th / 280);
        for (let i = 0; i < n; i++) {
            tx.globalAlpha = Math.random() * 0.11;
            tx.fillStyle   = Math.random() < 0.5 ? '#fff' : '#000';
            tx.beginPath();
            tx.arc(Math.random() * tw, Math.random() * th, Math.random() * 1.8 + 0.4, 0, Math.PI * 2);
            tx.fill();
        }
        tx.globalAlpha = 1;
        return tc;
    }

    /* Draw one arc of a ring system (back = π→2π, front = 0→π) */
    function drawRingArc(cx, cy, r, rings, a0, a1) {
        const tilt = 0.27;
        ctx.save();
        for (const ring of rings) {
            const oR = r * ring.outerR, iR = r * ring.innerR;
            for (let a = oR; a >= iR; a -= 1.4) {
                const frac = (oR - a) / (oR - iR);
                const al   = ring.alphaFn(frac);
                if (al < 0.01) continue;
                ctx.globalAlpha = al * 0.72;
                ctx.strokeStyle = ring.color;
                ctx.lineWidth   = 1.4;
                ctx.beginPath();
                ctx.ellipse(cx, cy, a, a * tilt, 0, a0, a1);
                ctx.stroke();
            }
        }
        ctx.globalAlpha = 1;
        ctx.restore();
    }

    function drawPlanet(p, ts) {
        const r  = p.radius;
        const cx = p.cx;
        const cy = p.cy + (p.floatAmp
            ? Math.sin(ts * p.floatSpeed + p.floatPhase) * p.floatAmp
            : 0);

        p.rotOffset = (p.rotOffset + p.rotSpeed) % 1;

        /* rings – back half */
        if (p.rings) drawRingArc(cx, cy, r, p.rings, Math.PI, Math.PI * 2);

        /* === sphere === */
        ctx.save();
        ctx.beginPath();
        ctx.arc(cx, cy, r, 0, Math.PI * 2);
        ctx.clip();

        /* scrolling texture */
        const tw = p.texture.width;
        const xOff = cx - r - (p.rotOffset * tw * 2) % tw;
        for (let dx = -tw; dx <= tw * 2; dx += tw)
            ctx.drawImage(p.texture, xOff + dx, cy - r, tw, r * 2);

        /* limb darkening */
        const lim = ctx.createRadialGradient(cx, cy, r * 0.42, cx, cy, r);
        lim.addColorStop(0,   'rgba(0,0,0,0)');
        lim.addColorStop(0.58,'rgba(0,0,0,0.06)');
        lim.addColorStop(1,   'rgba(0,0,0,0.82)');
        ctx.fillStyle = lim;
        ctx.fillRect(cx - r, cy - r, r * 2, r * 2);

        /* shadow / night side (light from upper-left → shadow at lower-right) */
        const shx = cx + r * 0.42, shy = cy + r * 0.18;
        const sh  = ctx.createRadialGradient(shx, shy, 0, shx, shy, r * 1.55);
        sh.addColorStop(0,    'rgba(0,0,6,0)');
        sh.addColorStop(0.32, 'rgba(0,0,6,0.12)');
        sh.addColorStop(1,    'rgba(0,0,6,0.60)');
        ctx.fillStyle = sh;
        ctx.fillRect(cx - r, cy - r, r * 2, r * 2);

        /* specular highlight */
        const hx = cx - r * 0.30, hy = cy - r * 0.30;
        const sp = ctx.createRadialGradient(hx, hy, 0, hx, hy, r * 0.68);
        sp.addColorStop(0,   'rgba(255,255,255,0.20)');
        sp.addColorStop(0.38,'rgba(255,255,255,0.05)');
        sp.addColorStop(1,   'rgba(255,255,255,0)');
        ctx.fillStyle = sp;
        ctx.fillRect(cx - r, cy - r, r * 2, r * 2);

        ctx.restore();

        /* atmospheric glow */
        const ag = ctx.createRadialGradient(cx, cy, r * 0.86, cx, cy, r * 1.30);
        ag.addColorStop(0, p.atmoColor);
        ag.addColorStop(1, 'transparent');
        ctx.fillStyle = ag;
        ctx.beginPath();
        ctx.arc(cx, cy, r * 1.30, 0, Math.PI * 2);
        ctx.fill();

        /* rings – front half */
        if (p.rings) drawRingArc(cx, cy, r, p.rings, 0, Math.PI);
    }

    function initPlanets() {
        planets = [];
        const base = Math.min(W, H);

        /* --- Planet 1: Jupiter-like gas giant, bottom-right corner --- */
        const r1 = base * 0.175;
        planets.push({
            cx: W - r1 * 0.25,
            cy: H + r1 * 0.08,
            radius: r1,
            texture: buildTexture(r1, [
                { y:0.00, h:0.07, color:'#d9be8a', alpha:0.90, waves:2, wAmp:0.012, phase:0.5  },
                { y:0.07, h:0.06, color:'#7a4a28', alpha:0.85, waves:4, wAmp:0.022, phase:1.2  },
                { y:0.13, h:0.09, color:'#cfa46a', alpha:0.88, waves:3, wAmp:0.018, phase:0.3  },
                { y:0.22, h:0.05, color:'#5e3018', alpha:0.82, waves:5, wAmp:0.026, phase:2.1  },
                { y:0.27, h:0.11, color:'#e0c88a', alpha:0.92, waves:2, wAmp:0.013, phase:0.8  },
                { y:0.38, h:0.06, color:'#6b3a1e', alpha:0.78, waves:4, wAmp:0.023, phase:1.7  },
                { y:0.44, h:0.10, color:'#c89858', alpha:0.88, waves:3, wAmp:0.017, phase:0.15 },
                { y:0.54, h:0.05, color:'#8c5030', alpha:0.80, waves:4, wAmp:0.021, phase:1.45 },
                { y:0.59, h:0.12, color:'#ddc07a', alpha:0.90, waves:2, wAmp:0.014, phase:0.6  },
                { y:0.71, h:0.06, color:'#6a3a18', alpha:0.80, waves:5, wAmp:0.025, phase:2.5  },
                { y:0.77, h:0.12, color:'#be9055', alpha:0.86, waves:3, wAmp:0.018, phase:0.9  },
                { y:0.89, h:0.11, color:'#d8c082', alpha:0.90, waves:2, wAmp:0.012, phase:0.4  },
            ], '#c8a060'),
            rotOffset: 0,
            rotSpeed:  0.00010,
            floatAmp:  0,
            floatSpeed:0,
            floatPhase:0,
            atmoColor: 'rgba(215,165,80,0.13)',
            rings: [{
                innerR:  1.20,
                outerR:  1.72,
                color:   'rgba(205,175,115,1)',
                alphaFn: t => {
                    if (t < 0.04) return t / 0.04 * 0.55;
                    if (t < 0.18) return 0.55 - (t - 0.04) / 0.14 * 0.18;
                    if (t < 0.28) return 0.37;
                    if (t < 0.38) return 0.37 + (t - 0.28) / 0.10 * 0.28;
                    if (t < 0.50) return 0.65;
                    if (t < 0.58) return 0.65 - (t - 0.50) / 0.08 * 0.38;
                    if (t < 0.65) return 0.27;
                    if (t < 0.78) return 0.27 - (t - 0.65) / 0.13 * 0.22;
                    return Math.max(0, 0.05 - (t - 0.78) / 0.22 * 0.05);
                },
            }],
        });

        /* --- Planet 2: Neptune-like ice giant, upper-right, floating --- */
        const r2 = base * 0.092;
        planets.push({
            cx: W * 0.875,
            cy: H * 0.20,
            radius: r2,
            texture: buildTexture(r2, [
                { y:0.00, h:0.14, color:'#2870c0', alpha:0.72, waves:2, wAmp:0.030, phase:1.0  },
                { y:0.10, h:0.09, color:'#0d3a78', alpha:0.62, waves:3, wAmp:0.040, phase:2.3  },
                { y:0.17, h:0.18, color:'#3880d0', alpha:0.55, waves:2, wAmp:0.026, phase:0.7  },
                { y:0.33, h:0.08, color:'#50a0e0', alpha:0.58, waves:4, wAmp:0.036, phase:1.8  },
                { y:0.39, h:0.22, color:'#1860b0', alpha:0.62, waves:2, wAmp:0.022, phase:0.2  },
                { y:0.59, h:0.11, color:'#62b8f0', alpha:0.48, waves:3, wAmp:0.032, phase:1.5  },
                { y:0.68, h:0.16, color:'#104888', alpha:0.68, waves:2, wAmp:0.025, phase:2.0  },
                { y:0.82, h:0.18, color:'#2878c8', alpha:0.62, waves:3, wAmp:0.030, phase:0.5  },
            ], '#1848a0'),
            rotOffset: 0.35,
            rotSpeed:  0.00016,
            floatAmp:  20,
            floatSpeed:0.00048,
            floatPhase:1.2,
            atmoColor: 'rgba(55,145,230,0.15)',
            rings: null,
        });
    }

    /* ============================================================ */

    function drawNebulas() {
        nebulas.forEach(n => {
            const g = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, n.r);
            g.addColorStop(0, n.color);
            g.addColorStop(1, 'transparent');
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
            ctx.fill();
        });
    }

    function drawStars() {
        stars.forEach(s => {
            s.alpha += s.dAlpha;
            if (s.alpha > 1) { s.alpha = 1; s.dAlpha *= -1; }
            if (s.alpha < 0) { s.alpha = 0; s.dAlpha *= -1; }
            ctx.globalAlpha = s.alpha;
            ctx.fillStyle   = s.color;
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.globalAlpha = 1;
    }

    function drawShootingStars() {
        for (let i = shootingStars.length - 1; i >= 0; i--) {
            const s = shootingStars[i];
            s.x += Math.cos(s.angle) * s.speed;
            s.y += Math.sin(s.angle) * s.speed;
            s.alpha -= 0.018;
            const x2 = s.x - Math.cos(s.angle) * s.len;
            const y2 = s.y - Math.sin(s.angle) * s.len;
            const g  = ctx.createLinearGradient(x2, y2, s.x, s.y);
            g.addColorStop(0, 'rgba(255,255,255,0)');
            g.addColorStop(1, `rgba(255,255,255,${s.alpha})`);
            ctx.strokeStyle = g;
            ctx.lineWidth   = 2;
            ctx.beginPath();
            ctx.moveTo(x2, y2);
            ctx.lineTo(s.x, s.y);
            ctx.stroke();
            if (s.alpha <= 0) shootingStars.splice(i, 1);
        }
    }

    let lastShoot = 0;
    function loop(ts) {
        ctx.clearRect(0, 0, W, H);

        const bg = ctx.createLinearGradient(0, 0, W * 0.6, H);
        bg.addColorStop(0,   '#020818');
        bg.addColorStop(0.4, '#040d2e');
        bg.addColorStop(0.8, '#06092a');
        bg.addColorStop(1,   '#020818');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, W, H);

        drawNebulas();
        drawStars();
        planets.forEach(p => drawPlanet(p, ts * 0.001));
        drawShootingStars();

        if (ts - lastShoot > 2800) { spawnShootingStar(); lastShoot = ts; }
        requestAnimationFrame(loop);
    }

    function init() {
        resize();
        initStars();
        initNebulas();
        initPlanets();
        requestAnimationFrame(loop);
    }

    init();
    window.addEventListener('resize', init);
})();
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/dashboard/index.blade.php ENDPATH**/ ?>