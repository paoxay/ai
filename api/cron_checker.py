import time
import requests
import datetime

# ປ່ຽນເປັນ URL ເວັບໄຊຂອງເຈົ້າ
API_URL = "http://localhost:8080/ai/api/check_all.php"

def run_scheduler():
    print(f"🚀 ເລີ່ມຕົ້ນລະບົບ Auto Check... ({API_URL})")
    
    while True:
        try:
            current_time = datetime.datetime.now().strftime("%H:%M:%S")
            print(f"[{current_time}] ກຳລັງກວດສອບ...")

            # ຍິງ Request ໄປຫາ PHP
            response = requests.get(API_URL, timeout=30)
            
            # ສະແດງຜົນລັບທີ່ PHP ສົ່ງມາ (Log)
            if response.status_code == 200:
                # ລ້າງ HTML tags ອອກໃຫ້ອ່ານງ່າຍ (ຖ້າຢາກເຮັດ) ຫຼື ໂຊເລີຍ
                print(f"   Status Code: {response.status_code}")
                # ຖ້າມີການຕອບກັບຍາວໆ ໃຫ້ຕັດມາສະແດງພໍປະມານ
                print(f"   Response: {response.text[:200]}...") 
            else:
                print(f"   ⚠️ Error: Server ຕອບກັບມາ {response.status_code}")

        except Exception as e:
            print(f"   ❌ Connection Error: {e}")

        # ລໍຖ້າ 50 ວິນາທີ ກ່ອນຮອບຕໍ່ໄປ
        print("   ...ພັກ 50 ວິນາທີ...")
        time.sleep(50)

if __name__ == "__main__":
    run_scheduler()