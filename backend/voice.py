import mimetypes
import struct
from google import genai
from google.genai import types
from dotenv import load_dotenv
load_dotenv()
import os
API_KEY = os.getenv("GEMINI_API_KEY")
def get_prompt(text, voice_type="mama"):
    if voice_type == "Mama nóng tính":
        return (
            "Bạn là một người mẹ nóng tính, thẳng thắn, nói chuyện to tiếng, "
            "nhưng rất quan tâm đến con cái. Bạn luôn muốn con mình phải rõ ràng trong việc thanh toán, "
            "chi tiêu, không lười nhác và không được chậm trễ khi quản lý tài chính. Khi phát hiện con cái "
            "tiêu xài linh tinh, chưa ghi lại các khoản chi, hoặc thanh toán chậm, bạn sẽ nhắc nhở bằng giọng "
            "“bà mẹ châu Á” – vừa mắng yêu vừa đốc thúc, đôi khi còn doạ cắt tiền tiêu vặt hoặc bắt ghi sổ chi tiết. "
            "Trả lời phải ngắn gọn, đanh thép, nhấn mạnh trách nhiệm và thói quen quản lý tài chính. Hãy nói câu sau đây:\n\n"
            + str(text)
        )
    elif voice_type == "Homie":
        return (
            "Bạn là một người bạn thân (homie), nói chuyện gần gũi, thoải mái, "
            "hơi lầy, pha chút hài hước, tư vấn tài chính và nhắc nhở nhẹ nhàng. "
            "Dùng từ ngữ trẻ trung, vui vẻ, phong cách đời thường. Hãy nói câu sau đây:\n\n"
            + str(text)
        )
    elif voice_type == "Trợ lý thông minh":
        return (
            "Bạn là một trợ lý AI thông minh, lịch sự, nhẹ nhàng, giải thích ngắn gọn, dễ hiểu, "
            "giúp nhắc nhở và quản lý thu chi khoa học, chuyên nghiệp. Hãy nói câu sau đây:\n\n"
            + str(text)
        )
    else:
        return str(text)

def parse_audio_mime_type(mime_type: str) -> dict:
    bits_per_sample = 16
    rate = 24000
    parts = mime_type.split(";")
    for param in parts:
        param = param.strip()
        if param.lower().startswith("rate="):
            try:
                rate = int(param.split("=")[1])
            except:
                pass
        elif param.startswith("audio/L"):
            try:
                bits_per_sample = int(param.split("L")[1])
            except:
                pass
    return {"bits_per_sample": bits_per_sample, "rate": rate}

def convert_to_wav(audio_data: bytes, mime_type: str) -> bytes:
    parameters = parse_audio_mime_type(mime_type)
    bits_per_sample = parameters["bits_per_sample"]
    sample_rate = parameters["rate"]
    num_channels = 1
    data_size = len(audio_data)
    bytes_per_sample = bits_per_sample // 8
    block_align = num_channels * bytes_per_sample
    byte_rate = sample_rate * block_align
    chunk_size = 36 + data_size

    header = struct.pack(
        "<4sI4s4sIHHIIHH4sI",
        b"RIFF",
        chunk_size,
        b"WAVE",
        b"fmt ",
        16,
        1,
        num_channels,
        sample_rate,
        byte_rate,
        block_align,
        bits_per_sample,
        b"data",
        data_size
    )
    return header + audio_data

def generate_voice(text: str, voice_type="mama") -> bytes:
    client = genai.Client(api_key=API_KEY)
    model = "gemini-2.5-flash-preview-tts"
    prompt = get_prompt(text, voice_type)
    print(prompt)
    contents = [
        types.Content(
            role="user",
            parts=[
                types.Part.from_text(text=prompt),
            ],
        ),
    ]

    generate_content_config = types.GenerateContentConfig(
        temperature=1,
        response_modalities=["audio"],
        speech_config=types.SpeechConfig(
            voice_config=types.VoiceConfig(
                prebuilt_voice_config=types.PrebuiltVoiceConfig(voice_name="Zephyr")
            )
        ),
    )

    for chunk in client.models.generate_content_stream(
        model=model,
        contents=contents,
        config=generate_content_config,
    ):
        if (
            chunk.candidates is None
            or chunk.candidates[0].content is None
            or chunk.candidates[0].content.parts is None
        ):
            continue
        if chunk.candidates[0].content.parts[0].inline_data:
            inline_data = chunk.candidates[0].content.parts[0].inline_data
            data_buffer = inline_data.data
            file_extension = mimetypes.guess_extension(inline_data.mime_type)
            if file_extension is None or not file_extension.startswith(".wav"):
                data_buffer = convert_to_wav(inline_data.data, inline_data.mime_type)
            return data_buffer
    raise Exception("Không tạo được voice!")
def generate_voice_stream(text: str, voice_type="Mama nóng tính"):
    client = genai.Client(api_key=API_KEY)
    model = "gemini-2.5-flash-preview-tts"
    prompt = get_prompt(text, voice_type)
    contents = [
        types.Content(
            role="user",
            parts=[
                types.Part.from_text(text=prompt),
            ],
        ),
    ]

    generate_content_config = types.GenerateContentConfig(
        temperature=1,
        response_modalities=["audio"],
        speech_config=types.SpeechConfig(
            voice_config=types.VoiceConfig(
                prebuilt_voice_config=types.PrebuiltVoiceConfig(voice_name="Zephyr")
            )
        ),
    )

    # Để đúng chuẩn WAV: cần header + stream chunk audio (Google trả về từng chunk thô)
    wav_header = None
    yielded_any = False

    for chunk in client.models.generate_content_stream(
        model=model,
        contents=contents,
        config=generate_content_config,
    ):
        if (
            chunk.candidates is None
            or chunk.candidates[0].content is None
            or chunk.candidates[0].content.parts is None       
        ):
            continue

        inline_data = chunk.candidates[0].content.parts[0].inline_data
        if inline_data:
            audio_data = inline_data.data
            mime_type = inline_data.mime_type
            # Sinh header khi lần đầu
            if not yielded_any:
                wav_full = convert_to_wav(audio_data, mime_type)
                wav_header = wav_full[:44]  # WAV header fixed 44 bytes
                yield wav_header
                yield wav_full[44:]  # data phần đầu tiên
                yielded_any = True
            else:
                yield audio_data  # chỉ data
    # Nếu chưa có chunk nào:
    if not yielded_any:
        raise Exception("Không tạo được voice!")
