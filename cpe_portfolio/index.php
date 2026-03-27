<?php 
require_once 'includes/db.php'; 
// Optimization: Start buffering with compression to prevent laggy rendering
if (!ob_start("ob_gzhandler")) ob_start(); 
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ken Daniel Llamanzares | Computer Engineer Portfolio</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        :root {
            --primary-glow: rgba(168, 85, 247, 0.4);
            --accent-glow: rgba(249, 115, 22, 0.4);
        }
        
        .animate-bounce-slow { animation: bounce-custom 4s infinite cubic-bezier(0.4, 0, 0.6, 1); }
        @keyframes bounce-custom { 0%, 100% { transform: translateY(-8%); } 50% { transform: translateY(0); } }
        
        /* Genius Fix: Hardware acceleration for smooth scrolling and hover */
        .project-card { 
            cursor: pointer; 
            pointer-events: auto !important; 
            transform: translateZ(0);
            backface-visibility: hidden;
        }

        .text-gradient {
            background: linear-gradient(to bottom, #fff 20%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Prevent Layout Shift */
        #content { transition: opacity 0.8s ease-in-out; }
    </style>
</head>

<body class="bg-[#0a0a0a] text-white overflow-x-hidden selection:bg-purple-500/30">

    <div class="fixed left-0 top-1/2 -translate-y-1/2 z-40 hidden lg:block">
        <div class="rotate-90 origin-left translate-x-10 flex gap-8 items-center opacity-30">
            <span class="font-mono text-[10px] tracking-[0.5em] uppercase whitespace-nowrap">SCROLL_FOR_SYSTEM_DATA</span>
            <div class="w-24 h-[1px] bg-white"></div>
            <span id="scroll-percentage" class="font-mono text-[10px]">00%</span>
        </div>
    </div>

    <div class="fixed top-32 right-10 hidden xl:flex flex-col gap-6 items-end opacity-40 hover:opacity-100 transition-opacity z-40">
        <div class="text-right">
            <div class="text-[10px] font-mono text-gray-500 uppercase">Latency</div>
            <div class="text-xs font-mono text-green-500 uppercase tracking-tighter">24ms / STABLE</div>
        </div>
        <div class="text-right">
            <div class="text-[10px] font-mono text-gray-500 uppercase">Kernel</div>
            <div class="text-xs font-mono text-purple-500 uppercase tracking-tighter">v6.0.2_ACTIVE</div>
        </div>
        <div class="w-12 h-12 rounded-full border border-white/10 flex items-center justify-center relative">
            <svg class="w-full h-full rotate-[-90deg] p-1">
                <circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="1" fill="transparent" class="text-white/5" />
                <circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="1" fill="transparent" class="text-purple-500" stroke-dasharray="113" stroke-dashoffset="30" />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center text-[8px] font-mono">CPU</div>
        </div>
    </div>

    <div id="custom-cursor" class="fixed w-6 h-6 border-2 border-orange-500 rounded-full pointer-events-none z-[999] mix-blend-difference hidden md:block"></div>

    <div id="loader" class="fixed inset-0 z-[100] bg-black flex items-center justify-center">
        <h1 class="text-orange-500 font-mono text-2xl tracking-tighter opacity-0" id="loader-text">
            INITIALIZING SYSTEM...
        </h1>
    </div>

    <main id="content" class="opacity-0">
        <nav class="p-6 flex justify-between items-center border-b border-purple-500/10 backdrop-blur-xl sticky top-0 z-50">
            <div class="text-xl font-bold tracking-tighter text-purple-500">KD_SYSTEMS.V6</div>
            <div class="space-x-8 hidden md:block text-[10px] uppercase tracking-[0.3em] text-gray-500">
                <a href="#projects" class="hover:text-purple-400 transition">Architectures</a>
                <a href="#about" class="hover:text-purple-400 transition">Profile</a>
                <a href="#terminal" class="hover:text-purple-400 transition">Kernel</a>
                <a href="mailto:kd.llamanzares@gmail.com" class="hover:text-purple-400 transition">Contact</a>
            </div>
        </nav>

        <section class="h-screen flex flex-col justify-center px-10 relative overflow-hidden">
            <div class="absolute top-[10%] right-[5%] w-64 h-64 bg-purple-600/20 blur-[100px] rounded-full animate-pulse"></div>
            <div class="absolute bottom-[20%] left-[10%] w-80 h-80 bg-blue-500/10 blur-[120px] rounded-full animate-bounce-slow"></div>
            <div class="absolute -bottom-20 left-1/2 -translate-x-1/2 w-[120%] h-[300px] bg-purple-900/10 blur-[150px] rounded-[100%]"></div>
            
            <div class="relative z-10">
                <h4 class="text-purple-500 font-mono mb-4 uppercase tracking-[0.5em] text-xs opacity-70">Computer Engineer // National University - Laguna</h4>
                <h1 class="text-7xl md:text-[10rem] font-black leading-[0.85] mb-8 text-gradient tracking-tighter uppercase">
                    KEN DANIEL<br>LLAMANZARES
                </h1>
                <p class="max-w-2xl text-gray-400 text-lg font-light leading-relaxed border-l border-white/10 pl-6">
                    Aspiring Ai Engineer developing <span class="text-white font-medium italic">immersive digital ecosystems</span> through IoT, Computer Vision, and high-fidelity embedded engineering.
                </p>
                <div class="mt-10 flex gap-6 font-mono text-[10px] tracking-widest text-gray-500">
                    <span class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-green-500"></div> AVAILABLE_FOR_PROJECTS</span>
                    <span class="opacity-50">LOCATION: BATANGAS, PH</span>
                </div>
            </div>
        </section>

        <section id="about" class="px-10 py-20 bg-white/[0.01]">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-20">
                <div>
                    <h2 class="text-3xl font-bold mb-10 border-l-4 border-purple-500 pl-4 tracking-tighter uppercase">Technical Expertise</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 border border-white/5 bg-white/[0.02] rounded-xl">
                            <h6 class="text-purple-500 font-mono text-[10px] mb-2">PROG_LANG</h6>
                            <p class="text-sm">Python Scripting, HTML, JavaScript, PHP, MATLAB, Arduino C++</p>
                        </div>
                        <div class="p-4 border border-white/5 bg-white/[0.02] rounded-xl">
                            <h6 class="text-purple-500 font-mono text-[10px] mb-2">AI_CV</h6>
                            <p class="text-sm">TensorFlow, Keras, OpenCV, Machine Learning</p>
                        </div>
                        <div class="p-4 border border-white/5 bg-white/[0.02] rounded-xl">
                            <h6 class="text-purple-500 font-mono text-[10px] mb-2">SYSTEMS</h6>
                            <p class="text-sm">IoT Development, Cybersecurity Fundamentals</p>
                        </div>
                        <div class="p-4 border border-white/5 bg-white/[0.02] rounded-xl">
                            <h6 class="text-purple-500 font-mono text-[10px] mb-2">HARDWARE</h6>
                            <p class="text-sm">Raspberry Pi, ESP32, Sensor Calibration,Computer Servicing</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl font-bold mb-10 border-l-4 border-purple-500 pl-4 tracking-tighter uppercase">Education & Certs</h2>
                    <div class="space-y-6">
                        <div class="relative pl-8 border-l border-white/10">
                            <div class="absolute left-[-5px] top-0 w-2 h-2 rounded-full bg-purple-500"></div>
                            <span class="text-xs font-mono text-gray-500">2022 - PRESENT</span>
                            <h4 class="text-lg font-bold">BS Computer Engineering</h4>
                            <p class="text-xs text-gray-400">National University - Laguna</p>
                        </div>
                        <div class="pt-4 grid grid-cols-2 gap-2 font-mono text-[9px] text-gray-500 uppercase">
                            <div class="p-2 border border-white/5">Lean Six Sigma Yellow Belt</div>
                            <div class="p-2 border border-white/5">ISC2 Candidate</div>
                            <div class="p-2 border border-white/5">Comp. System Servicing NCII</div>
                            <div class="p-2 border border-white/5">Introduction to Artificial Intelligence, Cisco Network Academy</div>
                            <div class="p-2 border border-white/5">Introduction to Artificial Cybersecurity, Cisco Network Academy</div>
                            <div class="p-2 border border-white/5">Basic Occupational Safety and Health Officer II</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-10 py-20 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white/[0.02] border border-white/5 p-8 rounded-[30px] backdrop-blur-sm hover:border-purple-500/30 transition-all group">
                <div class="text-5xl font-bold text-gradient mb-2 italic group-hover:scale-110 transition-transform">01.</div>
                <h5 class="font-mono text-xs tracking-widest text-purple-500 uppercase">IoT_Acquisition</h5>
                <p class="text-gray-500 text-xs mt-4">Sensor calibration and real-time data acquisition for agricultural and industrial monitoring.</p>
            </div>
            <div class="bg-white/[0.02] border border-white/5 p-8 rounded-[30px] backdrop-blur-sm md:translate-y-12 hover:border-purple-500/30 transition-all group">
                <div class="text-5xl font-bold text-gradient mb-2 italic group-hover:scale-110 transition-transform">02.</div>
                <h5 class="font-mono text-xs tracking-widest text-purple-500 uppercase">CV_Intelligence</h5>
                <p class="text-gray-500 text-xs mt-4">Integrating Computer Vision and Machine Learning to build autonomous surveillance systems.</p>
            </div>
            <div class="bg-white/[0.02] border border-white/5 p-8 rounded-[30px] backdrop-blur-sm hover:border-purple-500/30 transition-all group">
                <div class="text-5xl font-bold text-gradient mb-2 italic group-hover:scale-110 transition-transform">03.</div>
                <h5 class="font-mono text-xs tracking-widest text-purple-500 uppercase">Embedded_Logic</h5>
                <p class="text-gray-500 text-xs mt-4">Hardware-software integration using Arduino, ESP32, and Raspberry Pi platforms.</p>
            </div>
        </section>

        <section id="projects" class="py-20 px-10">
            <h2 class="text-3xl font-bold mb-10 border-l-4 border-orange-500 pl-4 tracking-tighter uppercase">Active Nodes (Academic Projects)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16">
                <?php
                try {
                    $stmt = $pdo->query("SELECT * FROM projects WHERE status = 'ACTIVE' ORDER BY created_at DESC");
                    while ($project = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="project-card group bg-white/[0.02] border border-white/[0.05] rounded-[40px] overflow-hidden transition-all duration-700 hover:border-purple-500/50 hover:bg-white/[0.04] relative" onclick="openModal(<?php echo $project['id']; ?>)">
                            <div class="p-4">
                                <div class="h-80 overflow-hidden rounded-[30px] bg-[#0d0d0d] relative shadow-2xl flex items-center justify-center">
                                    <?php if (!empty($project['image_url'])): ?>
                                        <img src="assets/projects/<?php echo htmlspecialchars($project['image_url']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="<?php echo htmlspecialchars($project['title']); ?>">
                                    <?php else: ?>
                                        <div class="text-zinc-800 font-mono text-[10px] tracking-widest animate-pulse">>> DYNAMIC_NODE_<?php echo $project['id']; ?>_ACTIVE</div>
                                    <?php endif; ?>
                                    <div class="absolute top-4 left-4 px-4 py-1 bg-black/40 backdrop-blur-md border border-white/10 rounded-full text-[10px] font-mono tracking-widest uppercase">
                                        <?php echo htmlspecialchars($project['category'] ?? 'System'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="p-8 pt-2">
                                <span class="text-[10px] text-orange-500 font-mono tracking-[0.2em] uppercase"><?php echo htmlspecialchars($project['tech_stack']); ?></span>
                                <h3 class="text-4xl font-bold mt-2 tracking-tighter group-hover:text-orange-500 transition-colors duration-500"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <p class="text-gray-500 mt-4 leading-relaxed text-sm font-light"><?php echo htmlspecialchars($project['description']); ?></p>
                                <div class="mt-8 flex items-center gap-4 text-[10px] font-mono tracking-[0.2em] text-gray-400 group-hover:text-white transition-all">
                                    <span class="w-10 h-10 rounded-full border border-white/10 flex items-center justify-center group-hover:bg-orange-500 group-hover:border-orange-500 transition-all">
                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 11L11 1M11 1H1M11 1V11" stroke="currentColor" stroke-width="2"/></svg>
                                    </span>
                                    EXECUTE_VIEW();
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                } catch (Exception $e) { /* Fail silently */ } ?>
            </div>
        </section>

        <div id="terminal" class="fixed bottom-6 right-6 z-[60] flex flex-col items-end">
            <div id="terminal-window" class="w-72 h-48 bg-black/95 border border-orange-500/30 rounded-lg mb-2 p-3 font-mono text-[10px] hidden overflow-y-auto shadow-2xl backdrop-blur-md">
                <div class="text-orange-500">>> SYSTEM_READY...</div>
                <div id="terminal-output"></div>
            </div>
            <input type="text" id="terminal-input" placeholder="TYPE 'HELP'..." class="bg-black/80 border border-white/10 p-2 rounded-md text-xs font-mono focus:border-orange-500 outline-none w-48 transition-all focus:w-72 text-orange-500">
        </div>

        <div id="project-modal" class="fixed inset-0 z-[200] bg-black/95 hidden items-center justify-center p-6 backdrop-blur-xl">
            <button onclick="closeModal()" class="absolute top-10 right-10 text-gray-500 hover:text-white font-mono tracking-widest text-xs z-[210] p-2">CLOSE_X</button>
            <div id="modal-content" class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 gap-10 opacity-0 translate-y-10"></div>
        </div>
    </main>

    <script src="js/script.js"></script>
    
    <script>
        window.openModal = async function(id) {
            const modal = document.getElementById('project-modal');
            const content = document.getElementById('modal-content');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            try {
                const response = await fetch(`get_project.php?id=${id}`);
                const data = await response.json();

                content.innerHTML = `
                    <div class="rounded-3xl overflow-hidden bg-white/5 border border-white/10">
                        <img src="assets/projects/${data.image_url || ''}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/800x600/000000/FFFFFF?text=SYSTEM_NODE_ACTIVE'">
                    </div>
                    <div class="flex flex-col justify-center">
                        <span class="text-orange-500 font-mono text-[10px] tracking-[0.3em] uppercase mb-2">${data.tech_stack}</span>
                        <h2 class="text-4xl md:text-5xl font-black mb-6 tracking-tighter uppercase italic text-white">${data.title}</h2>
                        <div class="h-[1px] w-20 bg-orange-500 mb-6"></div>
                        <p class="text-gray-400 leading-relaxed mb-8 font-light text-sm">${data.description}</p>
                        <div class="flex gap-4">
                             <button onclick="closeModal()" class="border border-white/10 text-white px-8 py-3 rounded-full font-mono text-[10px] tracking-widest hover:bg-white hover:text-black transition-all">
                                TERMINATE_VIEW();
                            </button>
                        </div>
                    </div>
                `;

                gsap.to(content, { opacity: 1, y: 0, duration: 0.6, ease: "power4.out" });
                
            } catch (err) {
                content.innerHTML = `<div class="text-red-500 font-mono text-xs">>> CRITICAL_ERROR: DATA_NODE_OFFLINE [${err.message}]</div>`;
                gsap.to(content, { opacity: 1, y: 0, duration: 0.5 });
            }
        };

        window.closeModal = function() {
            const modal = document.getElementById('project-modal');
            const content = document.getElementById('modal-content');
            
            gsap.to(content, { opacity: 0, y: 20, duration: 0.3, onComplete: () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }});
        };

        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = Math.round((winScroll / height) * 100);
            const display = scrolled < 10 ? `0${scrolled}%` : `${scrolled}%`;
            const scrollElem = document.getElementById('scroll-percentage');
            if(scrollElem) scrollElem.innerText = display;
        });

        document.addEventListener('mousemove', (e) => {
            const cursor = document.getElementById('custom-cursor');
            if(cursor) {
                gsap.to(cursor, {
                    x: e.clientX - 12,
                    y: e.clientY - 12,
                    duration: 0.1,
                    ease: "power2.out"
                });
            }
        });
    </script>
</body>
</html>