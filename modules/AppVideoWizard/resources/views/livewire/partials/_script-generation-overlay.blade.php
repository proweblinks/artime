{{-- Script Generation Loading Overlay --}}
@if($isGeneratingScript)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 10200; backdrop-filter: blur(4px);"
     x-data="{
         progress: 0,
         stageIndex: 0,
         stages: [
             '{{ __("Analyzing content...") }}',
             '{{ __("Crafting narration...") }}',
             '{{ __("Building scene structure...") }}',
             '{{ __("Polishing script...") }}',
             '{{ __("Almost ready...") }}'
         ],
         interval: null,
         init() {
             this.interval = setInterval(() => {
                 if (this.progress < 90) {
                     this.progress += Math.random() * 4 + 1;
                     if (this.progress > 90) this.progress = 90;
                 }
                 let newStage = Math.min(Math.floor(this.progress / 20), this.stages.length - 1);
                 if (newStage !== this.stageIndex) this.stageIndex = newStage;
             }, 800);
         },
         destroy() {
             if (this.interval) clearInterval(this.interval);
         }
     }"
     x-init="init()"
     @beforeunmount.window="destroy()">
    <div class="text-center" style="max-width: 360px;">
        <div class="mb-4" style="position: relative; display: inline-block;">
            <i class="fa-light fa-sparkles" style="font-size: 2.4rem; color: #03fcf4; animation: pulse-glow 2s ease-in-out infinite;"></i>
        </div>
        <h5 class="fw-bold mb-2" style="color: #ffffff; font-size: 1.1rem;">{{ __('Creating Your Script') }}</h5>
        <p class="mb-4" style="color: rgba(255,255,255,0.7); font-size: 0.85rem;" x-text="stages[stageIndex]"></p>
        <div style="background: rgba(255,255,255,0.1); border-radius: 6px; height: 6px; width: 280px; margin: 0 auto; overflow: hidden;">
            <div style="height: 100%; background: linear-gradient(90deg, #03fcf4, #00d4cc); border-radius: 6px; transition: width 0.6s ease;"
                 :style="'width: ' + Math.round(progress) + '%'"></div>
        </div>
        <p class="mt-2 mb-0" style="color: rgba(255,255,255,0.5); font-size: 0.75rem;" x-text="Math.round(progress) + '%'"></p>
    </div>
</div>
<style>
    @keyframes pulse-glow {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
</style>
@endif
