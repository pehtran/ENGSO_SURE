import { animate } from "https://esm.sh/animejs";

// Fetch and loop through squares as before
const response = await fetch("./actions.json");
const data = await response.json();
const squares = document.querySelectorAll(".square1, .square2, .square3, .square4, .square5, .square6");

squares.forEach((square, index) => {
  // Main square movement
  animate(square, {
    // Calculate center: (Half screen width) - (Half element width) - (Element's starting X)
    x: () => {
      const rect = square.getBoundingClientRect();
      const screenCenter = window.innerWidth / 2;
      const elementHalf = rect.width / 2;
      // We subtract the current left position to get the relative move distance
      return screenCenter - elementHalf - rect.left;
    },
    // If you also want it centered vertically:
    /*
    y: () => {
      const rect = square.getBoundingClientRect();
      const screenCenter = window.innerHeight / 2;
      const elementHalf = rect.height / 2;
      return screenCenter - elementHalf - rect.top;
    },
    */
    delay: index * 100,
    easing: "easeInOutSine",
    duration: 1000, // Adjust speed as needed
  });

  const balls = square.querySelectorAll(".ball");
  const totalBalls = balls.length;
  const circleRadius = 100;
  balls.forEach((ball, ballIndex) => {
    const angle = (ballIndex / totalBalls) * 2 * Math.PI;

    // 1. "Home" Position (On the circle)
    const startX = Math.cos(angle) * circleRadius;
    const startY = Math.sin(angle) * circleRadius;

    // 2. "Float" Position (Slightly further out)
    const floatDistance = 15;
    const endX = Math.cos(angle) * (circleRadius + floatDistance);
    const endY = Math.sin(angle) * (circleRadius + floatDistance);

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
