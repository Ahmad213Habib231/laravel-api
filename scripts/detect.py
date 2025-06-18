import sys
import os
import json
import logging
from ultralytics import YOLO

try:
    image_path = sys.argv[1]
    model_path = sys.argv[2]

    # قلل مستوى الـ logging من مكتبة Ultralytics
    logging.getLogger('ultralytics').setLevel(logging.CRITICAL)

    # تحقق من المسارات
    if not os.path.exists(image_path):
        print(json.dumps({
            "status": False,
            "message": f"Image path does not exist: {image_path}"
        }))
        sys.exit(1)

    if not os.path.exists(model_path):
        print(json.dumps({
            "status": False,
            "message": f"Model path does not exist: {model_path}"
        }))
        sys.exit(1)

    # تحميل الموديل
    model = YOLO(model_path)

    # التنبؤ بالصورة
    results = model.predict(source=image_path, save=False, save_txt=False, verbose=False)

    # الحصول على النتائج وتحويلها إلى JSON نقي
    raw_detections = results[0].tojson()

    # التأكد أن الناتج عبارة عن قائمة
    if isinstance(raw_detections, str):
        detections = json.loads(raw_detections)
    else:
        detections = raw_detections

    # طباعة JSON نظيف
    print(json.dumps(detections, indent=2))

except Exception as e:
    print(json.dumps({
        "status": False,
        "message": "Detection script error",
        "error": str(e)
    }))
    sys.exit(1)
