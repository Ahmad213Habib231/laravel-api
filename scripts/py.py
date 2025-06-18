import sys
from ultralytics import YOLO
import cv2
import os

try:
 
    image_path = sys.argv[1]
    model_path = sys.argv[2]

    print(f"Loading image from: {image_path}")
    print(f"Loading model from: {model_path}")

    if not os.path.exists(image_path):
        print(f"Error: Image path does not exist: {image_path}")
        sys.exit(1)

    if not os.path.exists(model_path):
        print(f"Error: Model path does not exist: {model_path}")
        sys.exit(1)

    model = YOLO(model_path)

    image = cv2.imread(image_path)
    if image is None:
        print(f"Error: Failed to load image from {image_path}")
        sys.exit(1)

    results = model(image)

    results.show()

    detections = results.pandas().xywh[0].to_json(orient="records")  
    print(detections)

except Exception as e:
    print(f"Error: {str(e)}")
    sys.exit(1)
