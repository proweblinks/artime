import runpod
import os
import time
import json
import requests
import string
import random
import io
from PIL import Image

def upload_file(file_path, pre_signed_url):
    with open(file_path, 'rb') as file:
        headers = {
            'Content-Type': 'application/octet-stream'
        }
        response = requests.put(pre_signed_url, data=file, headers=headers)

    if response.status_code == 200:
        print(f"runpod-worker-comfy - Uploaded {file_path} to storage successful!")
    else:
        print(f"runpod-worker-comfy - Upload failed. Status code: {response.status_code}")
        print(f"runpod-worker-comfy - Response: {response.text}")

def generate_random_seed():
    return random.randint(10**15, 10**16 - 1)

def check_server(url, retries=50, delay=500):
    for i in range(retries):
        try:
            response = requests.get(url)

            if response.status_code == 200:
                print(f"runpod-worker-comfy - API is reachable")
                return True
        except requests.RequestException as e:
            pass

        time.sleep(delay / 1000)

    print(
        f"runpod-worker-comfy - Failed to connect to server at {url} after {retries} attempts."
    )
    return False

def poll_result(url, prompt_id, node_id):
    completed = False
    history = {}

    while not completed:
        time.sleep(1)
        response = requests.request("GET",f"{url}/history")
        history = response.json()
        completed = prompt_id in history
    audio_name = history[prompt_id]["outputs"][str(node_id)]["audio"][0]["filename"]

    return audio_name

def handler(job):
    try:
        # Ensure required params
        required_params = ["prompt", "speaker", "audio_upload_url"]
        for param in required_params:
            if param not in job['input']:
                return {"status": 400, "message": f"Missing required parameter '{param}'"}
            
        # Get params
        prompt = job['input']["prompt"]
        speaker = job['input']["speaker"]
        audio_upload_url = job['input']["audio_upload_url"]

        with open('kokoro_tts_api.json', 'r') as file:
            workflow = json.load(file)
   
        # Set values
        workflow["16"]["inputs"]["text"] = prompt
        workflow["16"]["inputs"]["speaker"] = speaker 

        url = "http://127.0.0.1:8188"
        check_server(url)

        # create prompt
        p = {"prompt": workflow}
        data = json.dumps(p).encode('utf-8')
        headers = { 'Content-Type': 'application/json'}
        response =  requests.request("POST",f"{url}/prompt", data=data,headers=headers)
        prompt_id = response.json()["prompt_id"]

        audio_name = poll_result(url=url, prompt_id=prompt_id, node_id=5 ) 

        # upload to bucket
        upload_file(f"output/{audio_name}", audio_upload_url)
        return {"status": 200, "message": "Audio created successfully", "payload": {}}

    except Exception as e:
        return {"status": 500, "message": str(e)}

runpod.serverless.start({"handler": handler})