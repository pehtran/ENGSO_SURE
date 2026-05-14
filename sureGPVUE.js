const { createApp } = Vue;

createApp({
  data() {
    return {
      actions: [],
      bsOffcanvas: null,
      hideTimeout: null,
      modal_data: false, // To store the data for the modal when a ball is clicked
      all_tags: [], // To store the unique tags for filtering
      selectedTags: [], // To track which tags are currently selected for filtering
      searchQuery: "", // For the search bar input
      toggleTags: false,
      categoryColors: {
        "SOCIAL SUPPORT": "linear-gradient(145deg, #ffcc33, #edb92e)",
        COACHING: "linear-gradient(145deg, #70cbff, #4facfe)",
        GOVERNANCE: "linear-gradient(145deg, #a8edea, #fed6e3)",
        ACTIVITY: "linear-gradient(145deg, #84fab0, #8fd3f4)",
        "FINANCIAL STABILITY": "linear-gradient(145deg, #f6d365, #fda085)",
        FACILITIES: "linear-gradient(145deg, #e0c3fc, #8ec5fc)",
      },
    };
  },
  async created() {
    await this.loadActions();
  },
  mounted() {
    const offcanvasElement = document.getElementById("offcanvasScrolling");
    if (offcanvasElement) {
      this.bsOffcanvas = new bootstrap.Offcanvas(offcanvasElement);

      offcanvasElement.addEventListener("mouseenter", () => {
        clearTimeout(this.hideTimeout);
      });

      offcanvasElement.addEventListener("mouseleave", () => {
        this.startHideTimer();
      });
    }

    // Initial run
    this.initAnimations();
  },
  watch: {
    selectedTags: {
      handler() {
        // Wait for Vue to finish updating the HTML (v-html)
        this.$nextTick(() => {
          this.initAnimations();
        });
      },
      deep: true, // Essential for watching arrays
    },
    searchQuery() {
      this.$nextTick(() => {
        this.initAnimations();
      });
    },
  },
  methods: {
    getPillStyle(category) {
      const bg = this.categoryColors[category.toUpperCase()] || "#70cbff";
      return { background: bg };
    },
    // <--- Only one methods object!
    async loadActions() {
      try {
        const response = await fetch("./actions.json");
        this.actions = await response.json();
      } catch (error) {
        console.error("Error loading actions:", error);
      }
    },

    filtered_actions(category) {
      const query = this.searchQuery ? this.searchQuery.toLowerCase().trim() : "";

      // Define colors inside or as a global constant
      const categoryColors = {
        "SOCIAL SUPPORT": "linear-gradient(145deg, #fade8b, #edb92e)",
        COACHING: "linear-gradient(145deg, #70cbff, #157ad3)",
        GOVERNANCE: "linear-gradient(145deg, #a8edea, #fed6e3)",
        ACTIVITY: "linear-gradient(145deg, #84fab0, #8fd3f4)",
        "FINANCIAL STABILITY": "linear-gradient(145deg, #f6d365, #fda085)",
        FACILITIES: "linear-gradient(145deg, #e0c3fc, #8ec5fc)",
      };

      return this.actions
        .filter((action) => {
          const catKey = action.category.toUpperCase();
          const categoryMatch = catKey === category.toUpperCase();
          const tagMatch = this.selectedTags.length === 0 || action.tags.some((tag) => !this.selectedTags.includes(tag));
          const searchFields = [action.title, action.description, action.category, ...action.tags].join(" ").toLowerCase();
          const searchMatch = query === "" || searchFields.includes(query);

          return categoryMatch && tagMatch && searchMatch;
        })
        .map((action) => {
          // Get the specific gradient for this category
          const bgStyle = categoryColors[action.category.toUpperCase()] || "#ccc";

          return `<div class="ball" 
                   id="${action.id}" 
                   style="background: ${bgStyle}; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; 
                   text-align: center; padding: 5px; border-radius: 50%; min-width: 140px; min-height: 140px; cursor: pointer; box-shadow: 0 4px 6px rgba(97, 97, 97, 0.5);" 
                   data-bs-toggle="modal" 
                   data-bs-target="#exampleModal">
                ${action.title}
              </div>`;
        })
        .join("");
    },

    handleBallHover(event) {
      const ball = event.target; // Reference the specific ball
      if (ball.classList.contains("ball")) {
        clearTimeout(this.hideTimeout);

        const action = this.actions.find((a) => a.title.trim() === ball.innerText.trim());
        const body = document.querySelector(".offcanvas-body");

        if (action && body) {
          body.innerHTML = `
            <div class="mb-4">
                <span class="badge bg-primary mb-2">${action.category}</span>
                <h3 class="h4 fw-bold">${action.title}</h3>
                <p class="text-muted small">${action.tags.join(" ")}</p>
                <hr>
                <p class="lead" style="font-size: 1rem;">${action.description}</p>
            </div>
            <div class="mb-4 small">
                <h5 class="fw-bold">Practical Tips</h5>
                <ul class="list-group list-group-flush">
                    ${action.tips.map((tip) => `<li class="list-group-item ps-0 border-0">• ${tip}</li>`).join("")}
                </ul>
            </div>
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-success">Case Example</h5>
                    <p class="card-text small mb-2"><em>${action.case_example.context}</em></p>
                    <ul class="mb-0 small">
                        ${action.case_example.bullets.map((b) => `<li class="mb-1">${b}</li>`).join("")}
                    </ul>
                </div>
            </div>`;

          // 2. Start the timer
          setTimeout(() => {
            // 3. CHECK: Is the mouse still over THIS specific ball?
            const isStillHovered = ball.matches(":hover");

            if (isStillHovered && this.bsOffcanvas) {
              this.bsOffcanvas.show();
            }
          }, 3000); // 3-second delay
        }
      }
    },

    handleBallLeave(event) {
      if (event.target.classList.contains("ball")) {
        this.startHideTimer();
      }
    },

    startHideTimer() {
      clearTimeout(this.hideTimeout);
      this.hideTimeout = setTimeout(() => {
        if (this.bsOffcanvas) this.bsOffcanvas.hide();
      }, 1000);
    },
    handleBallClick(event) {
      // Check if the actual thing clicked was a ball
      if (event.target.classList.contains("ball")) {
        console.log("Ball clicked:", event.target.innerText);
        const ballIdbyTitle = event.target.innerText;

        this.modal_data_insert(ballIdbyTitle);
      }
    },
    modal_data_insert(id) {
      const action = this.actions.find((a) => a.title === id);
      console.log("Inserting data for:", action.id);
      // Logic to populate your modal fields...
      this.modal_data = action; // Store the entire action for use in the modal
    },
    return_all_tags() {
      const allTags = new Set();
      this.actions.forEach((action) => {
        action.tags.forEach((tag) => allTags.add(tag));
      });

      this.all_tags = Array.from(allTags); // Store unique tags in the component's data

      return Array.from(allTags);
    },
    initAnimations() {
      // 1. ALWAYS stop previous animations before starting new ones
      // This prevents multiple loops from fighting over the same elements
      anime.remove(".ball");
      //anime.remove(".square");

      const squares = document.querySelectorAll(".square");

      squares.forEach((square, index) => {
        const balls = square.querySelectorAll(".ball");
        const circleRadius = 150; // Increased slightly for better spacing

        balls.forEach((ball, ballIndex) => {
          // 1. Calculate the base mathematical position
          const baseAngle = (ballIndex / balls.length) * 2 * Math.PI;

          // 2. Add a random offset (e.g., +/- 10 degrees)
          // 0.17 radians is roughly 10 degrees
          const randomOffset2 = (Math.random() - 0.5) * 0.3;

          const angle = baseAngle + randomOffset2;
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

          // Snap to circle immediately
          anime.set(ball, { translateX: startX, translateY: startY });

          // Start the infinite loop
          anime({
            targets: ball,
            translateX: [startX, endX],
            translateY: [startY, endY],
            duration: 3000 + Math.random() * 2000,
            easing: "easeInOutQuad",
            direction: "alternate",
            loop: true,
            delay: Math.random() * 1000,
          });
        });
      });
    },
    ballHoverAnimation(ball) {
      anime({
        targets: ball,
        translateX: [startX, endX],
        translateY: [startY, endY],
        duration: 3000 + Math.random() * 2000,
        easing: "easeInOutQuad",
        direction: "alternate",
        loop: true,
        delay: Math.random() * 1000,
      });
    },
    toggleAllTags() {
      if (!this.toggleTags) {
        this.selectedTags = [...this.all_tags];
      } else {
        this.selectedTags = [];
      }
    },
  }, // <--- End of methods
}).mount("#SUREappGP");
