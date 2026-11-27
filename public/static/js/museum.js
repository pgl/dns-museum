const searchInput = document.querySelector("[data-collection-search]");
const normalize = (value) => value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase();

const quickSearch = document.querySelector("[data-quick-search]");
if (quickSearch) {
  const input = quickSearch.querySelector("input");
  const list = quickSearch.querySelector(".quick-search-results");
  const status = quickSearch.querySelector("[role=status]");
  let results = null;
  let loading = false;

  const update = () => {
    if (!results) return;
    const terms = normalize(input.value.trim()).split(/\s+/).filter(Boolean);
    let count = 0;
    results.forEach(({ item, text }) => {
      item.hidden = !terms.every((term) => text.includes(term));
      if (!item.hidden) count += 1;
    });
    status.textContent = `${count} ${count === 1 ? "exhibit" : "exhibits"}`;
  };

  const load = async () => {
    if (results || loading) return;
    loading = true;
    status.textContent = "Loading exhibits…";
    try {
      const response = await fetch("/search-index.json");
      if (!response.ok) throw new Error("Could not load the exhibit index.");
      const entries = await response.json();
      const fragment = document.createDocumentFragment();
      const loadedResults = entries.map(({ slug, title, summary, text }) => {
        const item = document.createElement("li");
        const link = document.createElement("a");
        const heading = document.createElement("strong");
        const description = document.createElement("span");
        link.href = `/exhibits/${encodeURIComponent(slug)}`;
        heading.textContent = title;
        description.textContent = summary;
        link.append(heading, description);
        item.append(link);
        fragment.append(item);
        return { item, title, normalizedTitle: normalize(title), text: normalize(`${title} ${summary} ${text}`) };
      });
      list.append(fragment);
      results = loadedResults;
      update();
    } catch {
      status.textContent = "Search is temporarily unavailable.";
    } finally {
      loading = false;
    }
  };

  const open = () => {
    if (!quickSearch.open) quickSearch.showModal();
    input.focus();
    load();
  };

  document.addEventListener("keydown", (event) => {
    if ((event.metaKey || event.ctrlKey) && !event.altKey && event.key.toLowerCase() === "k") {
      if (event.target.closest("[contenteditable=true]")) return;
      event.preventDefault();
      open();
      input.select();
    }
  });
  document.querySelector("[data-search-open]").addEventListener("click", open);
  quickSearch.querySelector("[data-search-close]").addEventListener("click", () => quickSearch.close());
  quickSearch.addEventListener("click", (event) => { if (event.target === quickSearch) quickSearch.close(); });
  input.addEventListener("input", update);
  input.addEventListener("keydown", (event) => {
    if (event.key !== "Tab" || event.shiftKey || event.ctrlKey || event.metaKey || event.altKey) return;
    const query = normalize(input.value.trim());
    if (!query || !results) return;
    const match = results.find(({ item, normalizedTitle }) => !item.hidden && normalizedTitle.startsWith(query));
    if (match && match.normalizedTitle !== query) {
      event.preventDefault();
      input.value = match.title;
      update();
    }
  });
}

if (searchInput) {
  const cards = Array.from(document.querySelectorAll("[data-exhibit-card]"), (card) => ({
    card,
    text: normalize(card.dataset.searchText),
  }));
  const status = document.querySelector("#search-status");
  const emptyMessage = document.querySelector("[data-no-search-results]");

  searchInput.addEventListener("input", () => {
    const query = normalize(searchInput.value.trim());
    let visibleCount = 0;

    cards.forEach(({ card, text }) => {
      const matches = text.includes(query);
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
