from sqlalchemy import Column, Integer, String, DateTime, ForeignKey, func
from sqlalchemy.orm import relationship
from database import Base

class Session(Base):
    __tablename__ = "sessions"

    id = Column(Integer, primary_key=True, index=True)
    session_name = Column(String(255), nullable=False)
    session_start = Column(DateTime, nullable=False)
    session_end = Column(DateTime, nullable=False)
    created_at = Column(DateTime, server_default=func.now())

    attendees = relationship("Attendee", back_populates="session")

class Attendee(Base):
    __tablename__ = "attendees"

    id = Column(Integer, primary_key=True, index=True)
    session_id = Column(Integer, ForeignKey("sessions.id"), nullable=False)
    full_name = Column(String(255), nullable=False)
    document_type = Column(String(100), nullable=False)
    document_number = Column(String(100), nullable=False)
    email = Column(String(255), nullable=False)
    phone = Column(String(50))
    gender = Column(String(50))
    registered_at = Column(DateTime, server_default=func.now())
    ip_address = Column(String(100))

    session = relationship("Session", back_populates="attendees")
