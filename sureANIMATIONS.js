import { animate } from "https://esm.sh/animejs";

// Fetch and loop through squares as before
const response = await fetch("./actions.json");
const data = await response.json();
const squares = document.querySelectorAll(".square1, .square2, .square3, .square4, .square5, .square6");

squares.forEach((square, index) => {
  // Main square movement

  const balls = square.querySelectorAll(".ball");
  const totalBalls = balls.length;
  const circleRadius = 150; // how far from the center the balls will float (adjust as needed)
  balls.forEach((ball, ballIndex) => {
    // 1. Calculate the base mathematical position
    const baseAngle = (ballIndex / balls.length) * 2 * Math.PI;

    // 2. Add a random offset (e.g., +/- 10 degrees)
    // 0.17 radians is roughly 10 degrees
    const randomOffset2 = (Math.random() - 0.5) * 0.3;

    const angle = baseAngle + randomOffset2;

    // 1. "Home" Position (On the circle)
    const startX = Math.cos(angle) * circleRadius;
    const startY = Math.sin(angle) * circleRadius;

    // 2. "Float" Position (Slightly further out)
    const floatDistance = 15;
    // Define your "jitter" range in degrees
    const jitterDegrees = 5;

    // Convert jitter to radians and apply randomly: (Math.random() * 2 - 1) gives a range of -1 to 1
    const randomOffset = (Math.random() * 2 - 1) * ((jitterDegrees * Math.PI) / 180);

    const finalAngle = angle + randomOffset;

    const endX = Math.cos(finalAngle) * (circleRadius + floatDistance);
    const endY = Math.sin(finalAngle) * (circleRadius + floatDistance);

    // 3. IMPORTANT: Set the initial position immediately
    // This prevents the "leap" from the center (0,0)
    anime.set(ball, {
      translateX: startX,
      translateY: startY,
    });

    // 4. The Infinite Loop
    anime({
      targets: ball,
      translateX: [startX, endX],
      translateY: [startY, endY],
      duration: 3000 + Math.random() * 2000, // Slower for a "floating" feel
      easing: "easeInOutQuad",
      direction: "alternate", // This is the "yoyo" back and forth
      loop: true,
      delay: Math.random() * 2000, // Stagger the start so they aren't in sync
    });
  });
});
