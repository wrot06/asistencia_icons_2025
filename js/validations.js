// js/validations.js

// --- Expresiones regulares ---
const patterns = {
  nombre: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,100}$/,
  documento: /^[0-9]{5,15}$/,
  correo: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
  telefono: /^[0-9]{7,15}$/
};

// --- Función genérica de validación ---
export function validateInput(id, regex, message) {
  const input = document.getElementById(id);
  const field = input.closest(".field");
  let help = field.querySelector(".help");

  if (!help) {
    help = document.createElement("p");
    help.className = "help";
    field.appendChild(help);
  }

  if (!regex.test(input.value.trim())) {
    input.classList.add("is-danger");
    input.classList.remove("is-success");
    help.textContent = message;
    help.className = "help is-danger";
    return false;
  } else {
    input.classList.remove("is-danger");
    input.classList.add("is-success");
    help.textContent = "Dato válido";
    help.className = "help is-success";
    return true;
  }
}

// --- Validación completa del formulario ---
export function validateForm() {
  const nombreOk = validateInput("nombre_completo", patterns.nombre, "Ingrese un nombre válido (solo letras).");
  const docOk = validateInput("numero_documento", patterns.documento, "Ingrese un número de documento válido.");
  const correoOk = validateInput("correo_electronico", patterns.correo, "Ingrese un correo válido.");
  const telOk = validateInput("telefono", patterns.telefono, "Ingrese un teléfono válido.");

  const tipoDoc = document.getElementById("tipo_documento");
  const genero = document.getElementById("genero");

  if (!tipoDoc.value) {
    tipoDoc.classList.add("is-danger");
    return false;
  } else tipoDoc.classList.remove("is-danger");

  if (!genero.value) {
    genero.classList.add("is-danger");
    return false;
  } else genero.classList.remove("is-danger");

  return nombreOk && docOk && correoOk && telOk && tipoDoc.value && genero.value;
}

// --- Inicializa validaciones en tiempo real ---
export function initRealtimeValidation() {
  document.getElementById("nombre_completo").addEventListener("input", () =>
    validateInput("nombre_completo", patterns.nombre, "Ingrese un nombre válido (solo letras).")
  );
  document.getElementById("numero_documento").addEventListener("input", () =>
    validateInput("numero_documento", patterns.documento, "Ingrese un número de documento válido.")
  );
  document.getElementById("correo_electronico").addEventListener("input", () =>
    validateInput("correo_electronico", patterns.correo, "Ingrese un correo válido.")
  );
  document.getElementById("telefono").addEventListener("input", () =>
    validateInput("telefono", patterns.telefono, "Ingrese un teléfono válido.")
  );
}
