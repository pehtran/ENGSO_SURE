const { createApp, ref, nextTick } = Vue;

createApp({
  data() {
    return {
      questions: [],
      actions: [],
      currentStep: 0,
      maxStepReached: 0,
      reviewReturnStep: null,
      // Mirrors the ribbon colours used for each competency on the Crisis
      // Resilient Club Guide (good.html / sureGPVUE.js categoryColors), so a
      // competency's colour carries over from this scorecard into the guide.
      categoryColors: {
        "SOCIAL SUPPORT": "#c2410c",
        COACHING: "#92400e",
        "FINANCIAL STABILITY": "#4d7c0f",
        PARTICIPATION: "#047857",
        FACILITIES: "#0f766e",
        GOVERNANCE: "#be185d",
      },
      // Personalised, per-competency verdict copy for each score tier. Keyed
      // by the same uppercase competency names as categoryColors. Falls back
      // to the generic tierInfo() copy if a competency is ever missing here.
      competencyCopy: {
        "SOCIAL SUPPORT": {
          priority:
            "Right now, staff and coaches may not have a clear, confident way to spot when a participant is struggling or know who to bring them to. That's a gap that matters most exactly when things get hard — building it now means fewer people fall through the cracks later.",
          building:
            "You've got some of the awareness and outside partnerships that keep people supported, but it isn't consistent yet. A clearer referral routine and closer ties with local services would turn good intentions into a dependable safety net.",
          strong:
            "Your club already knows how to look after its people — staff are tuned in, and you've got real partnerships to lean on. Worth a periodic check-in, since this kind of readiness can quietly slip if it's not maintained.",
        },
        COACHING: {
          priority:
            "Coaches are usually the first to notice when something's wrong, but yours may not yet have the training or the backup to act on it. Investing here pays off everywhere else too — confident coaches make every other part of the club steadier.",
          building:
            "Your coaches have some of what they need, but training and peer support aren't routine yet. A bit more structure — regular check-ins, a clear escalation path — would make good instincts a reliable practice.",
          strong:
            "Your coaches are well-trained and well-supported, and they know exactly who to turn to when something comes up. That's a real strength — they're not carrying it alone.",
        },
        PARTICIPATION: {
          priority:
            "It's hard to picture your club running smoothly if your usual routines were disrupted — different spaces, different channels, different needs. That's precisely the scenario a crisis creates, which makes this an area worth prioritising.",
          building:
            "You can already reach and engage most participants, just not yet in every situation you might face. A little more flexibility in how and where you run things would close that gap.",
          strong:
            "Your club can reach its people wherever they are — in the community, online, in person — and has the relationships to back it up. That flexibility is exactly what keeps participation alive through disruption.",
        },
        "FINANCIAL STABILITY": {
          priority:
            "Your club is currently leaning on very few sources of income, which is a fragile place to stand if one of them disappears. This is often the single factor that decides whether a club survives a real shock — worth tackling early.",
          building:
            "There's some financial resilience here, but not enough spread to absorb a serious hit. Widening your mix of funding and partners would take the pressure off any one source.",
          strong:
            "Your funding is genuinely diversified, so losing one sponsor or grant wouldn't sink you. Just keep watching it — diversification has a way of narrowing quietly if no one's tracking it.",
        },
        GOVERNANCE: {
          priority:
            "Decisions may currently sit with too few people, with limited structure for hearing from the members you serve. In a crisis, that's exactly when diverse input and outside accountability matter most — worth addressing before you need it.",
          building:
            "There's a solid foundation of good governance here, but participant voice and outside accountability aren't fully built in yet. Strengthening that will make your decisions more resilient once things get pressured.",
          strong:
            "Your leadership reflects the community you serve, with real structures for listening and accountability. That's a genuine advantage when fast, trusted decisions matter most.",
        },
        FACILITIES: {
          priority:
            "If your usual venue became unavailable tomorrow, it's not yet clear your club could keep going. That's a common crisis scenario, so building real alternatives — remote, outdoor, borrowed space — deserves near-term attention.",
          building:
            "You've got some experience running things outside your normal setup, but it's not second nature yet. A bit more practice and a few more partner venues would make this far more dependable.",
          strong:
            "Your club already knows how to keep going without its usual facilities — remotely, outdoors, or through partner venues. That adaptability is a real asset when the unexpected happens.",
        },
      },
    };
  },
  computed: {
    progress() {
      if (!this.questions.length) return 0;
      const reached = Math.min(this.maxStepReached, this.questions.length);
      return (reached / this.questions.length) * 100;
    },
    progressPercentRounded() {
      return Math.round(this.progress);
    },
    trackLabel() {
      if (!this.questions.length) return "";
      if (this.currentStep >= this.questions.length) return "All questions answered";
      return `Question ${this.currentStep + 1} of ${this.questions.length}`;
    },
    categoryAverages() {
      if (!this.questions.length) return [];
      const categories = [...new Set(this.questions.map((q) => q.Competency))];
      return categories.map((cat) => {
        const group = this.questions.filter((q) => q.Competency === cat);
        const sum = group.reduce((acc, q) => acc + (q.Result || 0), 0);
        return {
          competency: cat,
          average: parseFloat((sum / group.length).toFixed(1)),
          count: group.length,
        };
      });
    },
    overallAverage() {
      if (!this.categoryAverages.length) return 0;
      const sum = this.categoryAverages.reduce((acc, c) => acc + c.average, 0);
      return parseFloat((sum / this.categoryAverages.length).toFixed(1));
    },
    overallPercent() {
      return Math.round((this.overallAverage / 5) * 100);
    },
    overallTier() {
      return this.tierInfo(this.overallAverage);
    },
    // A dynamic overall verdict that names the visitor's own weakest (or, if
    // all strong, still-improvable) competencies, so the headline reads as
    // a response to their specific answers rather than a generic verdict.
    overallVerdictText() {
      const tier = this.overallTier;
      const priorityCats = this.categoryAverages.filter((c) => c.average < 2.5).map((c) => c.competency);
      const buildingCats = this.categoryAverages
        .filter((c) => c.average >= 2.5 && c.average < 4)
        .map((c) => c.competency);

      if (tier.className === "is-priority") {
        const list = this.formatList(priorityCats.length ? priorityCats : buildingCats);
        return `Right now, ${list} would struggle to hold up under real pressure. That's not a verdict — it's a starting point. Work through those areas first; they'll do the most to protect your club when it matters.`;
      }
      if (tier.className === "is-building") {
        if (priorityCats.length) {
          return `Your club has real foundations in place, but ${this.formatList(priorityCats)} still need focused attention before you can call yourselves crisis-ready. Start there.`;
        }
        return `Your club has real foundations in place, but resilience is still uneven — ${this.formatList(buildingCats)} would benefit from a bit more reinforcement to make it consistent.`;
      }
      const watchList = buildingCats.length ? buildingCats : this.categoryAverages.map((c) => c.competency);
      return `Your club is in a strong position to weather a crisis. Keep an eye on ${this.formatList(watchList)} so today's strengths don't quietly erode over time.`;
    },
  },
  async created() {
    await Promise.all([this.loadQuestions(), this.loadActions()]);
  },
  methods: {
    async loadActions() {
      try {
        const response = await fetch("./actions.json");
        this.actions = await response.json();
      } catch (error) {
        console.error("Error loading actions:", error);
      }
    },
    actionsForCompetency(competency) {
      const upper = competency.toUpperCase();
      return this.actions.filter((a) => a.category && a.category.toUpperCase() === upper);
    },
    getCategoryColor(competency) {
      return this.categoryColors[competency?.toUpperCase()] || "#6b7280";
    },
    // Red/amber/green colour coding for weak/moderate/strong scores — this
    // was well received in user testing, so the score ring, meter and left
    // border for each competency are coloured by tier rather than by the
    // competency's own brand colour (that colour is reserved for the action
    // links, which carry it through into the Club Guide ribbons).
    tierColor(tierClassName) {
      if (tierClassName === "is-priority") return "#dc3545";
      if (tierClassName === "is-building") return "#e0a800";
      return "#198754";
    },
    formatList(items) {
      if (!items.length) return "";
      if (items.length === 1) return items[0];
      if (items.length === 2) return `${items[0]} and ${items[1]}`;
      return `${items.slice(0, -1).join(", ")} and ${items[items.length - 1]}`;
    },
    // Personalised per-competency verdict, falling back to the generic
    // tier copy if this competency has no bespoke text.
    competencyMessage(competency, avg) {
      const tier = this.tierInfo(avg);
      const tierKey = tier.className === "is-priority" ? "priority" : tier.className === "is-building" ? "building" : "strong";
      const entry = this.competencyCopy[competency?.toUpperCase()];
      return (entry && entry[tierKey]) || tier.copy;
    },
    actionPillHTML(action, color) {
      return `<a href="good.html?action=${encodeURIComponent(action.id)}" target="_blank" rel="noopener" class="result-action-pill" style="--pill-color:${color}">${action.title}</a>`;
    },
    // Single source of truth for the three score tiers, shared by the
    // overall headline score and every per-competency card below it.
    tierInfo(avg) {
      if (avg < 2.5) {
        return {
          className: "is-priority",
          label: "Priority focus",
          copy: "Needs focused attention — a priority area for crisis readiness.",
        };
      }
      if (avg < 4) {
        return {
          className: "is-building",
          label: "Building strength",
          copy: "Solid groundwork is in place. Keep reinforcing it.",
        };
      }
      return {
        className: "is-strong",
        label: "Performing well",
        copy: "A genuine strength — well prepared for a crisis.",
      };
    },
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

        if (this.currentStep > this.maxStepReached) {
          this.maxStepReached = this.currentStep;
        }

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
    // Called whenever an answer is selected. If the user is reviewing/editing
    // a previously answered question (jumped back via the dot navigation),
    // send them back to where they came from instead of stepping forward
    // one question at a time.
    handleAnswer() {
      if (this.reviewReturnStep === null) {
        this.nextQuestion();
        return;
      }

      const returnStep = this.reviewReturnStep;
      this.reviewReturnStep = null;

      setTimeout(() => {
        this.currentStep = returnStep;

        if (this.currentStep < this.questions.length) {
          nextTick(() => this.animateIn());
        } else {
          nextTick(() => {
            this.renderSpiderChart();
            this.renderResultSummary();
          });
        }
      }, 300);
    },
    // Dot navigation: only questions already reached can be jumped to,
    // so users can revisit and change past answers but never skip ahead.
    goToQuestion(index) {
      if (index > this.maxStepReached || index === this.currentStep) return;

      if (this.reviewReturnStep === null) {
        this.reviewReturnStep = this.currentStep;
      }

      this.currentStep = index;
      nextTick(() => this.animateIn());
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
      const categories = this.categoryAverages.map((c) => c.competency.toUpperCase());
      const resultData = this.categoryAverages.map((c) => c.average);

      Highcharts.chart("container", {
        chart: {
          polar: true,
          type: "area",
          backgroundColor: "transparent",
          style: { fontFamily: '"Barlow", sans-serif' },
        },
        title: {
          text: "",
        },
        pane: {
          size: "80%",
        },
        xAxis: {
          categories: categories,
          tickmarkPlacement: "on",
          lineWidth: 0,
          labels: {
            style: {
              color: "#1f3a2a",
              fontFamily: '"Barlow Condensed", sans-serif',
              fontWeight: "600",
              fontSize: "12px",
              letterSpacing: "0.03em",
            },
          },
        },
        yAxis: {
          gridLineInterpolation: "polygon",
          gridLineColor: "rgba(63, 107, 82, 0.18)",
          lineWidth: 0,
          min: 0,
          max: 5, // Set max to 5 to match the 1-5 radio scale
          tickInterval: 1,
          labels: {
            style: { color: "rgba(31, 58, 42, 0.55)", fontSize: "11px" },
          },
        },
        tooltip: {
          shared: true,
          pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y}</b></span><br/>',
          backgroundColor: "#faf6ec",
          borderColor: "#eab53c",
          style: { fontFamily: '"Barlow", sans-serif', color: "#1f3a2a" },
        },
        legend: {
          enabled: false,
        },
        series: [
          {
            name: "Your score",
            data: resultData,
            pointPlacement: "on",
            color: "#eab53c",
            lineWidth: 3,
            fillColor: "rgba(234, 181, 60, 0.28)",
            marker: { fillColor: "#2f5d45", lineColor: "#fff", lineWidth: 2, radius: 5 },
          },
        ],
        credits: {
          style: { color: "rgba(31, 58, 42, 0.35)", fontSize: "10px" },
        },
        responsive: {
          rules: [
            {
              condition: { maxWidth: 500 },
              chartOptions: {
                pane: { size: "70%" },
              },
            },
          ],
        },
      });
    },
    renderResultSummary() {
      let html = `<div class="results-breakdown">
        <div class="results-breakdown-head">
          <span class="results-eyebrow">Full breakdown</span>
          <h3>Competency Breakdown</h3>
          <p>Each area is scored out of 5, colour-coded red/amber/green for weak/moderate/strong.</p>
        </div>
        <div class="results-list">`;

      this.categoryAverages.forEach(({ competency: cat, average: avg, count }) => {
        const brandColor = this.getCategoryColor(cat);
        const pct = Math.round((avg / 5) * 100);
        const tier = this.tierInfo(avg);
        const ragColor = this.tierColor(tier.className);
        const primary = this.actionsForCompetency(cat);
        const primaryLabel = tier.className === "is-priority" ? "Start here" : tier.className === "is-building" ? "Explore next steps" : "Go further";

        let actionsHTML = "";
        if (primary.length) {
          actionsHTML += `<div class="result-actions-label">${primaryLabel}</div>
          <div class="result-actions">${primary.slice(0, 2).map((a) => this.actionPillHTML(a, brandColor)).join("")}</div>`;
        }
        actionsHTML += `<a href="good.html?category=${encodeURIComponent(cat)}" target="_blank" rel="noopener" class="result-explore-all">See more recommendations on developing ${cat} in the Club Guide &rarr;</a>`;

        html += `
      <article class="result-row" style="--cat-color:${ragColor}">
        <div class="result-meter" style="--cat-pct:${pct}">
          <span class="result-meter-value">${avg}<small>/5</small></span>
        </div>
        <div class="result-row-body">
          <span class="result-status-chip ${tier.className}">${tier.label}</span>
          <h4 class="result-row-heading">${cat}</h4>
          <span class="result-card-basis">Based on ${count} question${count === 1 ? "" : "s"}</span>
          <p class="result-card-copy">${this.competencyMessage(cat, avg)}</p>
          ${actionsHTML}
        </div>
      </article>`;
      });

      html += `</div></div>`;
      return html;
    },
    downloadResults() {
      // Map competency averages to the summarized export format, reusing the
      // same tier thresholds/copy as the on-screen breakdown (tierInfo).
      const summaryData = this.categoryAverages.map(({ competency: cat, average: avg, count }) => {
        const tier = this.tierInfo(avg);

        return {
          competency: cat,
          averageScore: avg,
          questionCount: count,
          status: tier.label,
          performingWell: tier.className === "is-strong",
          recommendation: this.competencyMessage(cat, avg),
        };
      });

      // Create the final object to download
      const exportBlob = {
        exportDate: new Date().toISOString(),
        overallResults: summaryData,
        rawDetails: this.questions, // Optional: keeping raw data for reference
      };

      // Create a hidden form to send data via POST to a new tab
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "utilities/test.php";
      form.target = "_blank"; // This forces the new tab

      const input = document.createElement("input");
      input.type = "textarea";
      input.name = "payload";
      input.value = JSON.stringify(exportBlob);

      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    },
    fillRandomTest() {
      // Testing helper: randomly answers every question and jumps to results.
      this.questions.forEach((q) => {
        q.Result = Math.floor(Math.random() * 5) + 1;
      });
      this.currentStep = this.questions.length;
      this.maxStepReached = this.questions.length;
      nextTick(() => {
        this.renderSpiderChart();
        this.renderResultSummary();
      });
    },
    reset_form() {
      this.questions.forEach((q) => {
        q.Result = null;
      });
      this.currentStep = 0;
      this.maxStepReached = 0;
      this.reviewReturnStep = null;
    },
  },
}).mount("#SUREapp");
