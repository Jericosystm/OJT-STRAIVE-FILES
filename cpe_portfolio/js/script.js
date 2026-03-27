window.addEventListener('load', () => {
    const tl = gsap.timeline();

    // 1. Animate Loader Text
    tl.to("#loader-text", { opacity: 1, duration: 1 })
      .to("#loader-text", { opacity: 0, duration: 0.5, delay: 1 });

    // 2. Slide Loader up
    tl.to("#loader", { 
        y: "-100%", 
        duration: 0.8, 
        ease: "power4.inOut" 
    });

    // 3. Reveal Content
    tl.to("#content", { 
        opacity: 1, 
        duration: 1 
    }, "-=0.4");

    // 4. Stagger animate headers
    tl.from("h1", { 
        y: 50, 
        opacity: 0, 
        duration: 1, 
        ease: "power3.out" 
    }, "-=0.5");

    // --- GSAP REVEAL FOR SECTIONS ---
    gsap.registerPlugin(ScrollTrigger);
    gsap.utils.toArray('section').forEach(section => {
        gsap.from(section, {
            scrollTrigger: {
                trigger: section,
                start: "top 80%",
            },
            y: 50,
            opacity: 0,
            duration: 1.2,
            ease: "power4.out"
        });
    });

    // --- SCROLL PERCENTAGE LOGIC ---
    window.addEventListener('scroll', () => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = Math.round((winScroll / height) * 100);
        const scrollElem = document.getElementById('scroll-percentage');
        if(scrollElem) scrollElem.innerText = scrolled.toString().padStart(2, '0') + '%';
    });

    // Cinematic Project Card Interaction
    document.querySelectorAll('.project-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, { 
                y: -10, 
                scale: 1.02, 
                borderColor: "rgba(168, 85, 247, 0.4)", 
                duration: 0.4, 
                ease: "power2.out" 
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, { 
                y: 0, 
                scale: 1, 
                borderColor: "rgba(255, 255, 255, 0.05)", 
                duration: 0.4, 
                ease: "power2.inOut" 
            });
        });
    });

    // Entrance animation for cards
    gsap.from(".project-card", {
        scrollTrigger: {
            trigger: "#projects",
            start: "top 80%",
        },
        y: 100,
        opacity: 0,
        duration: 1.2,
        stagger: 0.15,
        ease: "expo.out"
    });

    // --- CUSTOM CURSOR LOGIC ---
    const cursor = document.querySelector('#custom-cursor');
    if(cursor) {
        document.addEventListener('mousemove', (e) => {
            gsap.to(cursor, { x: e.clientX, y: e.clientY, duration: 0.1 });
        });

        document.querySelectorAll('a, button, .project-card').forEach(el => {
            el.addEventListener('mouseenter', () => gsap.to(cursor, { scale: 2, backgroundColor: "rgba(168, 85, 247, 0.2)" }));
            el.addEventListener('mouseleave', () => gsap.to(cursor, { scale: 1, backgroundColor: "transparent" }));
        });
    }

    // --- COMMAND TERMINAL LOGIC ---
    const termInput = document.querySelector('#terminal-input');
    const termOutput = document.querySelector('#terminal-output');
    const termWindow = document.querySelector('#terminal-window');
    const termContainer = document.querySelector('#terminal'); // Main wrapper

    if(termInput) {
        termInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const cmd = termInput.value.toLowerCase().trim();
                
                // Revised clear logic: Hide window but KEEP input visible
                if (cmd === 'clear') { 
                    termOutput.innerHTML = ""; 
                    termInput.value = ""; 
                    if(termWindow) termWindow.classList.add('hidden');
                    return; 
                }

                termWindow.classList.remove('hidden');
                let response = `>> UNKNOWN_COMMAND: ${cmd}`;
                
                if (cmd === 'help') response = ">> CMDS: 'about', 'clear', 'contact', 'status', 'date'hi'";
                if (cmd === 'about') response = ">> KEN DANIEL: COMPUTER ENGINEER // 2026_CORE";
                if (cmd === 'contact') response = ">> EMAIL: kd.llamanzares@gmail.com";
                if (cmd === 'status') response = ">> KERNEL: OPTIMIZED | UPTIME: 100% | LATENCY: 24ms";
                if (cmd === 'date') response = ">> " + new Date().toLocaleString();
                if (cmd === 'hi') response = ">> Hello, wassup?";

                termOutput.innerHTML += `<div class="mb-1 text-gray-400">${response}</div>`;
                termInput.value = "";
                termWindow.scrollTop = termWindow.scrollHeight;
            }
        });
    }

    // Floating Animation
    gsap.to(".project-card", {
        y: "random(-10, 10)",
        duration: "random(2, 4)",
        repeat: -1,
        yoyo: true,
        ease: "sine.inOut",
        stagger: { each: 0.2, from: "random" }
    });

    // Refined 3D Tilt
    document.querySelectorAll('.project-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const rotateX = (y - rect.height / 2) / 25;
            const rotateY = (rect.width / 2 - x) / 25;

            gsap.to(card, {
                rotateX: rotateX,
                rotateY: rotateY,
                duration: 0.8,
                ease: "power3.out",
                transformPerspective: 1200
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                rotateX: 0,
                rotateY: 0,
                duration: 1.2,
                ease: "elastic.out(1, 0.5)"
            });
        });
    });
});

/**
 * AJAX MODAL LOGIC
 */
window.openModal = async function(id) {
    const modal = document.querySelector('#project-modal');
    const content = document.querySelector('#modal-content');
    
    if(!modal || !content) return;

    modal.style.display = 'flex';
    modal.classList.remove('hidden'); 
    content.innerHTML = `<div class="text-orange-500 font-mono animate-pulse">>> ACCESSING_DATA_NODE...</div>`;
    
    gsap.set(content, { opacity: 0, y: 30 });

    try {
        const res = await fetch(`get_project.php?id=${id}`);
        if (!res.ok) throw new Error('OFFLINE');
        const data = await res.json();

        if(!data) throw new Error('VOID_DATA');

        const mediaHtml = data.image_url 
            ? `<img src="assets/projects/${data.image_url}" class="w-full h-full object-cover rounded-lg shadow-2xl">`
            : `<div class="text-zinc-800 font-mono text-xs">>> VISUAL_DATA_MISSING</div>`;

        content.innerHTML = `
            <div class="flex items-center justify-center">
                <div class="aspect-video w-full bg-zinc-900 rounded-[30px] flex items-center justify-center border border-white/5 overflow-hidden">
                    ${mediaHtml}
                </div>
            </div>
            <div class="flex flex-col justify-center">
                <span class="text-orange-500 font-mono text-[10px] tracking-[0.3em] uppercase mb-2">${data.tech_stack || 'HARDWARE_NODE'}</span>
                <h2 class="text-4xl md:text-5xl font-black mb-6 tracking-tighter uppercase italic text-white">${data.title}</h2>
                <div class="h-[1px] w-20 bg-purple-500 mb-6"></div>
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
}

window.closeModal = function() {
    const modal = document.querySelector('#project-modal');
    gsap.to('#modal-content', { 
        opacity: 0, 
        y: 20, 
        duration: 0.4, 
        ease: "power2.in",
        onComplete: () => {
            modal.style.display = 'none';
            modal.classList.add('hidden');
        }
    });
}