const { createApp, ref, nextTick } = Vue;

createApp({
  data() {
    return {
      questions: [],
      currentStep: 0,
    };
  },
  computed: {
    progress() {
      return (this.currentStep / this.questions.length) * 100;
    },
  },
  created() {
    this.loadQuestions();
  },
  methods: {
    async loadQuestions() {
      try {
        const response = await fetch("./questions.json");
        const data = await response.json();

        // 1. Get the array from the JSON
        let fetchedQuestions = data.questions;

        // 2. Fisher-Yates Shuffle Algorithm
        for (let i = fetchedQuestions.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [fetchedQuestions[i], fetchedQuestions[j]] = [fetchedQuestions[j], fetchedQuestions[i]];
        }

        // 3. Assign the shuffled array to Vue
        this.questions = fetchedQuestions;

        // Animate the first question
        nextTick(() => this.animateIn());

        console.log("Questions randomized successfully!");
      } catch (error) {
        console.error("Error loading or shuffling questions:", error);
      }
    },
    nextQuestion() {
      setTimeout(() => {
        this.currentStep++;

        if (this.currentStep < this.questions.length) {
          nextTick(() => this.animateIn());
        } else {
          // All questions answered!
          // Wait for the results div to show up, then draw the chart.
          nextTick(() => {
            this.renderSpiderChart();
            this.renderResultSummary();
          });
        }
      }, 300);
    },
    animateIn() {
      // Check if anime actually exists before calling it to prevent the crash
      if (typeof anime !== "undefined") {
        anime({
          targets: ".question-card", // Ensure your HTML div has class="question-card"
          translateX: [0, 0],
          translateY: [50, 0],
          opacity: [0, 1],
          duration: 1000,
          easing: "easeOutQuart",
        });
      } else {
        console.error("Anime.js is not loaded! Check your script tags.");
      }
    },
    renderSpiderChart() {
      // 1. Get Unique Competencies for the xAxis categories
      const categories = [...new Set(this.questions.map((q) => q.Competency))];

      // 2. Calculate the average score for each Competency
      const resultData = categories.map((cat) => {
        const group = this.questions.filter((q) => q.Competency === cat);
        const sum = group.reduce((acc, q) => acc + (q.Result || 0), 0);
        return parseFloat((sum / group.length).toFixed(2));
      });

      // 3. Initialize Highcharts
      Highcharts.chart("container", {
        chart: {
          polar: true,
          type: "line",
        },
        title: {
          text: "",
          x: -80,
        },
        pane: {
          size: "80%",
        },
        xAxis: {
          categories: categories, // Dynamic categories from JSON
          tickmarkPlacement: "on",
          lineWidth: 0,
        },
        yAxis: {
          gridLineInterpolation: "polygon",
          lineWidth: 0,
          min: 0,
          max: 5, // Set max to 5 to match your 1-5 radio scale
        },
        tooltip: {
          shared: true,
          pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y}</b><br/>',
        },
        legend: {
          enabled: false,
          align: "right",
          verticalAlign: "middle",
          layout: "vertical",
        },
        series: [
          {
            name: "Your Score",
            data: resultData, // Dynamic data from your answers
            pointPlacement: "on",
            color: "#198754", // Match Bootstrap success green
          },
        ],
        responsive: {
          rules: [
            {
              condition: { maxWidth: 500 },
              chartOptions: {
                title: { x: 0 },
                legend: { align: "center", verticalAlign: "bottom", layout: "horizontal" },
                pane: { size: "70%" },
              },
            },
          ],
        },
      });
    },
    renderResultSummary() {
      const categories = [...new Set(this.questions.map((q) => q.Competency))];

      let html = `<div class="mt-4">
                <h3 class="mb-3 text-center">Competency Breakdown</h3>
                <div class="list-group shadow-sm">`;

      categories.forEach((cat) => {
        const group = this.questions.filter((q) => q.Competency === cat);
        const sum = group.reduce((acc, q) => acc + (q.Result || 0), 0);
        const avg = parseFloat((sum / group.length).toFixed(1));

        // Determine badge color
        let badgeClass = "bg-danger";
        if (avg >= 4) badgeClass = "bg-success";
        else if (avg >= 2.5) badgeClass = "bg-warning text-dark";

        // --- NEW LOGIC START ---
        let feedbackHTML = "";
        if (avg < 4) {
          feedbackHTML = `
        <div class="mt-2 small p-2 bg-light border-start border-warning border-4 rounded">
          <strong>Need a boost?</strong> Suggesting resources for <em>${cat}</em>... 
          <a href="#" class="text-decoration-none">View Guide</a>
        </div>`;
        } else {
          feedbackHTML = `
        <div class="mt-2 small p-2 text-success">
          <i class="bi bi-check-circle"></i> You are doing great in this area!
        </div>`;
        }
        // --- NEW LOGIC END ---

        html += `
      <div class="list-group-item p-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong class="d-block text-uppercase small text-muted">${cat}</strong>
            <span class="text-secondary small">Based on ${group.length} questions</span>
          </div>
          <span class="badge ${badgeClass} rounded-pill fs-6 px-3">
            ${avg} / 5
          </span>
        </div>
        ${feedbackHTML}
      </div>`;
      });

      html += `</div></div>`;
      return html;
    },
    reset_form() {
      this.questions.forEach((q) => {
        q.Result = null;
      });
    }
  },
}).mount("#SUREapp");
