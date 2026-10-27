const searchInput = document.querySelector("[data-collection-search]");

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
