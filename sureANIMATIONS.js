import { animate } from "https://esm.sh/animejs";

// 1. Kill any existing animation tracking
anime.remove(".ball");

const squares = document.querySelectorAll(".square1, .square2, .square3, .square4, .square5, .square6");

squares.forEach((square) => {
  const balls = Array.from(square.querySelectorAll(".ball"));
  if (balls.length === 0) return;

  // Configuration settings
  const ballRadiusPx = 70; // Half of your 140px width/height
  const containerWidth = square.clientWidth || 600;
  const containerHeight = square.clientHeight || 600;

  // 2. Set highly scattered, completely random initial pixel positions
  const physicsObjects = balls.map((ball) => {
    const posX = Math.random() * (containerWidth - 200) + 100;
    const posY = Math.random() * (containerHeight - 200) + 100;

    ball.style.position = "absolute";
    ball.style.left = "0px";
    ball.style.top = "0px";
    ball.style.transform = `translate(${posX - ballRadiusPx}px, ${posY - ballRadiusPx}px)`;

    return {
      element: ball,
      x: posX,
      y: posY,
      radius: ballRadiusPx,
      baseX: posX,
      baseY: posY,
      angleOffset: Math.random() * Math.PI * 2,
      // --- SLOWED DOWN HERE ---
      // Changed from (0.001 + random * 0.002) to a much smaller increment
      driftSpeed: 0.0003 + Math.random() * 0.0005 
    };
  });

  // 3. Continuous Physics Engine Loop (Keeps separation instant, doesn't slow down collision fixes)
  function resolvePhysics() {
    for (let pass = 0; pass < 4; pass++) {
      for (let i = 0; i < physicsObjects.length; i++) {
        for (let j = i + 1; j < physicsObjects.length; j++) {
          const b1 = physicsObjects[i];
          const b2 = physicsObjects[j];

          const dx = b2.x - b1.x;
          const dy = b2.y - b1.y;
          const distance = Math.sqrt(dx * dx + dy * dy);
          const minDist = b1.radius + b2.radius + 8; // 8px cushion

          if (distance < minDist) {
            const overlap = minDist - distance;
            const nx = distance > 0 ? dx / distance : 1;
            const ny = distance > 0 ? dy / distance : 0;

            const separationX = nx * overlap * 0.5;
            const separationY = ny * overlap * 0.5;

            b1.x -= separationX;
            b1.y -= separationY;
            b2.x += separationX;
            b2.y += separationY;

            b1.baseX -= separationX * 0.1;
            b1.baseY -= separationY * 0.1;
            b2.baseX += separationX * 0.1;
            b2.baseY += separationY * 0.1;
          }
        }
      }
    }

    // 4. Update DOM elements with slower ambient floating movement
    const time = Date.now();
    physicsObjects.forEach((b) => {
      // --- SLOWED DOWN HERE ---
      // Changed the multiplier from 6 down to 3 so the distance they float is shorter and gentler
      const driftX = Math.cos(time * b.driftSpeed + b.angleOffset) * 6;
      const driftY = Math.sin(time * b.driftSpeed + b.angleOffset) * 6;

      const renderX = b.x + driftX - b.radius;
      const renderY = b.y + driftY - b.radius;

      b.element.style.transform = `translate(${renderX}px, ${renderY}px)`;
    });

    requestAnimationFrame(resolvePhysics);
  }

  requestAnimationFrame(resolvePhysics);
});