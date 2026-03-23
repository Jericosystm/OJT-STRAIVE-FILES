<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'euc_admin') {
        header("Location: index_admin.php");
    } else {
        header("Location: index_user.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Welcome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            background: #000; 
            margin: 0; 
            font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            overflow: hidden; 
            color: white;
        }

        #lines-canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .content-overlay {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100%;
        }

        /* --- INFINITE LOGO ONLY LOOP STYLES --- */
        .logo-loop-container {
            position: absolute;
            bottom: 40px;
            width: 100%;
            overflow: hidden;
            padding: 20px 0;
            z-index: 15;
            mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
        }

        .logo-track {
            display: flex;
            width: max-content;
            gap: 100px; 
            animation: scroll 30s linear infinite;
            align-items: center;
        }

        .logo-item {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            filter: grayscale(1) brightness(0.8);
        }

        .logo-item:hover {
            transform: scale(1.1);
            filter: grayscale(0) brightness(1.2);
        }

        .logo-img {
            height: 45px; 
            width: auto;
            max-width: 150px;
            object-fit: contain;
        }

        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* --- ORIGINAL STYLES --- */
        #landing-section {
            text-align: center;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 800px;
            padding: 20px;
            opacity: 1;
        }

        .hero-title { font-size: 3.5rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -1px; }
        .hero-subtitle { font-size: 1.2rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 40px; }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 40px; 
        }

        .btn { padding: 15px 35px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 1rem; border: none; }
        .btn-primary { background: white; color: black; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,255,255,0.2); }
        .btn-outline { background: rgba(255, 255, 255, 0.1); color: white; border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(5px); }

        #login-section {
            display: none; 
            opacity: 0;
            transform: scale(0.95) translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        #login-section.show-login { display: block !important; opacity: 1 !important; transform: scale(1) translateY(0) !important; }

        /* Shake Effect */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        .error-shake { animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) both; }

        .shine-container { position: relative; padding: 2px; border-radius: 20px; overflow: hidden; display: inline-block; }
        .shine-border {
            position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 0deg, transparent 20%, #ff6600 25%, #ffae00 50%, #ff6600 75%, transparent 80%);
            animation: rotate-shine 4s linear infinite; z-index: 0;
        }

        @keyframes rotate-shine { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .login-card { 
            position: relative; z-index: 2; background: rgba(15, 15, 15, 0.85); 
            backdrop-filter: blur(15px); padding: 30px 40px; border-radius: 19px; width: 380px; text-align: center; 
        }

        .logo { font-size: 2.2rem; font-weight: 800; color: #ff6600; margin-bottom: 5px; }

        .input-group { position: relative; margin-bottom: 25px; text-align: left; }
        .input-group i.main-icon { position: absolute; left: 15px; top: 15px; color: #ff6600; z-index: 5; }
        
        .input-group input { 
            width: 100%; padding: 15px 15px 15px 45px; border: 1px solid rgba(255,255,255,0.1); 
            background: rgba(255,255,255,0.05); border-radius: 12px; outline: none; box-sizing: border-box; 
            color: #fff; font-size: 1rem; transition: all 0.4s;
        }

        .input-group label {
            position: absolute; left: 45px; top: 15px; color: rgba(255,255,255,0.5);
            pointer-events: none; transition: all 0.3s ease;
        }

        .input-group input:focus ~ label,
        .input-group input:valid ~ label {
            top: -22px; left: 10px; font-size: 0.8rem; color: #ff6600; font-weight: 600;
        }

        .input-group input:focus {
            border-color: #ff6600;
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 15px rgba(255, 102, 0, 0.3);
        }

        .toggle-password {
            position: absolute; right: 15px; top: 15px; color: rgba(255,255,255,0.3);
            cursor: pointer; transition: 0.3s;
        }
        .toggle-password:hover { color: #ff6600; }

        /* CAPS LOCK WARNING STYLE */
        #caps-warning {
            position: absolute; right: 45px; top: 15px; color: #ffae00;
            font-size: 0.8rem; display: none; pointer-events: none;
        }

        .login-btn { 
            position: relative; overflow: hidden;
            width: 100%; padding: 14px; background: #ff6600; color: white; border: none; 
            border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; 
        }

        .login-btn::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.6s;
        }
        .login-btn:hover::before { left: 100%; }
        .login-btn:hover { background: #e65c00; box-shadow: 0 5px 15px rgba(255, 102, 0, 0.4); }
        .login-btn:active { transform: scale(0.96); }
        .login-btn:disabled { background: #884411; cursor: not-allowed; opacity: 0.7; }

        .fade-out { opacity: 0; transform: translateY(-30px); pointer-events: none; }

        /* SweetAlert Custom Dark Theme */
        .swal2-popup {
            background: rgba(20, 20, 20, 0.95) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 102, 0, 0.2) !important;
            color: #fff !important;
            border-radius: 20px !important;
        }

        /* LEARN MORE MODAL STYLES - UPDATED FOR WORLD CLASS LOOK */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
            display: flex; justify-content: center; align-items: center; z-index: 1000;
            transition: opacity 0.5s ease, visibility 0.5s ease;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .modal-card {
            background: rgba(18, 18, 18, 0.8); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 24px;
            padding: 40px; width: 90%; max-width: 650px; text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(20px);
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.8) translateY(30px);
            transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .modal-overlay.active { 
            opacity: 1; 
            visibility: visible;
            pointer-events: auto;
        }
        .modal-overlay.active .modal-card { 
            transform: scale(1) translateY(0); 
        }

        .dev-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; text-align: left;
        }
        .dev-item { 
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 12px 16px;
            border-radius: 12px;
            color: rgba(255,255,255,0.9); 
            font-size: 0.95rem; 
            display: flex; 
            align-items: center; 
            gap: 12px;
            transition: 0.3s;
        }
        .dev-item:hover {
            background: rgba(255, 102, 0, 0.1);
            border-color: rgba(255, 102, 0, 0.3);
            transform: translateY(-2px);
        }
        .dev-item i { color: #ff6600; font-size: 0.8rem; opacity: 0.8; }
        
        .close-modal-btn {
            margin-top: 35px; width: 100%; 
            background: #fff; color: #000; 
            padding: 14px; border-radius: 12px; 
            font-weight: 700; border: none; cursor: pointer;
            transition: 0.3s;
        }
        .close-modal-btn:hover { background: #eee; transform: scale(1.02); }

        .about-ojtbox {
            text-align: left;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 25px;
        }
        .about-ojtbox h3 { color: #ff6600; margin-bottom: 15px; font-size: 1.4rem; }
        .about-ojtbox p { color: rgba(255,255,255,0.8); line-height: 1.6; font-size: 0.95rem; margin-bottom: 15px; }
        .feature-list { list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .feature-list li { font-size: 0.85rem; color: rgba(255,255,255,0.6); display: flex; align-items: center; gap: 8px; }
        .feature-list li i { color: #ff6600; font-size: 0.7rem; }
    </style>
</head>
<body>
    <div id="lines-canvas-container"></div>

    <div id="learn-more-modal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div class="about-ojtbox">
                <div class="logo" style="font-size: 1.5rem; margin-bottom: 10px;"><i class="fa-solid fa-box-open"></i> OJTBox</div>
                <h3>Revolutionizing Asset Management</h3>
                <p>OJTBox was engineered to bridge the gap between complex organizational logistics and seamless digital tracking. It serves as a centralized hub for managing hardware, software, and personnel documentation with absolute precision.</p>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-check"></i> Real-time Inventory Tracking</li>
                    <li><i class="fa-solid fa-check"></i> Automated Documentation</li>
                    <li><i class="fa-solid fa-check"></i> Secure User Authentication</li>
                    <li><i class="fa-solid fa-check"></i> Fluid EUC Administration</li>
                </ul>
            </div>

            <h4 style="color: #fff; font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; text-align: left;">The Creative Team</h4>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; line-height: 1.5; text-align: left;">The architects dedicated to the innovation and maintenance of OJTBox.</p>
            <div class="dev-grid">
                <div class="dev-item"><i class="fa-solid fa-code"></i> Ken Daniel Llamanzares</div>
                <div class="dev-item"><i class="fa-solid fa-code"></i> Bentley Sabas III</div>
                <div class="dev-item"><i class="fa-solid fa-code"></i> Renyl Medina</div>
                <div class="dev-item"><i class="fa-solid fa-code"></i> Jerico Amata</div>
                <div class="dev-item"><i class="fa-solid fa-code"></i> Gavter Dausen</div>
                <div class="dev-item"><i class="fa-solid fa-code"></i> Jay-ar Bartolata</div>
                <div class="dev-item" style="grid-column: span 2;"><i class="fa-solid fa-code"></i> Ian Buisan</div>
            </div>
            <button class="close-modal-btn" onclick="toggleModal(false)">Dismiss</button>
        </div>
    </div>

    <div class="content-overlay">
        <div id="landing-section" style="<?php echo (isset($_GET['error'])) ? 'display: none;' : ''; ?>">
            <h1 class="hero-title">Precision in documentation is the hallmark of a Straiver.</h1>
            <p class="hero-subtitle">Manage your organizational assets with a modern and fluid experience.</p>
            <div class="button-group">
                <button class="btn btn-primary" onclick="showLogin()">Get Started</button>
                <button class="btn btn-outline" onclick="toggleModal(true)">Learn More</button>
            </div>
        </div>

        <div id="login-section" class="<?php echo (isset($_GET['error'])) ? 'show-login' : ''; ?>">
            <div class="shine-container <?php echo (isset($_GET['error'])) ? 'error-shake' : ''; ?>">
                <div class="shine-border"></div>
                <div class="login-card">
                    <div class="logo"><i class="fa-solid fa-box-open"></i> OJTBox</div>
                    <span class="subtitle" style="color: #ccc; font-size: 0.9rem; margin-bottom: 30px; display: block;">Asset Management System</span>
                    
                    <form id="auth-form" action="auth.php" method="POST" onsubmit="return handleLoginSubmit(this)">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="input-group">
                            <i class="fa-solid fa-user main-icon"></i>
                            <input type="text" name="username" id="username" required autocomplete="off">
                            <label for="username">Username</label>
                        </div>
                        
                        <div class="input-group">
                            <i class="fa-solid fa-lock main-icon"></i>
                            <input type="password" name="password" id="password" required onkeyup="checkCaps(event)">
                            <label for="password">Password</label>
                            <span id="caps-warning"><i class="fa-solid fa-arrow-up-z-a"></i> Caps ON</span>
                            <i class="fa-solid fa-eye toggle-password" id="eye-icon" onclick="togglePass()"></i>
                        </div>

                        <button type="submit" class="login-btn" id="submit-btn">
                            <span id="btn-text">Sign In</span>
                        </button>
                    </form>
                    <p style="font-size: 0.75rem; margin-top: 25px; color: rgba(255,255,255,0.4);">
                        <i class="fa-solid fa-shield-halved"></i> Authorized Personnel Only
                    </p>
                </div>
            </div>
        </div>

        <div class="logo-loop-container" id="logo-loop">
            <div class="logo-track" id="logo-track"></div>
        </div>
    </div>

    <script type="importmap"> { "imports": { "three": "https://unpkg.com/three@0.160.0/build/three.module.js" } } </script>
    <script type="module">
        import * as THREE from 'three';
        const vertexShader = `varying vec2 vUv; void main() { vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0); }`;
        const fragmentShader = `
            precision highp float;
            uniform float iTime; uniform vec3 iResolution; uniform vec2 iMouse; uniform float bendInfluence;
            mat2 rotate(float r) { return mat2(cos(r), sin(r), -sin(r), cos(r)); }
            float wave(vec2 uv, float offset, vec2 screenUv, vec2 mouseUv, bool shouldBend) {
                float time = iTime * 1.2;
                float amp = sin(offset + time * 0.2) * 0.4;
                float y = sin(uv.x * 0.8 + offset + time * 0.15) * amp;
                if (shouldBend) {
                    vec2 d = screenUv - mouseUv;
                    y += (mouseUv.y - screenUv.y) * exp(-dot(d, d) * 4.0) * -0.6 * bendInfluence;
                }
                return 0.02 / max(abs(uv.y - y) + 0.01, 1e-3);
            }
            void main() {
                vec2 baseUv = (2.0 * gl_FragCoord.xy - iResolution.xy) / iResolution.y;
                baseUv.y *= -1.0;
                vec2 mouseUv = (2.0 * iMouse - iResolution.xy) / iResolution.y;
                mouseUv.y *= -1.0;
                vec3 col = vec3(0.0);
                vec3 ORANGE = vec3(1.0, 0.4, 0.0); 
                for (int i = 0; i < 3; ++i) {
                    float fi = float(i);
                    float opacity = 0.8 - (fi * 0.25); 
                    vec2 ruv = baseUv * rotate(0.15 * log(length(baseUv) + 1.2) + fi * 0.1);
                    col += ORANGE * wave(ruv + vec2(fi * 0.5, 0.0), 3.0 + fi * 1.5, baseUv, mouseUv, true) * opacity;
                }
                gl_FragColor = vec4(col, 1.0);
            }
        `;
        const container = document.getElementById('lines-canvas-container');
        const scene = new THREE.Scene();
        const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setPixelRatio(window.devicePixelRatio);
        container.appendChild(renderer.domElement);
        const uniforms = { iTime: { value: 0 }, iResolution: { value: new THREE.Vector3() }, iMouse: { value: new THREE.Vector2(-1000, -1000) }, bendInfluence: { value: 0 } };
        const material = new THREE.ShaderMaterial({ uniforms, vertexShader, fragmentShader });
        scene.add(new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material));
        let targetInfluence = 0;
        window.addEventListener('pointermove', (e) => {
            const rect = container.getBoundingClientRect();
            uniforms.iMouse.value.set(e.clientX - rect.left, rect.height - (e.clientY - rect.top));
            targetInfluence = 1.0;
        });
        function animate(time) {
            uniforms.iTime.value = time * 0.001;
            uniforms.bendInfluence.value += (targetInfluence - uniforms.bendInfluence.value) * 0.05;
            const w = window.innerWidth; const h = window.innerHeight;
            if (renderer.domElement.width !== w || renderer.domElement.height !== h) {
                renderer.setSize(w, h, false); uniforms.iResolution.value.set(w, h, 1);
            }
            renderer.render(scene, camera);
            requestAnimationFrame(animate);
        }
        requestAnimationFrame(animate);
    </script>

    <script>
        // PARTNERS CONFIG
        const partners = [
            { name: "Straive", url: "https://www.straive.com/", img: "https://www.straive.com/wp-content/uploads/2024/12/Website-Logo-HD-1-172x48.png" },
            { name: "SG Analytics", url: "https://www.sganalytics.com/", img: "https://www.sganalytics.com/wp-content/uploads/2025/07/SGA_logo_horizontal_color.svg" },
            { name: "LearningMate", url: "https://learningmate.com/", img: "https://learningmate.com/wp-content/uploads/2024/02/logo-new-300x60.png" },
            { name: "Gramener", url: "https://gramener.com/", img: "https://gramener.com/wp-content/uploads/2023/10/Gramener-logo-01.png" },
            { name: "DoubleLine", url: "https://wearedoubleline.com/", img: "https://wearedoubleline.com/img/DL_interim_800px.png" }
        ];

        function initLogoLoop() {
            const track = document.getElementById('logo-track');
            const logoHTML = partners.map(p => `
                <a href="${p.url}" target="_blank" class="logo-item" title="${p.name}">
                    <img src="${p.img}" alt="${p.name}" class="logo-img">
                </a>
            `).join('');
            track.innerHTML = logoHTML + logoHTML; 
        }

        function showLogin() {
            const landing = document.getElementById('landing-section');
            const login = document.getElementById('login-section');
            landing.classList.add('fade-out');
            setTimeout(() => {
                landing.style.display = 'none';
                login.style.display = 'block';
                login.offsetHeight; 
                login.classList.add('show-login');
            }, 400);
        }

        function togglePass() {
            const passField = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passField.type === "password") {
                passField.type = "text";
                eyeIcon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                passField.type = "password";
                eyeIcon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }

        // --- NEW WORLD CLASS FEATURES ---

        // 1. Caps Lock Detection
        function checkCaps(event) {
            const capsWarning = document.getElementById('caps-warning');
            if (event.getModifierState("CapsLock")) {
                capsWarning.style.display = "block";
            } else {
                capsWarning.style.display = "none";
            }
        }

        // 2. Advanced Login Submission with SweetAlert
        function handleLoginSubmit(form) {
            const btn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            
            btn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Authenticating...';
            
            // Note: PHP error handling via SweetAlert
            return true; 
        }

        // 3. Error Alert (If PHP sends error param)
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('error')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'Invalid username or password. Please try again.',
                    confirmButtonColor: '#ff6600',
                    toast: true,
                    position: 'top-end',
                    timer: 4000,
                    showConfirmButton: false
                });
            }
        });

        // 4. Modal Controls
        function toggleModal(show) {
            const modal = document.getElementById('learn-more-modal');
            if (show) {
                modal.classList.add('active');
            } else {
                modal.classList.remove('active');
            }
        }

        function closeModal(event) {
            if(event.target.id === 'learn-more-modal') toggleModal(false);
        }

        window.onload = initLogoLoop;
    </script>
</body>
</html>