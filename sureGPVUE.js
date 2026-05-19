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
        "SOCIAL SUPPORT": "linear-gradient(145deg, #ffcc33, #6fc400)",
        COACHING: "linear-gradient(145deg, #70cbff, #4facfe)",
        GOVERNANCE: "linear-gradient(145deg, #a8edea, #fed6e3)",
        ACTIVITY: "linear-gradient(145deg, #84fab0, #8fd3f4)",
        "FINANCIAL STABILITY": "linear-gradient(145deg, #f6d365, #b63815)",
        FACILITIES: "linear-gradient(145deg, #e0c3fc, #8ec5fc)",
      },
      dev_areas: {
        "SOCIAL SUPPORT": true,
        COACHING: true,
        GOVERNANCE: true,
        "FINANCIAL STABILITY": true,
        FACILITIES: true,
        PARTICIPATION: true,
      },
      crisis_types: {
        "NATURAL DISASTERS": true,
        DISPLACEMENT: true,
        PANDEMICS: true,
        "ECONOMIC CRISIS": true,
        "ENERGY SHORTAGES": true,
      },
      target_groups: {
        CHILDREN: true,
        WOMEN: true,
        "PEOPLE WITH PHYSICAL DISABILITIES": true,
        "LOW-INCOME": true,
        "REFUGEES & MIGRANTS": true,
      },
      selectedDevelopmentAreas: ["COACHING", "GOVERNANCE", "FINANCIAL STABILITY", "FACILITIES", "PARTICIPATION", "SOCIAL SUPPORT"],
      selectedCrises: ["NATURAL DISASTERS", "DISPLACEMENT", "PANDEMICS", "ECONOMIC CRISIS", "ENERGY SHORTAGES"],
      selectedTargetGroups: ["CHILDREN", "WOMEN", "PEOPLE WITH PHYSICAL DISABILITIES", "LOW-INCOME", "REFUGEES & MIGRANTS"],
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
    selectedDevelopmentAreas() {
      this.$nextTick(() => {
        this.initAnimations();
      });
    },
    selectedCrises() {
      this.$nextTick(() => {
        this.initAnimations();
      });
    },
    selectedTargetGroups() {
      this.$nextTick(() => {
        this.initAnimations();
      });
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

      const categoryColors = {
        "SOCIAL SUPPORT": "linear-gradient(145deg, #fade8b, #89e215)",
        COACHING: "linear-gradient(145deg, #cfe3ee, #5087b8)",
        GOVERNANCE: "linear-gradient(145deg, #a8edea, #fed6e3)",
        ACTIVITY: "linear-gradient(145deg, #c0fcd6, #68b4da)",
        "FINANCIAL STABILITY": "linear-gradient(145deg, #f6d365, #db4319)",
        FACILITIES: "linear-gradient(145deg, #e0c3fc, #8ec5fc)",
      };

      // 1. Get the filtered list of actions
      const filteredList = this.actions.filter((action) => {
        const catKey = action.category.toUpperCase(); 
        
        //const targetCategory = category.toUpperCase();
        // target category is if it is included


        const categoryMatch = this.selectedDevelopmentAreas.includes(catKey);
        
        const tagMatch = this.selectedTags.length === 0 || action.tags.some((tag) => this.selectedTags.includes(tag));

        const searchFields = [action.title, action.description, action.category, ...action.tags].join(" ").toLowerCase();
        const searchMatch = query === "" || searchFields.includes(query);

        return categoryMatch && tagMatch && searchMatch;
      });

      const totalBalls = filteredList.length;
      if (totalBalls === 0) return "";

      // 2. Define grid dimensions dynamically based on ball count
      const cols = Math.ceil(Math.sqrt(totalBalls * 1.3));
      const rows = Math.ceil(totalBalls / cols);

      // Percentage boundaries inside the container to keep things centered
      const startX = 15;
      const startY = 15;
      const endX = 85;
      const endY = 85;

      const stepX = cols > 1 ? (endX - startX) / (cols - 1) : 0;
      const stepY = rows > 1 ? (endY - startY) / (rows - 1) : 0;

    
      // 3. Map over the filtered list with precise grid placement
      return filteredList
        .map((action, index) => {
          const bgStyle = categoryColors[action.category.toUpperCase()] || "#ccc";

          const colIndex = index % cols;
          const rowIndex = Math.floor(index / cols);

          let currentStartX = startX;
          let currentStepX = stepX;

          const isLastRow = rowIndex === rows - 1;
          const ballsInLastRow = totalBalls % cols || cols;

          if (isLastRow && ballsInLastRow < cols && cols > 1) {
            const unusedSpacePercentage = (cols - ballsInLastRow) * stepX;
            currentStartX = startX + unusedSpacePercentage / 2;
          }

          const finalLeft = cols > 1 ? currentStartX + colIndex * currentStepX : 50;
          const finalTop = rows > 1 ? startY + rowIndex * stepY : 50;

          return `<div class="ball" 
                   id="${action.id}" 
                   style="background: ${bgStyle}; 
                          position: absolute; 
                          top: ${finalTop}%; 
                          left: ${finalLeft}%; 
                          transform: translate(-50%, -50%); 
                          font-size: 1.1rem; 
                          display: flex; 
                          align-items: center; 
                          justify-content: center; 
                          text-align: center; 
                          padding: 12px; 
                          border-radius: 50%; 
                          width: 140px; 
                          height: 140px; 
                          cursor: pointer; 
                          box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
                          user-select: none;" 
                   data-bs-toggle="modal" 
                   data-bs-target="#exampleModal">
                ${action.title}
              </div>`;
        })
        .join("");
    },

    handleBallHover(event) {
      const ball = event.target;
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

          setTimeout(() => {
            const isStillHovered = ball.matches(":hover");
            if (isStillHovered && this.bsOffcanvas) {
              this.bsOffcanvas.show();
            }
          }, 3000);
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
      if (event.target.classList.contains("ball")) {
        console.log("Ball clicked:", event.target.innerText);
        const ballIdbyTitle = event.target.innerText;
        this.modal_data_insert(ballIdbyTitle);
      }
    },

    modal_data_insert(id) {
      const action = this.actions.find((a) => a.title === id);
      console.log("Inserting data for:", action.id);
      this.modal_data = action;
    },

    return_all_tags() {
      const allTags = new Set();
      this.actions.forEach((action) => {
        action.tags.forEach((tag) => allTags.add(tag));
      });
      this.all_tags = Array.from(allTags);
      return Array.from(allTags);
    },

    // FIXED ANIMATION ENGINE: No more circle transformation overlaps!
    initAnimations() {
      // 1. Terminate all running animation loops instantly
      anime.remove(".ball");

      const balls = document.querySelectorAll(".ball");

      balls.forEach((ball) => {
        // Clear out old residual transform coordinates so they snap back onto their grid layout
        anime.set(ball, { translateX: 0, translateY: 0 });

        // Apply a safe, microscopic floating drift so they feel fluid but stay on their grid nodes
        anime({
          targets: ball,
          translateX: () => anime.random(-6, 6),
          translateY: () => anime.random(-6, 6),
          duration: 3000 + Math.random() * 2000,
          easing: "easeInOutSine",
          direction: "alternate",
          loop: true,
          delay: Math.random() * 1000,
        });
      });
    },

    ballHoverAnimation(ball) {
      // Cleaned up placeholder to prevent reference runtime bugs
      anime({
        targets: ball,
        scale: 1.1,
        duration: 200,
        easing: "easeOutQuad",
      });
    },

    toggleAllTags() {
      if (!this.toggleTags) {
        this.selectedTags = [...this.all_tags];
      } else {
        this.selectedTags = [];
      }
    },
  },
}).mount("#SUREappGP");
