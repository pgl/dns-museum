document.querySelectorAll("[data-delete-form]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    if (!window.confirm("Delete this exhibit? This removes its Markdown file.")) event.preventDefault();
  });
});
