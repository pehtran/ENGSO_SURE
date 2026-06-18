const { createApp } = Vue;

createApp({
  data() {
    return {
      actions: [],
      modal_data: false,
      searchQuery: "",
      categoryColors: {
        "SOCIAL SUPPORT": "linear-gradient(145deg, #FFE8D6, #DDBEA9)",
        COACHING: "linear-gradient(145deg, #D6E4F0, #9DB4CE)",
        GOVERNANCE: "linear-gradient(145deg, #E6D7F1, #C4AAD8)",
        ACTIVITY: "linear-gradient(145deg, #D5EDDA, #99CCA6)",
        "FINANCIAL STABILITY": "linear-gradient(145deg, #FFF0C9, #E0C479)",
        FACILITIES: "linear-gradient(145deg, #F2DCE8, #D4A0BC)",
        PARTICIPATION: "linear-gradient(145deg, #D5ECE8, #95C9BF)",
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
        "REFUGEES AND MIGRANTS": true,
      },
      selectedDevelopmentAreas: [
        "COACHING",
        "GOVERNANCE",
        "FINANCIAL STABILITY",
        "FACILITIES",
        "PARTICIPATION",
        "SOCIAL SUPPORT",
      ],
      selectedCrises: [
        "NATURAL DISASTERS",
        "DISPLACEMENT",
        "PANDEMICS",
        "ECONOMIC CRISIS",
        "ENERGY SHORTAGES",
      ],
      selectedTargetGroups: [
        "CHILDREN",
        "WOMEN",
        "PEOPLE WITH PHYSICAL DISABILITIES",
        "LOW-INCOME",
        "REFUGEES AND MIGRANTS",
      ],
    };
  },
  computed: {
    filteredActionsList() {
      const query = this.searchQuery ? this.searchQuery.toLowerCase().trim() : "";

      return this.actions.filter((action) => {
        const crisisKeys = action.crysis_type.map((t) => t.toUpperCase());
        const targetKeys = action.target_group.map((g) => g.toUpperCase());
        const devAreaKeys = action.development_area.map((a) => a.toUpperCase());

        const selectedCrisisUpper = this.selectedCrises.map((c) => c.toUpperCase());
        const selectedDevUpper = this.selectedDevelopmentAreas.map((d) => d.toUpperCase());
        const selectedTargetUpper = this.selectedTargetGroups.map((g) => g.toUpperCase());

        const crisisMatch = crisisKeys.some((k) => selectedCrisisUpper.includes(k));
        const devMatch = devAreaKeys.some((k) => selectedDevUpper.includes(k));
        const targetMatch = targetKeys.some((k) => selectedTargetUpper.includes(k));

        const searchFields = [
          action.title,
          action.description,
          action.category,
          ...(action.tags || []),
        ]
          .join(" ")
          .toLowerCase();
        const searchMatch = query === "" || searchFields.includes(query);

        return crisisMatch && devMatch && targetMatch && searchMatch;
      });
    },
  },
  async created() {
    await this.loadActions();
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    const category_param = urlParams.get("category");
    if (category_param) {
      this.selectedDevelopmentAreas = [category_param.toUpperCase()];
    }

    this.$nextTick(() => {
      this.initAnimations();
    });
  },
  watch: {
    filteredActionsList() {
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
    getCategoryColor(category) {
      return (
        this.categoryColors[category?.toUpperCase()] ||
        "linear-gradient(145deg, #ccc, #aaa)"
      );
    },
    async loadActions() {
      try {
        const response = await fetch("./actions.json");
        this.actions = await response.json();
      } catch (error) {
        console.error("Error loading actions:", error);
      }
    },
    modal_data_insert(action) {
      this.modal_data = action;
    },
    initAnimations() {
      anime.remove(".ball");
      const balls = document.querySelectorAll(".ball");
      if (!balls.length) return;

      anime({
        targets: Array.from(balls),
        scale: [0, 1],
        opacity: [0, 1],
        duration: 500,
        easing: "easeOutBack",
        delay: anime.stagger(40, { from: "center" }),
        complete: () => {
          balls.forEach((ball) => {
            anime({
              targets: ball,
              translateX: () => anime.random(-5, 5),
              translateY: () => anime.random(-5, 5),
              duration: 3000 + Math.random() * 2000,
              easing: "easeInOutSine",
              direction: "alternate",
              loop: true,
            });
          });
        },
      });
    },
  },
}).mount("#SUREappGP");
