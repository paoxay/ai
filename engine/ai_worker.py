# engine/ai_worker.py (Nano Banana Pro Version)
import sys
import json
import base64
import os
import time
import requests
from dotenv import load_dotenv
from PIL import Image, ImageDraw, ImageFont

# ຕັ້ງຄ່າ Encoding
sys.stdout.reconfigure(encoding='utf-8')

# ໂຫລດ API Key
current_dir = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(current_dir, '../.env'))
KIE_API_KEY = os.getenv("KIE_API_KEY")

def process_image():
    try:
        # 1. ຮັບຂໍ້ມູນ (Base64)
        if len(sys.argv) < 2:
            raise Exception("No data received")
        
        try:
            input_base64 = sys.argv[1]
            json_str = base64.b64decode(input_base64).decode('utf-8')
            data = json.loads(json_str)
        except:
            data = json.loads(sys.argv[1])

        prompt = data.get('system_prompt', '')
        text_config = data.get('text_config', {})
        user_texts = data.get('user_texts', {})
        logo_path = data.get('logo_path')
        aspect_ratio = data.get('aspect_ratio', '1:1')

        # ======================================================
        # 🍌 ຂັ້ນຕອນທີ 1: ສ້າງ Task (Create Task)
        # ======================================================
        create_url = "https://api.kie.ai/api/v1/jobs/createTask"
        
        headers = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {KIE_API_KEY}"
        }
        
        payload = {
            "model": "nano-banana-pro",
            "input": {
                "prompt": prompt,
                "aspect_ratio": aspect_ratio,
                "resolution": "1K",
                "output_format": "png"
            }
        }

        response = requests.post(create_url, json=payload, headers=headers)
        
        if response.status_code != 200:
            raise Exception(f"Create Task Error: {response.text}")
            
        task_data = response.json()
        if task_data.get('code') != 200:
            raise Exception(f"API Error: {task_data.get('message')}")
            
        task_id = task_data['data']['taskId']
        
        # ======================================================
        # ⏳ ຂັ້ນຕອນທີ 2: ວົນຖາມຫາຜົນລັດ (Polling)
        # ======================================================
        query_url = "https://api.kie.ai/api/v1/jobs/queryTask"
        result_url = None
        
        # ວົນຖາມທຸກໆ 2 ວິນາທີ (ສູງສຸດ 30 ຮອບ = 60 ວິ)
        for i in range(30):
            time.sleep(2) # ພັກ 2 ວິນາທີ
            
            q_response = requests.get(f"{query_url}?taskId={task_id}", headers=headers)
            q_data = q_response.json()
            
            if q_data.get('code') != 200:
                continue
                
            state = q_data['data']['state'] # pending, running, success, fail
            
            if state == 'success':
                # ແກະ URL ຮູບຈາກ resultJson (ມັນເປັນ String ຊ້ອນ String)
                result_json_str = q_data['data']['resultJson']
                result_obj = json.loads(result_json_str)
                result_url = result_obj['resultUrls'][0]
                break
            elif state == 'fail':
                raise Exception(f"Generation Failed: {q_data['data'].get('failMsg')}")
            
            # ຖ້າ state == 'running' ຫຼື 'pending' ກໍວົນຕໍ່ໄປ...

        if not result_url:
            raise Exception("Timeout: ຖ້າດົນເກີນໄປ ກະລຸນາລອງໃໝ່")

        # ======================================================
        # 🖼️ ຂັ້ນຕອນທີ 3: ດາວໂຫລດ ແລະ ແຕ່ງຮູບ (ຄືເກົ່າ)
        # ======================================================
        img_data = requests.get(result_url).content
        temp_filename = f"generated_{os.urandom(4).hex()}.png"
        temp_path = os.path.join(current_dir, f"../assets/images/{temp_filename}")
        
        with open(temp_path, 'wb') as f:
            f.write(img_data)
            
        # ໃສ່ຂໍ້ຄວາມ
        img = Image.open(temp_path)
        draw = ImageDraw.Draw(img)
        font_path = os.path.join(current_dir, "fonts/Phetsarath_OT.ttf")
        
        for key, config in text_config.items():
            if key in user_texts and user_texts[key]:
                try:
                    font = ImageFont.truetype(font_path, config.get('size', 40))
                    color = config.get('color', '#ffffff')
                    draw.text((config['x'], config['y']), user_texts[key], font=font, fill=color)
                except:
                    pass

        # ໃສ່ Logo
        if logo_path and os.path.exists(logo_path):
            try:
                logo = Image.open(logo_path).convert("RGBA")
                logo.thumbnail((150, 150))
                img.paste(logo, (img.width - logo.width - 20, 20), logo)
            except:
                pass

        final_filename = f"final_{os.urandom(4).hex()}.png"
        save_path = os.path.join(current_dir, f"../assets/images/{final_filename}")
        img.save(save_path)

        print(json.dumps({
            "status": "success",
            "url": f"assets/images/{final_filename}"
        }))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))

if __name__ == "__main__":
    process_image()