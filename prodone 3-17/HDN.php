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
    <style>
        body { 
            background: #000; 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
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
            animation: scroll 25s linear infinite;
            align-items: center;
        }

        .logo-item {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            filter: brightness(0) invert(1) opacity(0.7);
        }

        .logo-item:hover {
            transform: scale(1.1);
            filter: brightness(0) invert(1) opacity(1);
        }

        .logo-img {
            height: 45px; 
            width: auto;
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

        .btn { padding: 15px 35px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 1rem; border: none; }
        .btn-primary { background: white; color: black; margin-right: 15px; } /* Added margin to separate buttons */
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,255,255,0.2); }
        .btn-outline { background: rgba(255, 255, 255, 0.1); color: white; border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(5px); }

        #login-section {
            display: none; 
            opacity: 0;
            transform: scale(0.95) translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        #login-section.show-login { display: block !important; opacity: 1 !important; transform: scale(1) translateY(0) !important; }

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
        .input-group { position: relative; margin-bottom: 15px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
        .input-group input { 
            width: 100%; padding: 12px 15px 12px 45px; border: 1px solid rgba(255,255,255,0.2); 
            background: rgba(255,255,255,0.9); border-radius: 12px; outline: none; box-sizing: border-box; color: #333;
        }
        .login-btn { width: 100%; padding: 14px; background: #ff6600; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .login-btn:hover { background: #e65c00; transform: translateY(-2px); }
        .fade-out { opacity: 0; transform: translateY(-30px); pointer-events: none; }
    </style>
</head>
<body>
    <div id="lines-canvas-container"></div>

    <div class="content-overlay">
        <div id="landing-section" style="<?php echo (isset($_GET['error'])) ? 'display: none;' : ''; ?>">
            <h1 class="hero-title">Precision in documentation is the hallmark of a Straiver.</h1>
            <p class="hero-subtitle">Manage your organizational assets with a modern and fluid experience.</p>
            <div class="button-group">
                <button class="btn btn-primary" onclick="showLogin()">Get Started</button>
                <button class="btn btn-outline">Learn More</button>
            </div>
        </div>

        <div id="login-section" class="<?php echo (isset($_GET['error'])) ? 'show-login' : ''; ?>">
            <div class="shine-container">
                <div class="shine-border"></div>
                <div class="login-card">
                    <div class="logo"><i class="fa-solid fa-box-open"></i> OJTBox</div>
                    <span class="subtitle" style="color: #ccc; font-size: 0.9rem; margin-bottom: 20px; display: block;">Asset Management System</span>
                    
                    <form id="auth-form" action="auth.php" method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="input-group">
                            <input type="text" name="username" placeholder="Username" required>
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Password" required>
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <button type="submit" class="login-btn">Sign In</button>
                    </form>
                    <p style="font-size: 0.75rem; margin-top: 20px; color: rgba(255,255,255,0.4);">
                        <i class="fa-solid fa-shield-halved"></i> Authorized Personnel Only
                    </p>
                </div>
            </div>
        </div>

        <div class="logo-loop-container" id="logo-loop">
            <div class="logo-track" id="logo-track">
                </div>
        </div>
    </div>

    <script type="importmap">
        { "imports": { "three": "https://unpkg.com/three@0.160.0/build/three.module.js" } }
    </script>
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
        const partners = [
            { name: "Straive", url: "https://www.straive.com/", img: "https://www.straive.com/wp-content/uploads/2024/12/Website-Logo-HD-1-172x48.png" },
            { name: "SG Analytics", url: "https://www.sganalytics.com/", img: "https://www.sganalytics.com/wp-content/uploads/2025/07/SGA_logo_horizontal_color.svg" },
            { name: "LearningMate", url: "https://learningmate.com/", img: "https://learningmate.com/wp-content/uploads/2024/02/logo-new-300x60.png" },
            { name: "Gramener", url: "https://gramener.com/", img: "https://gramener.com/wp-content/uploads/2023/10/Gramener-logo-01.png" },
            { name: "DoubleLine", url: "https://wearedoubleline.com/", img: "https://wearedoubleline.com/img/DL_interim_800px.png" }
        ];

        function initLogoLoop() {
            const track = document.getElementById('logo-track');
            const content = partners.map(p => `
                <a href="${p.url}" target="_blank" class="logo-item" title="${p.name}">
                    <img src="${p.img}" alt="${p.name}" class="logo-img">
                </a>
            `).join('');
            track.innerHTML = content + content + content; 
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

        window.onload = initLogoLoop;
    </script>
</body>
</html>