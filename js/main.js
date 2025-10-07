import { getSessions, registerAttendee } from "./api.js";

document.addEventListener("DOMContentLoaded", async () => {
  const sessionSelect = document.getElementById("sessionSelect");
  const registerBtn = document.getElementById("registerBtn");
  const responseMsg = document.getElementById("responseMsg");

  try {
    const sessions = await getSessions();
    sessionSelect.innerHTML = sessions.map(
      s => `<option value="${s.id}">${s.title}</option>`
    ).join("");
  } catch {
    responseMsg.textContent = "❌ Error al cargar las sesiones disponibles.";
    responseMsg.classList.add("has-text-danger");
  }

  registerBtn.addEventListener("click", async () => {
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const sessionId = sessionSelect.value;

    if (!name || !email) {
      responseMsg.textContent = "⚠️ Todos los campos son obligatorios.";
      responseMsg.classList.add("has-text-warning");
      return;
    }

    try {
      const result = await registerAttendee({
        name,
        email,
        session_id: sessionId
      });
      responseMsg.textContent = `✅ Registro exitoso: ${result.name}`;
      responseMsg.classList.remove("has-text-danger", "has-text-warning");
      responseMsg.classList.add("has-text-success");
    } catch {
      responseMsg.textContent = "❌ Error al registrar asistencia.";
      responseMsg.classList.add("has-text-danger");
    }
  });
});
