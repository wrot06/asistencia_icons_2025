document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.querySelectorAll(".tabs li");
  const tabContents = document.querySelectorAll(".tab-content");

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const target = tab.dataset.tab;

      // Desactivar todos los tabs
      tabs.forEach((t) => t.classList.remove("is-active"));
      tabContents.forEach((c) => c.classList.remove("is-active"));

      // Activar el tab actual
      tab.classList.add("is-active");
      document.getElementById(target).classList.add("is-active");
    });
  });
});

function mostrarDescripcion(id) {
  const modal = document.getElementById("modalDescripcion");
  const contenido = document.getElementById("contenidoDescripcion");
  contenido.innerHTML = descripciones[id] || "Sin descripción disponible.";
  modal.classList.add("is-active");
}

function cerrarModal() {
  document.getElementById("modalDescripcion").classList.remove("is-active");
}
