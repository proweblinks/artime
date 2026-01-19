import json
import requests
import time
import os
from cloudflare_r2 import create_presigned_url

video_upload_url = create_presigned_url(
    bucket_name="multitalk-videos",
    object_name="multitalk79999.mp4",
    operation='put_object',
    expiration=3600 
)

payload = json.dumps({
    "input":{
        "image_url":"https://siteuo.com/media/storage_b1c507fd453c57d0/175465221211ceec2ea5ca448e9acbbc2dcbc73692.webp",
        "audio_url": "https://pub-45d8d7703ee841ac947bf4df52b73f44.r2.dev/audio_multitalk.mp3",
        "audio_crop_start_time": "0:00",
        "audio_crop_end_time": "0:22",
        "positive_prompt": "A cheerful and charismatic social media influencer sits on a rooftop at golden hour, dressed in athletic wear, bathed in warm sunlight. She looks directly at the camera, smiling confidently. Her hair gently moves in the breeze, catching the light. As the camera stays still in a medium-close shot, she speaks naturally and expressively for 10 seconds.\n\nSpeech (10 seconds):\n\"Hey loves! Just finished my morning yoga—feeling so grounded and energized. Don’t forget to hydrate, stretch, and slay your goals today. You’ve got this!\"\n\nInstructions:\n\nStyle: 4K, photorealistic, soft lens flare.\n\nTone: Warm, inspirational, energetic.\n\nFacial movement: Natural blinking, expressive brows, lip-sync with speech.\n\nCamera: Stationary, front-facing, steady shot.\n\nLighting: Soft golden backlight, natural shadow contouring.\n\nLoop: No loop, one continuous 10s shot.",
        "negative_prompt": "simultaneous talking, both speakers talking at once, overlapping speech, synchronized mouth movements, identical gestures, mirrored actions, both people speaking together, multilingual mixing, language switching, foreign languages, non-English speech, robotic movements, unnatural lip sync, frozen expressions, identical facial expressions, puppet-like movements, mechanical gestures, both speakers moving identically, synchronized head nodding, artificial smiling, fake reactions, stiff postures, uncomfortable positioning, poor audio sync, mismatched lip movements, double speaking, echo effects, multiple voices at once, confusing dialogue, unclear speaker turns, simultaneous hand gestures, coordinated movements, unrealistic interactions, artificial conversation flow, robotic speech patterns, monotone delivery, expressionless faces, static poses, awkward positioning, poor eye contact, disconnected interaction, unnatural pauses, rushed speech, overlapping audio, audio bleeding, microphone feedback, background noise interference, poor lighting continuity, inconsistent shadows, warped faces, distorted features, blurry motion, choppy animation, frame stuttering, temporal artifacts, morphing between speakers, face swapping, identity confusion, gender confusion, clothing changes, background shifts, perspective warping, scale inconsistencies,\nbright tones, overexposed, static, blurred details, subtitles, style, works, paintings, images, static, overall gray, worst quality, low quality, JPEG compression residue, ugly, incomplete, extra fingers, poorly drawn hands, poorly drawn faces, deformed, disfigured, misshapen limbs, fused fingers, still picture, messy background, three legs, many people in the background, walking backwards,",
        "aspect_ratio": "16:9",
        "scale_to_length": 1024,
        "scale_to_side": "None",
        "fps": 16,
        "num_frames": 288,
        "embeds_audio_scale": 1.00,
        "embeds_cfg_audio_scale": 2.00,
        "embeds_multi_audio_type": "para",
        "embeds_normalize_loudness": True,
        "steps": 4,
        "seed": -1,
        "scheduler": "lcm",
        "video_upload_url": video_upload_url
    }
})

start = time.time()

endpoint_url = os.environ.get("RUNPOD_MULTITALK_ENDPOINT", "https://api.runpod.ai/v2/YOUR_ENDPOINT_ID")
headers = {
    'authorization': os.environ.get("RUNPOD_API_KEY", ""),
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