from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import base64
from google import genai
from google.genai import types
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)
CORS(app)

@app.route('/api/analyze-image', methods=['POST'])
def analyze():
    data = request.json
    if not data or 'image' not in data:
        return jsonify({"error": "No image data provided"}), 400
    
    prompt = data.get('message', 'What is in this image?')
    image_base64 = data.get('image')
    
    # Strip data:image/jpeg;base64, prefix if present
    if ',' in image_base64:
        image_base64 = image_base64.split(',')[1]

    try:
        api_key = os.getenv("GEMINI_API_KEY")
        client = genai.Client(api_key=api_key, http_options={'api_version': 'v1alpha'})

        response = client.models.generate_content(
            model="gemini-2.0-flash",
            contents=[
                types.Content(
                    parts=[
                        types.Part(text=prompt),
                        types.Part(
                            inline_data=types.Blob(
                                mime_type="image/jpeg",
                                data=base64.b64decode(image_base64),
                            ),
                            media_resolution={"level": "media_resolution_high"}
                        )
                    ]
                )
            ]
        )
        
        return jsonify({"reply": response.text})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    port = int(os.getenv("PORT", 5000))
    app.run(host='0.0.0.0', port=port, debug=True)
