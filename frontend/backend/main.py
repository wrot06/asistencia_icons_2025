import os
from fastapi import FastAPI, Depends, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.orm import Session
from dotenv import load_dotenv

import database
import models
import schemas
import crud

ALLOWED_IP = "10.10.10.35"  # IP del frontend permitida

load_dotenv()
models.Base.metadata.create_all(bind=database.engine)

app = FastAPI(title="Asistencia Icons 2025")

app.add_middleware(
    CORSMiddleware,
    allow_origins=[f"http://{ALLOWED_IP}", f"https://{ALLOWED_IP}"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

def get_db():
    db = database.SessionLocal()
    try:
        yield db
    finally:
        db.close()

def verify_client_ip(request: Request):
    client_ip = request.client.host
    if client_ip != ALLOWED_IP:
        raise HTTPException(status_code=403, detail="Acceso no autorizado")
    return client_ip

# ---------------- Endpoints ---------------- #

@app.get("/sessions", response_model=list[schemas.SessionOut])
def list_sessions(request: Request, db: Session = Depends(get_db)):
    verify_client_ip(request)
    return crud.get_sessions(db)

@app.post("/register", response_model=schemas.AttendeeOut)
def register_attendee(attendee: schemas.AttendeeCreate, request: Request, db: Session = Depends(get_db)):
    verify_client_ip(request)
    session = crud.get_session(db, attendee.session_id)
    if not session:
        raise HTTPException(status_code=404, detail="Sesión no encontrada")
    ip = request.client.host
    return crud.create_attendee(db, attendee, ip)

@app.get("/current_session", response_model=schemas.SessionOut)
def current_session(request: Request, db: Session = Depends(get_db)):
    verify_client_ip(request)
    session = crud.get_current_session(db)
    if not session:
        raise HTTPException(status_code=404, detail="No hay sesiones activas en este momento")
    return session

# ---------------- Inicio del servidor ---------------- #
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
