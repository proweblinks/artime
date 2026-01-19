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

def download_file(url, save_name):
    response = requests.get(url)
    save_path = f"input/{save_name}"
    with open(save_path, 'wb') as f:
        f.write(response.content)
    print(f"runpod-worker-comfy - Saved {url} to {save_path}")

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
    audio_name = history[prompt_id]["outputs"][str(node_id)]["gifs"][0]["filename"]

    return audio_name

def handler(job):
    try:
        # Ensure required params
        required_params = ["image_url", "audio_url", "video_upload_url", "audio_crop_start_time", "audio_crop_end_time", "positive_prompt", "negative_prompt", "aspect_ratio", "scale_to_length", "scale_to_side", "fps", "num_frames", "embeds_audio_scale", "embeds_cfg_audio_scale", "embeds_multi_audio_type", "embeds_normalize_loudness", "steps", "seed", "scheduler"]
        for param in required_params:
            if param not in job['input']:
                return {"status": 400, "message": f"Missing required parameter '{param}'"}
            
        # Get params
        image_url = job['input']["image_url"]
        audio_url = job['input']["audio_url"]
        video_upload_url = job['input']["video_upload_url"]

        audio_crop_start_time = job['input']["audio_crop_start_time"]
        audio_crop_end_time = job['input']["audio_crop_end_time"]
        positive_prompt = job['input']["positive_prompt"]
        negative_prompt = job['input']["negative_prompt"]
        aspect_ratio = job['input']["aspect_ratio"]
        scale_to_side = job['input']["scale_to_side"]
        scale_to_length = int(job['input']["scale_to_length"])
        fps = float(job['input']["fps"])
        num_frames = int(job['input']["num_frames"])
        steps = int(job['input']["steps"])
        seed = int(job['input']["seed"])
        scheduler = job['input']["scheduler"]
        embeds_audio_scale = float(job['input']["embeds_audio_scale"])
        embeds_cfg_audio_scale = float(job['input']["embeds_cfg_audio_scale"])
        embeds_multi_audio_type = job['input']["embeds_multi_audio_type"]
        embeds_normalize_loudness = bool(job['input']["embeds_normalize_loudness"])


        seed = generate_random_seed() if seed == -1 else seed
        print("Using seed:",seed)

        # Download files
        image_name = f"input_image_{generate_random_seed()}.png"
        audio_name = f"input_audio_{generate_random_seed()}.mp3"
        download_file(image_url, image_name)
        download_file(audio_url, audio_name)

        with open('multitalk_api.json', 'r') as file:
            workflow = json.load(file)
   
        # Set values
        workflow["218"]["inputs"]["image"] = image_name 
        workflow["219"]["inputs"]["audio"] = audio_name

        workflow["223"]["inputs"]["start_time"] = audio_crop_start_time 
        workflow["223"]["inputs"]["end_time"] = audio_crop_end_time

        workflow["135"]["inputs"]["positive_prompt"] = positive_prompt
        workflow["135"]["inputs"]["negative_prompt"] = negative_prompt

        workflow["233"]["inputs"]["aspect_ratio"] = aspect_ratio
        workflow["233"]["inputs"]["scale_to_side"] = scale_to_side
        workflow["233"]["inputs"]["scale_to_length"] = scale_to_length

        workflow["224"]["inputs"]["audio_scale"] = embeds_audio_scale
        workflow["224"]["inputs"]["audio_cfg_scale"] = embeds_cfg_audio_scale
        workflow["224"]["inputs"]["multi_audio_type"] = embeds_multi_audio_type
        workflow["224"]["inputs"]["normalize_loudness"] = embeds_normalize_loudness

        workflow["198"]["inputs"]["seed"] = seed
        workflow["198"]["inputs"]["steps"] = steps
        workflow["198"]["inputs"]["scheduler"] = scheduler

        workflow["226"]["inputs"]["value"] = fps
        workflow["228"]["inputs"]["value"] = num_frames
       

        url = "http://127.0.0.1:8188"
        check_server(url)

        # create prompt
        p = {"prompt": workflow}
        data = json.dumps(p).encode('utf-8')
        headers = { 'Content-Type': 'application/json'}
        response =  requests.request("POST",f"{url}/prompt", data=data,headers=headers)
        prompt_id = response.json()["prompt_id"]

        video_name = poll_result(url=url, prompt_id=prompt_id, node_id=221) 

        # upload to bucket
        upload_file(f"output/{video_name}", video_upload_url)
        return {"status": 200, "message": "Video created successfully", "payload": {}}

    except Exception as e:
        return {"status": 500, "message": str(e)}

runpod.serverless.start({"handler": handler})