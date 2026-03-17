<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
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
        /* Pinanatili ang lahat ng original styles mo */
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

        #landing-section {
            text-align: center;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 800px;
            padding: 20px;
            opacity: 1;
            transform: translateY(0);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn {
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
            border: none;
        }

        .btn-primary { background: white; color: black; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,255,255,0.2); }
        .btn-outline { background: rgba(255, 255, 255, 0.1); color: white; border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(5px); }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.2); }

        /* --- IMPROVED LOGIN SECTION TRANSITION --- */
        #login-section {
            display: none; 
            opacity: 0;
            transform: scale(0.95) translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* Pag nagpakita ang login card */
        #login-section.show-login {
            display: block !important;
            opacity: 1 !important;
            transform: scale(1) translateY(0) !important;
        }

        /* --- SHINE BORDER ANIMATION --- */
        .shine-container {
            position: relative;
            padding: 2px; 
            border-radius: 20px;
            overflow: hidden;
            display: inline-block;
        }

        .shine-border {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(
                from 0deg,
                transparent 20%,
                #ff6600 25%,
                #ffae00 50%,
                #ff6600 75%,
                transparent 80%
            );
            animation: rotate-shine 4s linear infinite;
            z-index: 0;
        }

        @keyframes rotate-shine {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-card { 
            position: relative;
            z-index: 2;
            background: rgba(15, 15, 15, 0.85); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 30px 40px; 
            border-radius: 19px; 
            width: 380px; 
            text-align: center; 
            box-sizing: border-box;
        }

        .logo { font-size: 2.2rem; font-weight: 800; color: #ff6600; margin-bottom: 5px; }
        .subtitle { color: #ccc; font-size: 0.9rem; margin-bottom: 20px; display: block; }
        
        .error-alert {
            background: rgba(255, 50, 50, 0.2);
            border: 1px solid #ff3232;
            color: #ff9999;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            margin-bottom: 15px;
            display: none;
        }

        .input-group { position: relative; margin-bottom: 15px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
        .input-group input { 
            width: 100%; padding: 12px 15px 12px 45px; 
            border: 1px solid rgba(255,255,255,0.2); 
            background: rgba(255,255,255,0.9);
            border-radius: 12px; outline: none; box-sizing: border-box; font-size: 0.95rem; color: #333;
        }
        .login-btn { 
            width: 100%; padding: 14px; background: #ff6600; color: white; border: none; 
            border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s;
            margin-top: 10px;
        }
        .login-btn:hover { background: #e65c00; transform: translateY(-2px); }

        .logoloop-container {
            position: fixed;
            bottom: 30px;
            width: 100%;
            z-index: 20;
            height: 100px;
        }
        .logoloop { position: relative; --logoloop-gap: 60px; --logoloop-logoHeight: 50px; --logoloop-fadeColorAuto: #000000; }
        .logoloop__track { display: flex; width: max-content; }
        .logoloop__list { display: flex; align-items: center; list-style: none; padding: 0; margin: 0; }
        .logoloop__item { flex: 0 0 auto; margin-right: var(--logoloop-gap); font-size: var(--logoloop-logoHeight); line-height: 1; color: rgba(255,255,255,0.4); transition: 0.3s; }
        .logoloop--fade::before, .logoloop--fade::after { content: ''; position: absolute; top: 0; bottom: 0; width: 15%; pointer-events: none; z-index: 10; }
        .logoloop--fade::before { left: 0; background: linear-gradient(to right, var(--logoloop-fadeColorAuto), transparent); }
        .logoloop--fade::after { right: 0; background: linear-gradient(to left, var(--logoloop-fadeColorAuto), transparent); }

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
                    <div class="logo"><i class="fa-solid fa-box-open"></i>OJTBox</div>
                    <span id="form-subtitle" class="subtitle">Asset Management System</span>
                    
                    <div id="error-box" class="error-alert" style="<?php echo isset($_GET['error']) ? 'display: block;' : ''; ?>">
                        <?php 
                            if(isset($_GET['error'])){
                                if($_GET['error'] == 'invalid_credentials') echo "Invalid username or password!";
                                else if($_GET['error'] == 'unauthorized') echo "Unauthorized Access!";
                                else echo "An error occurred. Please try again.";
                            }
                        ?>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div style="background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color: #a7ffbc; padding: 10px; border-radius: 8px; font-size: 0.8rem; margin-bottom: 15px;">
                            Action Successful!
                        </div>
                    <?php endif; ?>

                    <form id="auth-form" action="auth.php" method="POST">
                        <input type="hidden" name="action" id="form-action" value="login">
                        
                        <div class="input-group">
                            <input type="text" name="username" id="main-user" placeholder="Username or Email" required autocomplete="username">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        
                        <button type="submit" class="login-btn" id="submit-btn">Sign In</button>
                    </form>

                    <p style="font-size: 0.75rem; margin-top: 20px; color: rgba(255,255,255,0.4);">
                        <i class="fa-solid fa-shield-halved"></i> Authorized Personnel Only
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="logoloop-container" id="logo-loop-root"></div>

    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <script type="text/babel">
        const { useState, useEffect, useRef, memo } = React;
        const LogoLoop = memo(({ logos, speed = 80, fadeOut = true }) => {
            const trackRef = useRef(null);
            const offsetRef = useRef(0);
            const requestRef = useRef();

            useEffect(() => {
                let lastTime = 0;
                const animate = (time) => {
                    if (!lastTime) lastTime = time;
                    const delta = (time - lastTime) / 1000;
                    lastTime = time;

                    if (trackRef.current) {
                        const firstList = trackRef.current.querySelector('.logoloop__list');
                        if (firstList) {
                            const seqWidth = firstList.offsetWidth;
                            offsetRef.current += speed * delta;
                            if (offsetRef.current >= seqWidth) offsetRef.current = 0;
                            trackRef.current.style.transform = `translate3d(${-offsetRef.current}px, 0, 0)`;
                        }
                    }
                    requestRef.current = requestAnimationFrame(animate);
                };
                requestRef.current = requestAnimationFrame(animate);
                return () => cancelAnimationFrame(requestRef.current);
            }, [speed]);

            return (
                <div className={`logoloop ${fadeOut ? 'logoloop--fade' : ''}`}>
                    <div className="logoloop__track" ref={trackRef}>
                        {[...Array(3)].map((_, i) => (
                            <ul className="logoloop__list" key={i}>
                                {logos.map((logo, idx) => (
                                    <li className="logoloop__item" key={idx}>{logo.node}</li>
                                ))}
                            </ul>
                        ))}
                    </div>
                </div>
            );
        });

        const techLogos = [
            { node: <i className="fa-brands fa-react"></i> },
            { node: <i className="fa-brands fa-php"></i> },
            { node: <i className="fa-brands fa-js"></i> },
            { node: <i className="fa-brands fa-html5"></i> },
            { node: <i className="fa-brands fa-css3-alt"></i> },
            { node: <i className="fa-solid fa-database"></i> },
            { node: <i className="fa-brands fa-github"></i> }
        ];
        const root = ReactDOM.createRoot(document.getElementById('logo-loop-root'));
        root.render(<LogoLoop logos={techLogos} />);
    </script>

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
        function showLogin() {
            const landing = document.getElementById('landing-section');
            const login = document.getElementById('login-section');
            
            // Step 1: Fade out landing
            landing.classList.add('fade-out');
            
            // Step 2: After a brief delay, swap and trigger login animation
            setTimeout(() => {
                landing.style.display = 'none';
                login.style.display = 'block';
                
                // Force reflow para gumana ang transition
                login.offsetHeight; 
                
                login.classList.add('show-login');
            }, 400); // Binawasan ang delay para mas snappy pero smooth
        }
    </script>
</body>
</html>