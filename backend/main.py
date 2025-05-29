from cfg import *
from dotenv import load_dotenv
load_dotenv()
from fastapi import FastAPI, File, UploadFile, Depends, Form
from pydantic import BaseModel, validator
import uvicorn
from openai import OpenAI
from typing import Optional, List
import os,json
import aiohttp
import tempfile
import shutil
from load_chat import load_chat_store_user, initialize_chatbot_user, chat_interface
from fastapi.middleware.cors import CORSMiddleware
import openai
from openai import OpenAI
from openai import Client
import base64
from fastapi import HTTPException
from fastapi.responses import JSONResponse
from voice import generate_voice, generate_voice_stream
from fastapi.responses import StreamingResponse, JSONResponse
from MBBank.mbbank.main import getBank
client = Client(
    api_key=os.getenv("OPENAI_API_KEY"),
)


app = FastAPI(
    title="Chatbot API",
    description="API for chatbot",
    version="0.1",
    path = "/"
)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Cho phép tất cả origins trong môi trường dev
    # Hoặc chỉ định cụ thể:
    # allow_origins=["https://72c3-14-224-131-219.ngrok-free.app"],
    allow_credentials=True,
    allow_methods=["*"],  # Cho phép tất cả methods (GET, POST, etc.)
    allow_headers=["*"],  # Cho phép tất cả headers
)
class Chat(BaseModel):
    id_user: str
    message: str
    role: Optional[str] = "trợ lý thông minh"
class Delete(BaseModel):
    id_user: str
class ImageAnalysisRequest(BaseModel):
    image_base64: str
class TextRequest(BaseModel):
    text: str
    voice_type: str = "mama"   # "assistant", "mama", "homie"

@app.post("/voice")
async def voice(request: TextRequest):
    try:
        wav_bytes = generate_voice(request.text, request.voice_type)
        base64_wav = base64.b64encode(wav_bytes).decode("utf-8")
        return JSONResponse({"audio_base64": base64_wav})
    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})

@app.post("/voice/download")
async def voice_download(request: TextRequest):
    wav_bytes = generate_voice(request.text, request.voice_type)
    filename = f"voice_{request.voice_type}.wav"
    return StreamingResponse(io.BytesIO(wav_bytes), media_type="audio/wav", headers={
        "Content-Disposition": f"attachment; filename={filename}"
    })
@app.post("/voice/stream")
async def stream_voice(request: TextRequest):
    generator = generate_voice_stream(request.text, request.voice_type)
    # Response với media_type là "audio/wav", play trực tiếp luôn
    return StreamingResponse(generator, media_type="audio/wav")
@app.post("/analyze-bill")
async def analyze_bill(
    file: UploadFile = File(...),
    input_text: str = Form(...)
):
    # Kiểm tra loại file
    if file.content_type not in ["image/jpeg", "image/png"]:
        raise HTTPException(status_code=400, detail="Chỉ hỗ trợ file JPEG hoặc PNG.")

    # Đọc dữ liệu file và encode base64
    file_bytes = await file.read()
    image_base64 = base64.b64encode(file_bytes).decode("utf-8")

    # Gửi request đến OpenAI
    response = client.responses.create(
        model="gpt-4.1-mini",
        input=[
            {
                "role": "system",
                "content": [
                    {
                        "type": "input_text",
                        "text": (
                            "Extract information from an uploaded image of a payment bill to create an output in the format \"(product) - (amount)\".\n\n"
                            "- Analyze the image to identify and extract relevant text, specifically focusing on identifying product names and the associated amounts.\n"
                            "- Ensure accuracy in transcription from the image to text.\n"
                            "- Output the extracted data in the specified format for each item present in the bill.\n\n"
                            "# Output Format\n\n"
                            "The output should be a list of each product and its corresponding amount in the format: \"(product) - (amount)\". Each product should be on a new line.\n\n"
                            "# Notes\n\n"
                            "- Ensure to accurately capture the product names and associated amounts despite potential variations in layout or language on the bill.\n"
                            "- Handle possible discrepancies like poor image quality or unclear text within the bill when extracting information."
                        )
                    }
                ]
            },
            {
                "role": "user",
                "content": [
                    {
                        "type": "input_image",
                        "image_url": f"data:{file.content_type};base64,{image_base64}"
                    },
                    {
                        "type": "input_text",
                        "text": input_text  # Dùng input_text user gửi vào
                    }
                ]
            }
        ],
        text={
            "format": {
                "type": "text"
            }
        },
        reasoning={},
        tools=[],
        temperature=1,
        max_output_tokens=2048,
        top_p=1,
        store=True
    )


    # Trả kết quả
    # Extract the text content from the response
    output_text = response.output[0].content[0].text
    print(output_text)

    # Create a directory for storing bill information if it doesn't exist
    return {"output_text": output_text}
