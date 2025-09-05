// Sidebar toggle
const sidebar = document.getElementById("mySidebar");
const overlay = document.getElementById("overlay");
const openSidebar = document.getElementById("openSidebar");
const closeSidebar = document.getElementById("closeSidebar");

// Buka sidebar
openSidebar.addEventListener("click", () => {
  sidebar.classList.add("show");
  overlay.classList.add("show");
});

// Tutup sidebar (X button)
closeSidebar.addEventListener("click", () => {
  sidebar.classList.remove("show");
  overlay.classList.remove("show");
});

// Tutup sidebar kalau klik overlay
overlay.addEventListener("click", () => {
  sidebar.classList.remove("show");
  overlay.classList.remove("show");
});
