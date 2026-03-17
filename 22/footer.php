
//<?php include 'footer.php'; ?>

</main> <script src="backend.js"></script>

    <script>
        (function() {
            const canvas = document.createElement('canvas');
            canvas.id = 'click-spark-canvas';
            document.body.appendChild(canvas);

            // Styling
            Object.assign(canvas.style, {
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100vw',
                height: '100vh',
                pointerEvents: 'none',
                zIndex: '9999'
            });

            const ctx = canvas.getContext('2d');
            let sparks = [];
            const sparkColor = '#ff6600'; 
            const sparkSize = 20;
            const sparkRadius = 25;
            const sparkCount = 8;
            const duration = 400;

            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            window.addEventListener('resize', resize);
            resize();

            window.addEventListener('mousedown', (e) => {
                const now = performance.now();
                for (let i = 0; i < sparkCount; i++) {
                    sparks.push({
                        x: e.clientX,
                        y: e.clientY,
                        angle: (2 * Math.PI * i) / sparkCount,
                        startTime: now
                    });
                }
            });

            function draw(timestamp) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                sparks = sparks.filter(spark => {
                    const elapsed = timestamp - spark.startTime;
                    if (elapsed >= duration) return false;
                    const progress = elapsed / duration;
                    const eased = progress * (2 - progress);
                    const distance = eased * sparkRadius;
                    const lineLength = sparkSize * (1 - eased);
                    const x1 = spark.x + distance * Math.cos(spark.angle);
                    const y1 = spark.y + distance * Math.sin(spark.angle);
                    const x2 = spark.x + (distance + lineLength) * Math.cos(spark.angle);
                    const y2 = spark.y + (distance + lineLength) * Math.sin(spark.angle);
                    ctx.strokeStyle = sparkColor;
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.beginPath();
                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);
                    ctx.stroke();
                    return true;
                });
                requestAnimationFrame(draw);
            }
            requestAnimationFrame(draw);
        })();
    </script>
</body>
</html>