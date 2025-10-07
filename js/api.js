const API_BASE = "http://10.10.10.35/asistencia/api_proxy.php?endpoint=";

export async function getSessions() {
  const response = await fetch(`${API_BASE}sessions`);
  if (!response.ok) throw new Error("Error al obtener las sesiones");
  return response.json();
}

export async function registerAttendee(data) {
  const response = await fetch(`${API_BASE}register`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });
  if (!response.ok) throw new Error("Error al registrar la asistencia");
  return response.json();
}
