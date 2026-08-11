// Animations are now handled by the Vue component's initAnimations() method.

// Close the "How this works" popover when the visitor clicks outside it
// or presses Escape, since it floats over the page instead of sitting inline.
function closeIntroPopovers() {
  document.querySelectorAll("details.intro-popover[open]").forEach((el) => el.removeAttribute("open"));
}

document.addEventListener("click", (event) => {
  document.querySelectorAll("details.intro-popover[open]").forEach((el) => {
    if (!el.contains(event.target)) el.removeAttribute("open");
  });
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") closeIntroPopovers();
});
