from pydantic import BaseModel, EmailStr
from typing import Optional
from datetime import datetime

class AttendeeCreate(BaseModel):
    session_id: int
    full_name: str
    document_type: str
    document_number: str
    email: EmailStr
    phone: Optional[str] = None
    gender: Optional[str] = None

class AttendeeOut(BaseModel):
    id: int
    session_id: int
    full_name: str
    document_type: str
    document_number: str
    email: EmailStr
    phone: Optional[str]
    gender: Optional[str]
    registered_at: datetime

    class Config:
        orm_mode = True

class SessionCreate(BaseModel):
    session_name: str
    session_start: datetime
    session_end: datetime

class SessionOut(BaseModel):
    id: int
    session_name: str
    session_start: datetime
    session_end: datetime

    class Config:
        orm_mode = True
