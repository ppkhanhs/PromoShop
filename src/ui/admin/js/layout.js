let navLinks = [];

export async function loadLayout() {
  await Promise.all([injectHeader(), injectFooter()]);
  setupNavigation();
}

async function injectHeader() {
  const headerHost = document.getElementById("adminHeader");
  if (!headerHost) return;
  const res = await fetch("/admin/partials/admin-header.html");
  headerHost.innerHTML = res.ok ? await res.text() : "";
}

async function injectFooter() {
  const footerHost = document.getElementById("adminFooter");
  if (!footerHost) return;
  const res = await fetch("/admin/partials/admin-footer.html");
  footerHost.innerHTML = res.ok ? await res.text() : "";
}

function setupNavigation() {
  navLinks = Array.from(document.querySelectorAll(".admin-sidebar a"));
  navLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
      const target = link.dataset.section;
      if (target) {
        event.preventDefault();
        showSection(target);
      }
    });
  });

  const currentPath = window.location.pathname;
  if (!navLinks.some((link) => link.dataset.section)) {
    navLinks.forEach((link) => {
      if (link.getAttribute("href") === currentPath) {
        navLinks.forEach((l) => l.classList.remove("active"));
        link.classList.add("active");
      }
    });
  }
}

export function showSection(id) {
  const sections = document.querySelectorAll(".admin-section");
  sections.forEach((section) => {
    section.classList.toggle("active", section.id === id);
  });

  navLinks.forEach((link) => {
    link.classList.toggle("active", link.dataset.section === id);
  });
}
