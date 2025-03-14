import requests
import re
import openai
from cfg import INDEX_STORAGE, STORAGE_PATH, CACHE_FILE
import cfg
import os

from llama_index.core import SimpleDirectoryReader, StorageContext, VectorStoreIndex, load_index_from_storage, Settings
from llama_index.core.ingestion import IngestionPipeline, IngestionCache
from llama_index.core.node_parser import TokenTextSplitter
from llama_index.embeddings.openai import OpenAIEmbedding
from llama_index.llms.openai import OpenAI

openai.api_key = os.getenv("OPENAI_API_KEY")
Settings.llm = OpenAI(model=cfg.model, temperature=cfg.temperature)



def create_node_telegram(id, file_paths):
    documents = SimpleDirectoryReader(input_files=file_paths, filename_as_id=True).load_data()
    pipeline = IngestionPipeline(
            transformations=[
                TokenTextSplitter(chunk_size=512, chunk_overlap=20),
                OpenAIEmbedding(model="text-embedding-3-large"),
            ],

        )
    nodes = pipeline.run(documents=documents)
    try:
        storage_context = StorageContext.from_defaults(persist_dir=f"db_store/{id}")
    except:
        storage_context = StorageContext.from_defaults()
    vector_index = VectorStoreIndex(nodes, storage_context=storage_context)
    vector_index.set_index_id(str(id))
    storage_context.persist(persist_dir=f"db_store/{id}")
    return vector_index

def create_or_update_node_telegram(id, file_paths):
    documents = SimpleDirectoryReader(input_files=file_paths, filename_as_id=True).load_data()
    pipeline = IngestionPipeline(
        transformations=[
            TokenTextSplitter(chunk_size=512, chunk_overlap=20),
            OpenAIEmbedding(model="text-embedding-3-large"),
        ]
    )
    
    new_nodes = pipeline.run(documents=documents)
    
    try:
        storage_context = StorageContext.from_defaults(persist_dir=f"db_store/{id}")
        vector_index = load_index_from_storage(storage_context)
        print(f"Existing index found for ID {id}, adding new nodes.")
        

        vector_index.insert_nodes(new_nodes)
    except Exception as e:
        print(f"No existing index found for ID {id} or an error occurred: {e}. Creating a new one.")
        # If no existing index, create a new one
        storage_context = StorageContext.from_defaults()
        vector_index = VectorStoreIndex(new_nodes, storage_context=storage_context)
    
    # Set the index ID and persist the updated index
    vector_index.set_index_id(str(id))
    storage_context.persist(persist_dir=f"db_store/{id}")
    
    print(f"Index for ID {id} created/updated successfully.")
    return vector_index





def ingest_documents(idd):
    file_path = f"{cfg.STORE_TEXT}/{idd}.txt"
    documents = SimpleDirectoryReader(
        input_files=[file_path],
        filename_as_id = True
    ).load_data()
    for doc in documents:
        print(doc.id_)

    # try:
    #     cached_hashes = IngestionCache.from_persist_path(
    #         CACHE_FILE
    #         )
    #     print("Cache file found. Running using cache...")
    # except:
    #     cached_hashes = ""
    #     print("No cache file found. Running without cache...")
    pipeline = IngestionPipeline(
        transformations=[
            TokenTextSplitter(
                chunk_size=512,
                chunk_overlap=20
            ),
            #SummaryExtractor(summaries=['self'], prompt_template=CUSTORM_SUMMARY_EXTRACT_TEMPLATE),
            OpenAIEmbedding(
                model="text-embedding-3-large",
            )
        ],
        # cache=cached_hashes
    )

    nodes = pipeline.run(documents=documents)
    # pipeline.cache.persist(CACHE_FILE)

    return nodes
def build_indexes(nodes,idd):
    try:
        storage_context = StorageContext.from_defaults(
            persist_dir=f"db_store/{idd}"
        )
        vector_index = load_index_from_storage(
            storage_context, index_id=str(idd)
        )
        print("All indices loaded from storage.")
    except Exception as e:
        print(f"Error occurred while loading indices: {e}")
        storage_context = StorageContext.from_defaults()
        vector_index = VectorStoreIndex(
            nodes, storage_context=storage_context
        )
        vector_index.set_index_id(str(idd))
        storage_context.persist(
            persist_dir=f"db_store/{idd}"
        )
        print("New indexes created and persisted.")
    add_id(idd)
    return vector_index
