document.querySelectorAll("[data-delete-form]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    if (!window.confirm("Delete this exhibit? This removes its Markdown file.")) event.preventDefault();
  });
});

document.querySelectorAll(".editor-form").forEach((form) => {
  const imageInput = form.querySelector('[name="image_upload"]');
  const thumbnailInput = form.querySelector('[name="image_thumbnail"]');
  let thumbnailReady = false;
  let preparingThumbnail = false;

  form.addEventListener("submit", async (event) => {
    const image = imageInput.files[0];
    if (!image || thumbnailReady) return;

    event.preventDefault();
    if (preparingThumbnail) return;
    preparingThumbnail = true;

    try {
      if (image.size > 2 * 1024 * 1024) throw new Error("Choose an image smaller than 2 MB.");
      const bitmap = await createImageBitmap(image);
      if (bitmap.width > 8000 || bitmap.height > 8000 || bitmap.width * bitmap.height > 20000000) {
        bitmap.close();
        throw new Error("Choose an image no larger than 8000 pixels per side or 20 megapixels.");
      }

      const scale = Math.min(1, 640 / Math.max(bitmap.width, bitmap.height));
      const canvas = document.createElement("canvas");
      canvas.width = Math.max(1, Math.round(bitmap.width * scale));
      canvas.height = Math.max(1, Math.round(bitmap.height * scale));
      const context = canvas.getContext("2d");
      context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      bitmap.close();

      const thumbnail = await new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
          if (blob && blob.type === "image/webp") resolve(blob);
          else reject(new Error("This browser cannot create WebP thumbnails."));
        }, "image/webp", 0.78);
      });
      const transfer = new DataTransfer();
      transfer.items.add(new File([thumbnail], "thumbnail.webp", { type: "image/webp" }));
      thumbnailInput.files = transfer.files;
      thumbnailReady = true;
      if (event.submitter) form.requestSubmit(event.submitter);
      else form.requestSubmit();
    } catch (error) {
      window.alert(error.message || "The thumbnail could not be created. Try another image.");
    } finally {
      preparingThumbnail = false;
    }
  });
});
