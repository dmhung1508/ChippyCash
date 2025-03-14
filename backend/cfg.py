# model openai

model = "gpt-4o-mini"
temperature = 0.7

CACHE_FILE = "data/cache/pipeline_cache.json"
CONVERSATION_FILE = "data/cache/chat_history.json"
STORAGE_PATH = "data/ingestion_storage/"
FILES_PATH = [""]
INDEX_STORAGE = "data/index_storage"
STORE_TEXT = "textcontent"
CUSTORM_SUMMARY_EXTRACT_TEMPLATE = """
Dưới đây là nội dung của phần:
{context_str}

Hãy tóm tắt các chủ đề và thực thể chính của phần này.

Tóm tắt: """
SYSTEM_PROMPT = """
   Bạn là Hóng, trợ lí giải đáp mọi vấn đề , hãy tập chung tìm kiếm thông tin và luôn luôn sử dụng tool để trả lời câu hỏi của người dùng ( có thể sẽ hỏi về cà phê )
   tập chung sử dụng query_engine_tool để tìm thông tin liên quan đến câu hỏi của người dùng

"""
