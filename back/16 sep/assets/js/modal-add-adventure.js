
document.addEventListener("DOMContentLoaded", () => {
  // Alpine modal store
  document.addEventListener("alpine:init", () => {
    Alpine.store("modal", { isOpen: false });
  });

  const form = document.getElementById("mushroom-form");
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const savingOverlay = document.getElementById("saving-overlay");
    const savingSpinner = document.getElementById("saving-spinner");
    const savingCheck = document.getElementById("saving-check");
    const savingMessage = document.getElementById("saving-message");

    savingOverlay.classList.remove("hidden");
    savingSpinner.classList.remove("hidden");
    savingCheck.classList.add("hidden");
    savingMessage.textContent = "Saving...";

    const formData = new FormData(e.target);
    const location = formData.get("location");
    const photoFile = formData.get("mushroom_photo");
    const startDate = formData.get("start_date");

    // Collect mushrooms
    const mushrooms = {};
    if (formData.get("chanterelles"))
      mushrooms["Chanterelles"] = parseFloat(formData.get("chanterelles"));
    if (formData.get("funnel"))
      mushrooms["Funnel Chanterelles"] = parseFloat(formData.get("funnel"));
    if (formData.get("boletus"))
      mushrooms["Boletus"] = parseFloat(formData.get("boletus"));
    if (formData.get("trumpets"))
      mushrooms["Trumpets"] = parseFloat(formData.get("trumpets"));

    if (Object.keys(mushrooms).length === 0) {
      alert("Please enter at least one mushroom type and amount.");
      savingOverlay.classList.add("hidden");
      return;
    }

    const mushroomData = new FormData();
    mushroomData.append("action", "add_mushroom");
    mushroomData.append("types", JSON.stringify(mushrooms));
    mushroomData.append("start_date", startDate);
    mushroomData.append("location", location);

    if (photoFile && photoFile.size > 0) {
      mushroomData.append("mushroom_photo", photoFile);
    }

    try {
      const response = await fetch(ajaxurl, {
        method: "POST",
        body: mushroomData,
      });

      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result.data || "Unknown error occurred.");
      }

      savingSpinner.classList.add("hidden");
      savingCheck.classList.remove("hidden");
      savingMessage.textContent = "Saved!";

      setTimeout(() => {
        Alpine.store("modal").isOpen = false;
        e.target.reset();
        window.location.reload();
      }, 1000);
    } catch (error) {
      console.error(error);
      alert("Error saving mushroom(s): " + error.message);
      savingOverlay.classList.add("hidden");
    }
  });
});

