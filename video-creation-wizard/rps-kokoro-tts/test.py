import json
import requests
import time
from cloudflare_r2 import create_presigned_url

audio_upload_url = create_presigned_url(
    bucket_name="kokoro-tts",
    object_name="audio-9.flac",
    operation='put_object',
    expiration=3600 
)

payload = json.dumps({
    "input":{
        "prompt": "hey this is cool, isn't it great to hear Volta is finally in the music world. Excited ! i know, but. what's more fascinating is that now you have unlimited high quality voice for free.\nEnjoy and don't forget to thank me for bringing this to mimic pc",
        "speaker": "am_michael",
        "audio_upload_url": audio_upload_url
    }
})

start = time.time()

endpoint_url = "https://api.runpod.ai/v2/pzi4079jucgobi"
headers = {
    'authorization': "rpa",
    'content-type': 'application/json',
}

try:
    response = requests.request(
        "POST", f"{endpoint_url}/run", headers=headers, data=payload)
    data = response.json()

    job_id = data["id"]
    job_finished = False
    result = {}

    while not job_finished:
        time.sleep(1)
        response = requests.request(
            "GET", f"{endpoint_url}/status/{job_id}", headers=headers)
        result = response.json()
        job_finished = result["status"] == "COMPLETED"

    end = time.time()
    print("Total time take (sec):", end-start)

except Exception as e:
    print(e)