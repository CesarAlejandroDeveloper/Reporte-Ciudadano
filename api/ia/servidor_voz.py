# Ruta: C:\xampp\htdocs\api\ia\servidor_voz.py
import os
import uuid
import subprocess
from fastapi import FastAPI, UploadFile, File
from pydantic import BaseModel
from faster_whisper import WhisperModel
import edge_tts

app = FastAPI(title="Servicio de Voz Respondin")

CARPETA_UPLOADS = os.path.join(os.path.dirname(__file__), "uploads")
os.makedirs(CARPETA_UPLOADS, exist_ok=True)

print("Cargando modelo de voz Whisper...")
modelo_stt = WhisperModel("base", device="cpu", compute_type="int8")
VOZ_TTS = "es-MX-DaliaNeural"
print("¡Modelo de voz listo!")

class SolicitudSTTLocal(BaseModel):
    ruta: str

class SolicitudTTS(BaseModel):
    texto: str

def convertir_y_transcribir(ruta_entrada: str) -> str:
    ruta_wav = os.path.join(CARPETA_UPLOADS, f"conv_{uuid.uuid4().hex[:8]}.wav")
    try:
        # Normalizar cualquier audio (.webm de navegador o .ogg de WhatsApp) a WAV 16kHz mono con FFmpeg
        subprocess.run(
            ["ffmpeg", "-y", "-i", ruta_entrada, "-ar", "16000", "-ac", "1", ruta_wav],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            check=False
        )
        archivo_a_leer = ruta_wav if os.path.exists(ruta_wav) and os.path.getsize(ruta_wav) > 100 else ruta_entrada
        segmentos, _ = modelo_stt.transcribe(archivo_a_leer, language="es", beam_size=5)
        texto = " ".join([seg.text.strip() for seg in segmentos]).strip()
        print(f"[STT] Texto escuchado: '{texto}'")
        return texto
    finally:
        if os.path.exists(ruta_wav):
            try:
                os.remove(ruta_wav)
            except OSError:
                pass

@app.get("/")
async def estado():
    return {"ok": True, "servicio": "Servidor de Voz Respondin activo"}

@app.post("/stt_local")
async def stt_local(req: SolicitudSTTLocal):
    if not os.path.exists(req.ruta):
        print(f"[STT Error] No existe el archivo: {req.ruta}")
        return {"ok": False, "error": "Archivo no encontrado", "texto": ""}
    try:
        texto = convertir_y_transcribir(req.ruta)
        return {"ok": True, "texto": texto}
    except Exception as e:
        print(f"[STT Error]: {e}")
        return {"ok": False, "error": str(e), "texto": ""}

@app.post("/stt")
async def speech_to_text(audio: UploadFile = File(...)):
    ext = os.path.splitext(audio.filename or "audio.webm")[1] or ".webm"
    ruta_temp = os.path.join(CARPETA_UPLOADS, f"temp_{uuid.uuid4().hex}{ext}")
    with open(ruta_temp, "wb") as f:
        f.write(await audio.read())
    try:
        texto = convertir_y_transcribir(ruta_temp)
        return {"ok": True, "texto": texto}
    except Exception as e:
        print(f"[STT Error]: {e}")
        return {"ok": False, "error": str(e), "texto": ""}
    finally:
        if os.path.exists(ruta_temp):
            try:
                os.remove(ruta_temp)
            except OSError:
                pass

@app.post("/tts")
async def text_to_speech(req: SolicitudTTS):
    nombre_mp3 = f"tts_{uuid.uuid4().hex[:10]}.mp3"
    ruta_mp3 = os.path.join(CARPETA_UPLOADS, nombre_mp3)
    try:
        texto_limpio = req.texto.replace("*", "").replace("#", "").strip()
        comunicador = edge_tts.Communicate(texto_limpio, VOZ_TTS)
        await comunicador.save(ruta_mp3)
        print(f"[TTS] Audio generado: {nombre_mp3}")
        return {
            "ok": True,
            "archivo": nombre_mp3,
            "url_relativa": f"api/ia/uploads/{nombre_mp3}"
        }
    except Exception as e:
        print(f"[TTS Error]: {e}")
        return {"ok": False, "error": str(e)}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=5005)