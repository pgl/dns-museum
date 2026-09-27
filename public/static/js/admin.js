document.querySelectorAll("[data-delete-form]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    if (!window.confirm("Delete this exhibit? This removes its Markdown file.")) event.preventDefault();
  });
});

const visualEditor = document.querySelector("[data-visual-editor]");
if (visualEditor) {
  const form = visualEditor.closest("form");
  const markdownField = form.querySelector("#body-markdown");
  const previewBody = document.querySelector("[data-preview-body]");
  const allowedTags = new Set(["P", "DIV", "H1", "H2", "H3", "UL", "OL", "LI", "PRE", "CODE", "STRONG", "B", "EM", "I", "A", "BR"]);
  let uploadedImagePreview = "";

  const safeLink = (value) => {
    try {
      const url = new URL(value, window.location.origin);
      return ["http:", "https:", "mailto:"].includes(url.protocol) ? value : "";
    } catch {
      return "";
    }
  };

  const cleanNode = (node) => {
    if (node.nodeType === Node.TEXT_NODE) return document.createTextNode(node.nodeValue || "");
    if (node.nodeType !== Node.ELEMENT_NODE) return document.createDocumentFragment();
    if (!allowedTags.has(node.tagName)) {
      const fragment = document.createDocumentFragment();
      node.childNodes.forEach((child) => fragment.append(cleanNode(child)));
      return fragment;
    }
    const element = document.createElement(node.tagName === "DIV" ? "p" : node.tagName.toLowerCase());
    if (node.tagName === "A") {
      const href = safeLink(node.getAttribute("href") || "");
      if (href) {
        element.setAttribute("href", href);
        element.setAttribute("rel", "noopener noreferrer");
      }
    }
    node.childNodes.forEach((child) => element.append(cleanNode(child)));
    return element;
  };

  const cleanEditor = () => {
    const fragment = document.createDocumentFragment();
    visualEditor.childNodes.forEach((child) => fragment.append(cleanNode(child)));
    return fragment;
  };

  const inlineMarkdown = (node) => {
    if (node.nodeType === Node.TEXT_NODE) return node.nodeValue || "";
    if (node.nodeType !== Node.ELEMENT_NODE) return "";
    const inner = Array.from(node.childNodes, inlineMarkdown).join("");
    switch (node.tagName) {
      case "STRONG": case "B": return `**${inner}**`;
      case "EM": case "I": return `*${inner}*`;
      case "CODE": return `\`${inner}\``;
      case "A": {
        const href = safeLink(node.getAttribute("href") || "");
        return href ? `[${inner}](${href})` : inner;
      }
      case "BR": return "\n";
      default: return inner;
    }
  };

  const blockMarkdown = (node) => {
    if (node.nodeType === Node.TEXT_NODE) return node.nodeValue.trim() ? node.nodeValue.trim() : "";
    if (node.nodeType !== Node.ELEMENT_NODE) return "";
    const inner = Array.from(node.childNodes, inlineMarkdown).join("").trim();
    switch (node.tagName) {
      case "H1": case "H2": return inner ? `## ${inner}` : "";
      case "H3": return inner ? `### ${inner}` : "";
      case "UL": case "OL": return Array.from(node.children).map((item) => `- ${Array.from(item.childNodes, inlineMarkdown).join("").trim()}`).join("\n");
      case "PRE": return `\`\`\`\n${node.textContent.replace(/\n+$/, "")}\n\`\`\``;
      case "P": case "DIV": case "LI": return inner;
      default: return inner;
    }
  };

  const updatePreview = () => {
    const cleanContent = cleanEditor();
    previewBody.replaceChildren(cleanContent.cloneNode(true));
    markdownField.value = Array.from(visualEditor.childNodes, blockMarkdown).filter(Boolean).join("\n\n").trim();
    document.querySelector("[data-preview-title]").textContent = form.elements.title.value || "New entry";
    document.querySelector("[data-preview-summary]").textContent = form.elements.summary.value || "Summary will appear here.";
    document.querySelector("[data-preview-category]").textContent = form.elements.category.value || "Category";
    const tags = (form.elements.tags.value || "").split(",").map((tag) => tag.trim()).filter(Boolean);
    const tagList = document.querySelector("[data-preview-tags]");
    tagList.replaceChildren(...tags.map((tag) => {
      const span = document.createElement("span");
      span.textContent = tag;
      return span;
    }));
    const imagePath = form.elements.image.value.trim();
    const image = document.querySelector("[data-preview-image]");
    const figure = document.querySelector("[data-preview-figure]");
    const safeImage = safeLink(imagePath);
    const imageProtocol = safeImage ? new URL(safeImage, window.location.origin).protocol : "";
    const showImage = uploadedImagePreview || (safeImage && ["http:", "https:"].includes(imageProtocol) ? new URL(safeImage, window.location.origin).href : "");
    figure.hidden = !showImage;
    if (showImage) image.src = showImage;
    const sourcePath = form.elements.source.value.trim();
    const source = document.querySelector("[data-preview-source]");
    const sourceLink = document.querySelector("[data-preview-source-link]");
    const safeSource = safeLink(sourcePath);
    source.hidden = !safeSource;
    if (safeSource) {
      sourceLink.href = new URL(safeSource, window.location.origin).href;
      sourceLink.textContent = sourcePath;
    }
  };

  document.querySelectorAll("[data-format]").forEach((button) => {
    button.addEventListener("mousedown", (event) => event.preventDefault());
    button.addEventListener("click", () => {
      visualEditor.focus();
      const format = button.dataset.format;
      if (format === "link") {
        const href = window.prompt("Enter a web or email link:", "https://");
        if (href && safeLink(href)) document.execCommand("createLink", false, href);
      } else if (format === "ul") {
        document.execCommand("insertUnorderedList");
      } else if (format === "pre") {
        document.execCommand("formatBlock", false, "pre");
      } else if (format === "p" || format === "h2" || format === "h3") {
        document.execCommand("formatBlock", false, format);
      } else {
        document.execCommand(format === "bold" ? "bold" : "italic");
      }
      updatePreview();
    });
  });

  form.querySelectorAll("input:not([type=file]), select, textarea").forEach((field) => field.addEventListener("input", updatePreview));
  const imageUpload = form.querySelector('input[type="file"][name="image_upload"]');
  imageUpload.addEventListener("change", () => {
    if (uploadedImagePreview) URL.revokeObjectURL(uploadedImagePreview);
    uploadedImagePreview = imageUpload.files[0] ? URL.createObjectURL(imageUpload.files[0]) : "";
    updatePreview();
  });
  visualEditor.addEventListener("input", updatePreview);
  form.addEventListener("submit", (event) => {
    updatePreview();
    if (!markdownField.value) {
      event.preventDefault();
      visualEditor.focus();
      window.alert("Add some exhibit content before saving.");
    }
  });
  updatePreview();
}
