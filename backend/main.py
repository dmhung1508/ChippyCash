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
class Delete(BaseModel):
    id_user: str

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
        agent = initialize_chatbot_user(chat_store, chat.id_user)
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
