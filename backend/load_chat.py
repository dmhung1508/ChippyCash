import nest_asyncio
import openai
import os
import json
import datetime
from datetime import datetime
from utils import get_prompt
from llama_index.core import (
    load_index_from_storage,
    StorageContext,
    Settings
)
from llama_index.embeddings.openai import OpenAIEmbedding
from llama_index.llms.openai import OpenAI
from llama_index.core.memory import ChatMemoryBuffer
from llama_index.core.tools import QueryEngineTool, FunctionTool
from llama_index.agent.openai import OpenAIAgent
from llama_index.core.agent import ReActAgent
from llama_index.core.storage.chat_store import SimpleChatStore

from cfg import (
    SYSTEM_PROMPT,
    model,
    temperature
)



# Set OpenAI API key and model settings
openai.api_key = os.getenv("OPENAI_API_KEY")
Settings.llm = OpenAI(model=model, temperature=temperature)
embed_model = OpenAIEmbedding(model="text-embedding-3-large", api_key=os.getenv("OPENAI_API_KEY"))
Settings.embed_model = embed_model
def get_date_time()->str:
    """Get current date and time

    Returns:
        str: Current date and time
    """
    now = datetime.now()
    return now.strftime("%Y-%m-%d %H:%M:%S")

def add(x: int, y: int) -> int:
    """Useful function to add two numbers."""
    return x + y


def multiply(x: int, y: int) -> int:
    """Useful function to multiply two numbers."""
    return x * y
def subtract(x: int, y: int) -> int:
    """Useful function to subtract two numbers."""
    return x - y
def divide(x: int, y: int) -> int:
    """Useful function to divide two numbers."""
    return x / y

def load_chat_store_user(idd):
    if os.path.exists(f"db_chat/{idd}/chat_history.json") and os.path.getsize(f"db_chat/{idd}/chat_history.json") > 0:
        try:
            chat_store = SimpleChatStore.from_persist_path(f"db_chat/{idd}/chat_history.json")
        except json.JSONDecodeError:
            chat_store = SimpleChatStore()
    else:
        chat_store = SimpleChatStore()
    return chat_store
def save_income_expense(id_user, name, amount):
    """Lưu dữ liệu thu nhập của người dùng vào trong file khi có thông tin thu nhập, chỉ có trong các loại thu nhập đã được định nghĩa trước đó, không cho phép người dùng tự định nghĩa loại thu nhập
    (lương, tiền thưởng, tiền bán hàng, ...)

    Args:
        id_user (string): id của người dùng
        name (string): tên khoản thu nhập (lương, tiền thưởng, tiền bán hàng, ...)
        amount (int): số tiền
        """
    user_dir = f"db_store/{id_user}"
    if not os.path.exists(user_dir):
        os.makedirs(user_dir)
    if os.path.exists(f"{user_dir}/bill.json"):
        with open(f"{user_dir}/bill.json", "r") as f:
            data = json.load(f)
    else:
        data = []
    new_entry = {
        "type": "income",
        "name": name,
        "amount": amount,
    }
    data.append(new_entry)
    with open(f"{user_dir}/bill.json", "w") as f:
        json.dump(data, f)
def save_outcome_expense(id_user, name, amount):
    """Lưu dữ liệu chi tiêu của người dùng vào trong file khi có thông tin chi tiêu, chỉ có trong các loại chi tiêu đã được định nghĩa trước đó, không cho phép người dùng tự định nghĩa loại chi tiêu
    (tiền ăn uống, tiền đi lại, tiền học tập, ...)

    Args:
        id_user (string): id của người dùng
        name (string): tên khoản chi tiêu (tiền ăn uống, tiền đi lại, tiền học tập, ...)
        amount (int): số tiền
    """
    user_dir = f"db_store/{id_user}"
    if not os.path.exists(user_dir):
        os.makedirs(user_dir)
    if os.path.exists(f"{user_dir}/bill.json"):
        with open(f"{user_dir}/bill.json", "r") as f:
            data = json.load(f)
    else:
        data = []
    new_entry = {
        "type": "outcome",
        "name": name,
        "amount": amount,
    }
    data.append(new_entry)
    with open(f"{user_dir}/bill.json", "w") as f:
        json.dump(data, f)

def load_bill(id_user):
    """Load bill của người dùng từ file

    Args:
        id_user (string): id của người dùng

    Returns:
        list: Danh sách các bill của người dùng
    """
    if os.path.exists(f"db_store/{id_user}/bill.json") and os.path.getsize(f"db_store/{id_user}/bill.json") > 0:
        with open(f"db_store/{id_user}/bill.json", "r") as f:
            data = json.load(f)
    else:
        data = []
    return data
def initialize_chatbot_user(chat_store, id_user, role):
    get_date_time_tool = FunctionTool.from_defaults( fn = get_date_time )
    load_bill_tool = FunctionTool.from_defaults( fn = load_bill )
    save_income_expense_tool = FunctionTool.from_defaults( fn = save_income_expense )
    save_outcome_expense_tool = FunctionTool.from_defaults( fn = save_outcome_expense )


    memory = ChatMemoryBuffer.from_defaults(
            token_limit=5000,
            chat_store=chat_store,
            chat_store_key=id_user
        )

    system_prompt = get_prompt(id_user, role)
    print(system_prompt)
    agent = OpenAIAgent.from_tools(
        tools=[
            # tool_coffee,
            # tool_drink,
            FunctionTool.from_defaults(add),
            FunctionTool.from_defaults(multiply),
            FunctionTool.from_defaults(subtract),
            FunctionTool.from_defaults(divide),
            get_date_time_tool,
            load_bill_tool,
            save_income_expense_tool,
            save_outcome_expense_tool
        ],
        memory=memory,
        system_prompt=system_prompt,
        verbose=True,
    
    
    )

    return agent
  
def chat_interface(agent, chat_store,prompt, id_user):
    if os.path.exists(f"db_store/{id_user}"):
        response = str(agent.chat(prompt))
    else:
        response = str(agent.chat(prompt))

    chat_store.persist(f"db_chat/{agent.memory.chat_store_key}/chat_history.json")
    return response
