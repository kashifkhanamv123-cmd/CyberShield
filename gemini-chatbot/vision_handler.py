import os
import base64
from google import genai
from google.genai import types
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

def analyze_image(image_path_or_base64, prompt="What is in this image?"):
    """
    Analyzes an image using Gemini 3.1 Pro Preview with high resolution.
    """
    api_key = os.getenv("GEMINI_API_KEY")
    if not api_key:
        raise ValueError("GEMINI_API_KEY not found in environment variables")

    client = genai.Client(api_key=api_key, http_options={'api_version': 'v1alpha'})

    # If it's a file path, read and encode
    if os.path.exists(image_path_or_base64):
        with open(image_path_or_base64, "rb") as image_file:
            image_data = base64.b64encode(image_file.read()).decode('utf-8')
            mime_type = "image/jpeg" # Default, could be improved
    else:
        # Assume it's already base64
        image_data = image_path_or_base64
        mime_type = "image/jpeg"

    response = client.models.generate_content(
        model="gemini-2.0-flash",
        contents=[
            types.Content(
                parts=[
                    types.Part(text=prompt),
                    types.Part(
                        inline_data=types.Blob(
                            mime_type=mime_type,
                            data=base64.b64decode(image_data),
                        ),
                        media_resolution={"level": "media_resolution_high"}
                    )
                ]
            )
        ]
    )

    return response.text

if __name__ == "__main__":
    # Example usage
    # print(analyze_image("path/to/image.jpg"))
    pass
