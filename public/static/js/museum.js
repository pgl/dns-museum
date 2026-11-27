const searchInput = document.querySelector("[data-collection-search]");

const quickSearch = document.querySelector("[data-quick-search]");
if (quickSearch) {
  const input = quickSearch.querySelector("input");
  const results = Array.from(quickSearch.querySelectorAll("[data-quick-result]"));
  const status = quickSearch.querySelector("[role=status]");
  const normalize = (value) => value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase();
  const update = () => {
    const terms = normalize(input.value.trim()).split(/\s+/).filter(Boolean);
    let count = 0;
    results.forEach((result) => {
      const text = normalize(result.dataset.searchText);
      result.hidden = !terms.every((term) => text.includes(term));
      if (!result.hidden) count += 1;
    });
    status.textContent = quickSearch.dataset.available === "true" ? `${count} ${count === 1 ? "exhibit" : "exhibits"}` : "Search is temporarily unavailable.";
  };
  document.addEventListener("keydown", (event) => {
    if ((event.metaKey || event.ctrlKey) && !event.altKey && event.key.toLowerCase() === "k") {
      if (event.target.closest("[contenteditable=true]")) return;
      event.preventDefault();
      if (!quickSearch.open) quickSearch.showModal();
      input.focus();
      input.select();
    }
  });
  document.querySelector("[data-search-open]").addEventListener("click", () => {
    quickSearch.showModal();
    input.focus();
  });
  quickSearch.querySelector("[data-search-close]").addEventListener("click", () => quickSearch.close());
  quickSearch.addEventListener("click", (event) => { if (event.target === quickSearch) quickSearch.close(); });
  input.addEventListener("input", update);
  input.addEventListener("keydown", (event) => {
    if (event.key !== "Tab" || event.shiftKey || event.ctrlKey || event.metaKey || event.altKey) return;
    const query = normalize(input.value.trim());
    if (!query) return;
    const match = results.find((result) => !result.hidden && normalize(result.querySelector("strong").textContent).startsWith(query));
    const title = match?.querySelector("strong").textContent;
    if (title && normalize(title) !== query) {
      event.preventDefault();
      input.value = title;
      update();
    }
  });
  update();
}

if (searchInput) {
  const cards = Array.from(document.querySelectorAll("[data-exhibit-card]"));
  const status = document.querySelector("#search-status");
  const emptyMessage = document.querySelector("[data-no-search-results]");
  const normalize = (value) => value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase();

  searchInput.addEventListener("input", () => {
    const query = normalize(searchInput.value.trim());
    let visibleCount = 0;

    cards.forEach((card) => {
      const matches = normalize(card.dataset.searchText).includes(query);
      card.hidden = !matches;
      if (matches) visibleCount += 1;
    });

    document.querySelectorAll(".collection-section").forEach((section) => {
      const sectionCount = section.querySelectorAll("[data-exhibit-card]:not([hidden])").length;
      section.hidden = sectionCount === 0;
      section.querySelector(".collection-section-heading > span").textContent = `${sectionCount} ${sectionCount === 1 ? "entry" : "entries"}`;
      const categoryLink = document.querySelector(`.category-index a[href="#${CSS.escape(section.id)}"]`);
      if (categoryLink) {
        categoryLink.hidden = sectionCount === 0;
        categoryLink.querySelector("small").textContent = sectionCount;
      }
    });

    status.textContent = query
      ? `${visibleCount} ${visibleCount === 1 ? "exhibit" : "exhibits"} found`
      : `${cards.length} exhibits`;
    emptyMessage.hidden = visibleCount !== 0;
  });
}
