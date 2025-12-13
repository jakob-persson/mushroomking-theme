document.addEventListener("DOMContentLoaded", () => {

  /* -------------------------------------------------
   *  Alpine store för modal
   * ------------------------------------------------- */
  document.addEventListener("alpine:init", () => {
    Alpine.store("modal", {
      isOpen: false,
    });

    window.openAdventureEditor = function (data) {
      Alpine.store("modal").isOpen = true;
      window.isEditing = true;
      window.editAdventureId = data.id;

      document.getElementById("save-label").textContent = "Update adventure";

      // Fill mushroom values
      document.querySelector('[name="chanterelles"]').value = data.types["Chanterelles"] ?? "";
      document.querySelector('[name="funnel"]').value = data.types["Funnel Chanterelles"] ?? "";
      document.querySelector('[name="boletus"]').value = data.types["Boletus"] ?? "";
      document.querySelector('[name="trumpets"]').value = data.types["Trumpets"] ?? "";

      // Other fields
      document.querySelector('[name="location"]').value = data.location || "";
      document.querySelector('[name="start_date"]').value = data.start_date;

      // Description (plain textarea)
      document.getElementById("adventure_text").value = data.adventure_text || "";

      // Existing images (edit mode)
      const preview = document.getElementById("image_preview");
      preview.innerHTML = "";
      if (data.photo_urls && data.photo_urls.length > 0) {
        data.photo_urls.forEach(url => addImagePreview(url, true));
      }
    };
  });

  window.isEditing = false;
  window.editAdventureId = null;

  /* -------------------------------------------------
   *  Image preview for multiple files
   * ------------------------------------------------- */
  const input = document.getElementById("mushroom_photos");
  const preview = document.getElementById("image_preview");

  const addImagePreview = (src, isExisting = false, fileObj = null) => {
    const div = document.createElement("div");
    div.classList.add("relative");
    div.innerHTML = `
      <img src="${src}" class="w-28 h-28 object-cover rounded-md border" />
      <button type="button" class="absolute top-0 right-0 bg-red-600 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center remove-btn">x</button>
    `;
    preview.appendChild(div);

    div.querySelector(".remove-btn").addEventListener("click", () => {
      div.remove();
      if (!isExisting && fileObj) {
        const dt = new DataTransfer();
        Array.from(input.files).filter(f => f !== fileObj).forEach(f => dt.items.add(f));
        input.files = dt.files;
      }
    });
  };

  if (input) {
    input.addEventListener("change", () => {
      Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => addImagePreview(e.target.result, false, file);
        reader.readAsDataURL(file);
      });
    });
  }

  /* -------------------------------------------------
   *  Submit form
   * ------------------------------------------------- */
  const form = document.getElementById("mushroom-form");
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const overlay = document.getElementById("saving-overlay");
    const spinner = document.getElementById("saving-spinner");
    const check = document.getElementById("saving-check");
    const message = document.getElementById("saving-message");

    overlay.classList.remove("hidden");
    spinner.classList.remove("hidden");
    check.classList.add("hidden");
    message.textContent = "Saving...";

    const formData = new FormData(e.target);

    // Adventure description (plain textarea now)
    const adventureText = formData.get("adventure_text") || "";

    const location = formData.get("location") || "";
    const startDate = formData.get("start_date");

    const normalize = (v) => v ? parseFloat(v.toString().replace(",", ".")) : 0;
    const mushrooms = {};
    const c = formData.get("chanterelles");
    const f = formData.get("funnel");
    const b = formData.get("boletus");
    const t = formData.get("trumpets");
    if (c) mushrooms["Chanterelles"] = normalize(c);
    if (f) mushrooms["Funnel Chanterelles"] = normalize(f);
    if (b) mushrooms["Boletus"] = normalize(b);
    if (t) mushrooms["Trumpets"] = normalize(t);

    if (Object.keys(mushrooms).length === 0) {
      alert("Please enter at least one mushroom type.");
      overlay.classList.add("hidden");
      return;
    }

    const sendData = new FormData();
    sendData.append("action", window.isEditing ? "update_mushroom" : "add_mushroom");
    if (window.isEditing) sendData.append("adventure_id", window.editAdventureId);

    sendData.append("types", JSON.stringify(mushrooms));
    sendData.append("start_date", startDate);
    sendData.append("location", location);
    sendData.append("adventure_text", adventureText);

    Array.from(input.files).forEach(file => {
      sendData.append("mushroom_photos[]", file);
    });

    try {
      const response = await fetch(ajaxurl, { method: "POST", body: sendData });
      const result = await response.json();

      if (!result.success) throw new Error(result.data || "Save failed");

      spinner.classList.add("hidden");
      check.classList.remove("hidden");
      message.textContent = "Saved!";

      setTimeout(() => {
        Alpine.store("modal").isOpen = false;
        e.target.reset();
        preview.innerHTML = "";
        input.value = "";
        window.isEditing = false;
        window.editAdventureId = null;
        document.getElementById("save-label").textContent = "Publish adventure";
        window.location.reload();
      }, 900);
    } catch (err) {
      alert("Error: " + err.message);
      overlay.classList.add("hidden");
    }
  });
});
