let navLinks = [];

export async function loadLayout() {
  setupNavigation();
}

function setupNavigation() {
  navLinks = Array.from(document.querySelectorAll(".admin-sidebar a"));
  navLinks.forEach((link) => {
    const target = link.dataset.section;
    if (target) {
      link.addEventListener("click", (event) => {
        event.preventDefault();
        showSection(target);
      });
    }
  });

  const currentPath = window.location.pathname;
  if (currentPath) {
    setActiveNavByPath(currentPath);
  }
}

function setActiveNavByPath(pathname) {
  if (!navLinks.length) return;
  navLinks.forEach((link) => {
    const href = link.getAttribute("href");
    link.classList.toggle("active", href === pathname);
  });
}

export function setActiveNav(navKey) {
  if (!navLinks.length) {
    navLinks = Array.from(document.querySelectorAll(".admin-sidebar a"));
  }
  if (!navLinks.length) return;

  navLinks.forEach((link) => {
    const key = link.dataset.nav || link.dataset.section || "";
    if (navKey) {
      link.classList.toggle("active", key === navKey);
    } else {
      const href = link.getAttribute("href");
      link.classList.toggle("active", href === window.location.pathname);
    }
  });
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
