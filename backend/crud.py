from datetime import datetime
from zoneinfo import ZoneInfo
from sqlalchemy.orm import Session
import models

def get_sessions(db: Session):
    return db.query(models.Session).all()

def get_session(db: Session, session_id: int):
    return db.query(models.Session).filter(models.Session.id == session_id).first()

def create_attendee(db: Session, attendee, ip: str):
    new_attendee = models.Attendee(
        name=attendee.name,
        email=attendee.email,
        document=attendee.document,
        session_id=attendee.session_id,
        ip=ip,
        timestamp=datetime.now(tz=ZoneInfo("America/Bogota"))
    )
    db.add(new_attendee)
    db.commit()
    db.refresh(new_attendee)
    return new_attendee

def get_current_session(db: Session):
    """Devuelve la sesión activa según la hora actual en Colombia."""
    now = datetime.now(tz=ZoneInfo("America/Bogota"))
    return db.query(models.Session).filter(
        models.Session.session_start <= now,
        models.Session.session_end >= now
    ).first()
