const { createApp } = Vue;

createApp({
  data() {
    return {
      actions: [],
      modal_data: false,
      searchQuery: "",
      // Solid, saturated tag colors — deliberately a different family (dark,
      // vivid) from the pale tan/blue/lavender used by the filter buttons
      // above, so a bubble's category tag is never mistaken for a filter.
      categoryColors: {
        "SOCIAL SUPPORT": "#c2410c",
        COACHING: "#92400e",
        "FINANCIAL STABILITY": "#4d7c0f",
        PARTICIPATION: "#047857",
        FACILITIES: "#0f766e",
        GOVERNANCE: "#be185d",
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
      return this.categoryColors[category?.toUpperCase()] || "#6b7280";
    },
    async loadActions() {
      try {
        const response = await fetch("./actions.json");
        this.actions = await response.json();
        this.openActionFromURLParam();
      } catch (error) {
        console.error("Error loading actions:", error);
      }
    },
    openActionFromURLParam() {
      const params = new URLSearchParams(window.location.search);
      const actionId = params.get("action");
      if (!actionId) return;

      const action = this.actions.find((a) => String(a.id) === actionId);
      if (!action) return;

      this.modal_data = action;
      this.$nextTick(() => {
        const modalEl = document.getElementById("exampleModal");
        if (modalEl && window.bootstrap) {
          bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
      });
    },
    modal_data_insert(action) {
      this.modal_data = action;
    },
    downloadActionPDF(action) {
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "utilities/action_pdf.php";
      form.target = "_blank";

      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "payload";
      input.value = JSON.stringify(action);

      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    },
    initAnimations() {
      anime.remove(".bubble-item");
      const bubbles = document.querySelectorAll(".bubble-item");
      if (!bubbles.length) return;

      anime({
        targets: Array.from(bubbles),
        scale: [0, 1],
        opacity: [0, 1],
        duration: 500,
        easing: "easeOutBack",
        delay: anime.stagger(40, { from: "center" }),
        complete: () => {
          bubbles.forEach((bubble) => {
            anime({
              targets: bubble,
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
