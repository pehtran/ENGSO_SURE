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
  },
  methods: {
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

      return this.actions
        .filter((action) => {
          // 1. Category Match
          const categoryMatch = action.category.toUpperCase() === category.toUpperCase();

          // 2. Exclusion Tag Match (Show only if NO selected tags are present in action)
          const tagMatch = this.selectedTags.length === 0 || action.tags.every((tag) => !this.selectedTags.includes(tag));

          // 3. Global Search Match
          // We check title, description, and tags
          const searchFields = [action.title, action.description, action.category, ...action.tags].join(" ").toLowerCase();

          const searchMatch = query === "" || searchFields.includes(query);

          return categoryMatch && tagMatch && searchMatch;
        })
        .map((action) => {
          return `<div class="ball" 
                   id="${action.id}" 
                   data-bs-toggle="modal" 
                   data-bs-target="#exampleModal">
                ${action.title}
              </div>`;
        })
        .join("");
    },

    handleBallHover(event) {
      if (event.target.classList.contains("ball")) {
        clearTimeout(this.hideTimeout);

        // Added .trim() here just in case of extra spaces in your HTML
        const action = this.actions.find((a) => a.title.trim() === event.target.innerText.trim());
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

          if (this.bsOffcanvas) this.bsOffcanvas.show();
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
      anime.remove(".square");

      const squares = document.querySelectorAll(".square");

      squares.forEach((square, index) => {
        // Main square centering (only if you want them to move on load)
        anime({
          targets: square,
          translateX: () => {
            const rect = square.getBoundingClientRect();
            return window.innerWidth / 2 - rect.width / 2 - rect.left;
          },
          easing: "easeInOutSine",
          duration: 1000,
          delay: index * 100,
        });

        const balls = square.querySelectorAll(".ball");
        const circleRadius = 120; // Increased slightly for better spacing

        balls.forEach((ball, ballIndex) => {
          const angle = (ballIndex / balls.length) * 2 * Math.PI;
          const startX = Math.cos(angle) * circleRadius;
          const startY = Math.sin(angle) * circleRadius;
          const endX = Math.cos(angle) * (circleRadius + 15);
          const endY = Math.sin(angle) * (circleRadius + 15);

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
  }, // <--- End of methods
}).mount("#SUREappGP");
