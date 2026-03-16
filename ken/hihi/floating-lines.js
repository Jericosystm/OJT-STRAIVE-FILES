import * as THREE from 'three';

const vertexShader = `... (Copy the exact text from your vertexShader code) ...`;
const fragmentShader = `... (Copy the exact text from your fragmentShader code) ...`;

class FloatingLines {
    constructor(container) {
        this.container = container;
        this.init();
    }

    init() {
        const scene = new THREE.Scene();
        const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
        const renderer = new THREE.WebGLRenderer({ antialias: true });
        
        renderer.setPixelRatio(window.devicePixelRatio);
        this.container.appendChild(renderer.domElement);

        const uniforms = {
            iTime: { value: 0 },
            iResolution: { value: new THREE.Vector3() },
            // Set your defaults here based on your React props
            animationSpeed: { value: 1.0 },
            enableTop: { value: true },
            enableMiddle: { value: true },
            enableBottom: { value: true },
            topLineCount: { value: 5 },
            middleLineCount: { value: 5 },
            bottomLineCount: { value: 5 },
            topLineDistance: { value: 0.05 },
            middleLineDistance: { value: 0.05 },
            bottomLineDistance: { value: 0.05 },
            topWavePosition: { value: new THREE.Vector3(10, 0.5, -0.4) },
            middleWavePosition: { value: new THREE.Vector3(5, 0, 0.2) },
            bottomWavePosition: { value: new THREE.Vector3(2, -0.7, 0.4) },
            iMouse: { value: new THREE.Vector2(0, 0) },
            interactive: { value: true },
            bendRadius: { value: 5.0 },
            bendStrength: { value: -0.5 },
            bendInfluence: { value: 0 },
            parallax: { value: true },
            parallaxOffset: { value: new THREE.Vector2(0, 0) },
            lineGradient: { value: [new THREE.Vector3(0.9, 0.3, 0.9), new THREE.Vector3(0.2, 0.3, 0.6)] },
            lineGradientCount: { value: 2 }
        };

        const material = new THREE.ShaderMaterial({ uniforms, vertexShader, fragmentShader });
        const mesh = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material);
        scene.add(mesh);

        const resize = () => {
            const w = this.container.clientWidth;
            const h = this.container.clientHeight;
            renderer.setSize(w, h);
            uniforms.iResolution.value.set(w, h, 1);
        };
        window.addEventListener('resize', resize);
        resize();

        const animate = (time) => {
            uniforms.iTime.value = time * 0.001;
            renderer.render(scene, camera);
            requestAnimationFrame(animate);
        };
        requestAnimationFrame(animate);
    }
}

// Initialize it
new FloatingLines(document.getElementById('lines-container'));