@app.get("/")
def read_root():
    return "USE POST"
class CreateDB:
    def __init__(self, id_user: str = Form(...), session_id: str = Form(...)):
        self.id_user = id_user
        self.session_id = session_id


# @app.post("/upload")
# async def upload_and_process_files(
#     id_user: str = Form(...), 
#     file_links: List[str] = Form(...)
# ):
#     temp_dir = tempfile.mkdtemp()
#     try:
#         file_paths = []
        
#         for file_link in file_links:
#             file_name = file_link.split("/")[-1]
#             file_path = os.path.join(temp_dir, file_name)
            
#             async with aiohttp.ClientSession() as session:
#                 async with session.get(file_link) as response:
#                     if response.status == 200:
#                         with open(file_path, "wb") as f:
#                             f.write(await response.read())  # Save the file
#                         file_paths.append(file_path)
#                     else:
#                         return {"message": f"Failed to download file from {file_link}"}

#         vector_index = create_or_update_node_telegram(id_user, file_paths)

#         return {"message": "Files processed successfully"}
    
#     finally:
#         # Clean up: remove the temporary directory
#         shutil.rmtree(temp_dir)
@app.get("/update")
async def update():
    return {"message": "Updated"}
def load_bill_store_user(id_user):
    if os.path.exists(f"db_store/{id_user}/bill.json"):
        with open(f"db_store/{id_user}/bill.json", "r") as f:
            bill_store = json.load(f)
    else:
        bill_store =[]
    return bill_store



@app.post("/chat")
async def chat(
    chat: Chat
):
    try:
        chat_store = load_chat_store_user(chat.id_user)
        agent = initialize_chatbot_user(chat_store, chat.id_user, chat.role)
        response = chat_interface(agent, chat_store, chat.message, chat.id_user)

        return {
            "message": response,
            "bill": load_bill_store_user(chat.id_user)
        }
    except Exception as e:
        return {
            "message": str(e),
            "bill": {}
        }
@app.post("/delete")
async def delete(
    delete: Delete
):
    try:
        print(f"Delete {delete.id_user}")
        if os.path.exists(f"db_store/{delete.id_user}"):
            shutil.rmtree(f"db_store/{delete.id_user}")
        if os.path.exists(f"db_chat/{delete.id_user}"):
            shutil.rmtree(f"db_chat/{delete.id_user}")
        return {"message": "Deleted"}

    except Exception as e:
        return {"message": str(e)}

if __name__ == "__main__":
    uvicorn.run("main:app", port=8506, reload=True)

@app.get("/history")
async def get_history(
    id_user: str
):
    try:
        if os.path.exists(f"db_chat/{id_user}/chat_history.json"):
            with open(f"db_chat/{id_user}/chat_history.json", "r") as f:
                chat_history = json.load(f)
        else:
            chat_history = []
        return {
            "history": chat_history,
        }
    except Exception as e:
        return {
            "message": str(e),
            "history": {}
        }

@app.get("/bank")
async def get_bank(
    username: str,
    password: str,
    proxy: str = None
):
    return getBank(username, password, proxy)


