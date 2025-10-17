const API_BASE = "api_proxy.php?endpoint=";



export async function getSessions() {
  const response = await fetch(`${API_BASE}sessions`);
  if (!response.ok) throw new Error("Error al obtener las sesiones");
  return response.json();
}



export async function registerAttendee(data) {
  // Incluir los tres campos nuevos
  const payload = {
    full_name: data.full_name,
    document_type: data.document_type,
    document_number: data.document_number,
    email: data.email,
    phone: data.phone,
    gender: data.gender,
    organizacion: data.organizacion,            // <--- agregado
    ocupacion: data.ocupacion,                  // <--- agregado
    ciudad_de_procedencia: data.ciudad_de_procedencia, // <--- agregado
    session_id: data.session_id,
  };

  const response = await fetch(`${API_BASE}register`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json"
    },
    body: JSON.stringify(payload),
  });

  const text = await response.text();
  console.log("Respuesta del backend:", text, "Status:", response.status);

  if (!response.ok) {
    throw new Error(`Error al registrar la asistencia (${response.status}): ${text}`);
  }

  return JSON.parse(text);
}



